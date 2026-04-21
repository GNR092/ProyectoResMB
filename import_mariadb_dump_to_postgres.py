#!/usr/bin/env python3
"""Importa un dump de MariaDB/MySQL a PostgreSQL con transformaciones basicas.

Uso rapido:
  python import_mariadb_dump_to_postgres.py \
    --dump compras_backup_20260418_172237.sql \
    --db mb_compras --user mbuser --password mbuser123

Notas:
  - Modo por defecto: solo inserciones (data-only).
  - Para recrear tablas (sin llaves foraneas), usa: --mode full
"""

from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path
from typing import Dict, Iterable, List, Tuple

try:
    import psycopg2
except ImportError:
    print("Error: falta psycopg2. Instala con: pip install psycopg2-binary", file=sys.stderr)
    sys.exit(1)


SKIP_PREFIXES = (
    "SET ",
    "LOCK TABLES",
    "UNLOCK TABLES",
    "ALTER TABLE",
    "CREATE DATABASE",
    "USE ",
    "CREATE ALGORITHM",
    "CREATE DEFINER",
)

MYSQL_SYSTEM_TABLES = {
    "proc", "event", "general_log", "slow_log",
    "columns_priv", "db", "user", "func", "plugin",
    "servers", "help_topic", "help_category", "help_relation",
    "help_keyword", "time_zone_name", "time_zone", "time_zone_transition",
    "time_zone_transition_type", "time_zone_leap_second",
    "gtid_slave_pos", "slave_master_info", "slave_relay_log_info",
    "slave_worker_info", "innodb_index_stats", "innodb_table_stats",
    "plugin", "ndb_binlog_index", "proxies_priv", "tables_priv",
    "column_stats", "index_stats", "table_stats", "global_priv",
    "password_history", "roles_mapping", "default_roles", "fact_filters",
    "store_info", "time_basis", "cas_user_account_attributes",
}

_table_names = "|".join(re.escape(t) for t in MYSQL_SYSTEM_TABLES)
MYSQL_SYSTEM_TABLES_RE = re.compile(
    r"^\s*(?:INSERT\s+INTO|CREATE\s+TABLE|DROP\s+TABLE)\s+(?:`?(?:" + _table_names + r"))\b",
    re.I,
)

# Orden correcto basado en dependencias FK (menor a mayor dependencia).
INSERT_TABLE_ORDER = [
    "Places",                      # 1. Sin dependencias
    "Razon_Social",                # 2. Sin dependencias
    "segmento_negocio",            # 3. FK -> Razon_Social
    "BancoDpto",                   # 4. FK -> Razon_Social (dump nuevo: ID_RazonSocial)
    "Departamentos",              # 5. FK -> Places, UnidadOperativa
    "UnidadOperativa",             # 6. FK -> Places
    "GrupoPresupuestal",           # 7. FK -> UnidadOperativa
    "Usuarios",                    # 8. FK -> Departamentos, Razon_Social
    "Proveedor",                   # 9. Sin FK
    "Producto",                     # 10. Sin FK
    "ApiToken",                    # 11. Sin FK
    "PresupuestoAnual",            # 12. Sin FK
    "PresupuestoMensual",          # 13. FK -> GrupoPresupuestal, UnidadOperativa
    "SaldosBancarios",             # 14. Sin FK
    "Cuentas",                     # 15. Sin FK
    "User_Tokens",                 # 16. FK -> Usuarios
    "Solicitud",                   # 17. FK -> Usuarios, Proveedor, UnidadOperativa
    "Solicitud_Producto",           # 18. FK -> Solicitud
    "Solicitud_Servicios",          # 19. FK -> Solicitud
    "Detalle_Servicio",            # 20. FK -> Solicitud_Servicios
    "Cotizacion",                  # 21. FK -> Solicitud, Proveedor
    "OrdenCompra",                 # 22. FK -> Cotizacion, Proveedor, GrupoPresupuestal
    "Pago",                        # 23. FK -> OrdenCompra
    "Entregas",                    # 24. FK -> Solicitud
    "DetalleEntrega",              # 25. FK -> Entregas, Producto
    "HistorialProductos",          # 26. Sin FK
    "MapeoProductos",              # 27. Sin FK
    "bitacora",                    # 28. Sin FK
    "proveedor_archivos",          # 29. FK -> Proveedor
    "SolicitudesCambioPresupuesto", # 30. Sin FK
]
TABLE_PRIORITY = {name.lower(): idx for idx, name in enumerate(INSERT_TABLE_ORDER)}


def remove_comments(sql_text: str) -> str:
    """Quita comentarios SQL y conserva contenido de comentarios condicionales MySQL."""
    sql_text = re.sub(r"/\*![0-9]+\s*(.*?)\*/", r"\1", sql_text, flags=re.S)
    sql_text = re.sub(r"/\*(?!\!).*?\*/", "", sql_text, flags=re.S)

    lines: List[str] = []
    for line in sql_text.splitlines():
        stripped = line.lstrip()
        if stripped.startswith("--") or stripped.startswith("#"):
            continue
        lines.append(line)
    return "\n".join(lines)


def split_statements(sql_text: str) -> List[Tuple[int, str]]:
    """Separa sentencias por ';' respetando strings simples."""
    statements: List[Tuple[int, str]] = []
    buff: List[str] = []
    in_string = False
    escaped = False
    line = 1
    start_line = 1

    for ch in sql_text:
        if not buff and not ch.isspace():
            start_line = line

        buff.append(ch)

        if ch == "\n":
            line += 1

        if in_string:
            if escaped:
                escaped = False
            elif ch == "\\":
                escaped = True
            elif ch == "'":
                in_string = False
        else:
            if ch == "'":
                in_string = True
            elif ch == ";":
                stmt = "".join(buff).strip()
                if stmt:
                    statements.append((start_line, stmt))
                buff = []

    remaining = "".join(buff).strip()
    if remaining:
        statements.append((start_line, remaining))

    return statements


def split_top_level_commas(text: str) -> List[str]:
    """Divide por comas ignorando parentesis y strings simples."""
    parts: List[str] = []
    buff: List[str] = []
    depth = 0
    in_string = False
    escaped = False
    backslash_next = False

    for ch in text:
        if backslash_next:
            backslash_next = False
            buff.append(ch)
            continue

        if not in_string and ch == '\\':
            backslash_next = True
            buff.append(ch)
            continue

        if in_string:
            buff.append(ch)
            if escaped:
                escaped = False
            elif ch == "\\":
                escaped = True
            elif ch == "'":
                in_string = False
            continue

        if ch == "'":
            in_string = True
            buff.append(ch)
            continue

        if ch == "(":
            depth += 1
            buff.append(ch)
            continue

        if ch == ")":
            depth = max(depth - 1, 0)
            buff.append(ch)
            continue

        if ch == "," and depth == 0:
            parts.append("".join(buff).strip())
            buff = []
            continue

        buff.append(ch)

    tail = "".join(buff).strip()
    if tail:
        parts.append(tail)
    return parts


def extract_insert_table_name(stmt: str) -> str | None:
    m = re.match(r'^\s*INSERT\s+INTO\s+"([^"]+)"\s+', stmt, flags=re.I)
    if not m:
        return None
    return m.group(1)


def sort_insert_statements_by_priority(statements: List[Tuple[int, str]]) -> List[Tuple[int, str]]:
    def key_fn(item: Tuple[int, str]) -> Tuple[int, int]:
        line, stmt = item
        table = extract_insert_table_name(stmt)
        if not table:
            return (10_000, line)
        return (TABLE_PRIORITY.get(table.lower(), 9_999), line)

    return sorted(statements, key=key_fn)


def extract_source_columns(statements: Iterable[Tuple[int, str]]) -> Dict[str, List[str]]:
    """Extrae orden de columnas desde CREATE TABLE del dump."""
    table_cols: Dict[str, List[str]] = {}

    for _, stmt in statements:
        if not stmt.lstrip().upper().startswith("CREATE TABLE"):
            continue

        m = re.search(r"CREATE\s+TABLE\s+`([^`]+)`\s*\((.*)\)\s*ENGINE=", stmt, flags=re.I | re.S)
        if not m:
            continue

        table = m.group(1)
        body = m.group(2)
        defs = split_top_level_commas(body)
        cols: List[str] = []

        for item in defs:
            item = item.strip()
            col_m = re.match(r"^`([^`]+)`\s+", item)
            if col_m:
                cols.append(col_m.group(1))

        if cols:
            table_cols[table] = cols

    return table_cols


def get_pg_table_info(cur, table_name: str, cache: Dict[str, List[Dict[str, str]]]) -> List[Dict[str, str]]:
    if table_name in cache:
        return cache[table_name]

    cur.execute(
        """
        SELECT
            column_name,
            is_nullable,
            column_default,
            is_identity,
            identity_generation,
            data_type
        FROM information_schema.columns
        WHERE table_schema = current_schema()
          AND table_name = %s
        ORDER BY ordinal_position
        """,
        (table_name,),
    )

    info: List[Dict[str, str]] = []
    for row in cur.fetchall():
        info.append(
            {
                "column_name": row[0],
                "is_nullable": row[1],
                "column_default": row[2],
                "is_identity": row[3],
                "identity_generation": row[4],
                "data_type": row[5],
            }
        )

    cache[table_name] = info
    return info


def adapt_insert_to_existing_table(
    stmt: str,
    cur,
    source_columns: Dict[str, List[str]],
    target_cache: Dict[str, List[Dict[str, str]]],
    strict: bool,
) -> str | None:
    """Adapta INSERT sin lista de columnas al esquema real de PostgreSQL."""

    def convert_mysql_to_pg(sql: str) -> str:
        sql = sql.replace("\\'", "''")
        sql = sql.replace('\\"', '"')
        sql = sql.replace('\\\\', '\\')
        return sql

    m = re.match(r'^INSERT\s+INTO\s+"([^"]+)"\s+VALUES\s*(.+);$', stmt.strip(), flags=re.I | re.S)
    if not m:
        return stmt

    table = m.group(1)
    values_block = m.group(2).strip()

    src_cols = source_columns.get(table)
    if not src_cols:
        return convert_mysql_to_pg(stmt)

    tgt_info = get_pg_table_info(cur, table, target_cache)
    if not tgt_info:
        return convert_mysql_to_pg(stmt)

    tgt_cols = [c["column_name"] for c in tgt_info]

    tgt_lookup = {c["column_name"].lower(): c for c in tgt_info}
    tgt_col_types = {c["column_name"].lower(): c["data_type"] for c in tgt_info}
    boolean_cols: set[str] = set()
    json_cols: set[str] = set()
    for col in tgt_info:
        dt = (col.get("data_type") or "").lower()
        if dt in ("boolean", "bool"):
            boolean_cols.add(col["column_name"].lower())
        if dt in ("json", "jsonb"):
            json_cols.add(col["column_name"].lower())

    selected_indexes: List[int] = []
    selected_columns: List[str] = []

    for idx, col in enumerate(src_cols):
        pg_col_entry = tgt_lookup.get(col.lower())
        if pg_col_entry:
            selected_indexes.append(idx)
            selected_columns.append(pg_col_entry["column_name"])

    if not selected_indexes:
        return stmt

    selected_lower = {c.lower() for c in selected_columns}
    missing_required: List[str] = []
    for col in tgt_info:
        name = col["column_name"]
        if name.lower() in selected_lower:
            continue

        is_nullable = (col["is_nullable"] or "").upper() == "YES"
        has_default = col["column_default"] is not None
        is_identity = (col["is_identity"] or "").upper() == "YES"

        if (not is_nullable) and (not has_default) and (not is_identity):
            missing_required.append(name)

    if missing_required:
        msg = (
            f"Tabla '{table}' incompatible: faltan columnas requeridas en dump -> PG: "
            f"{', '.join(missing_required)}"
        )
        if strict:
            raise RuntimeError(msg)
        print(
            f"Aviso: se omite tabla '{table}' por columnas requeridas faltantes: {', '.join(missing_required)}",
            file=sys.stderr,
        )
        return None

    rows = split_top_level_commas(values_block)
    new_rows: List[str] = []

    for row in rows:
        row = row.strip()
        if not (row.startswith("(") and row.endswith(")")):
            return convert_mysql_to_pg(stmt)

        row_values = split_top_level_commas(row[1:-1])
        if len(row_values) != len(src_cols):
            return convert_mysql_to_pg(stmt)

        picked = []
        for j, i in enumerate(selected_indexes):
            val = row_values[i].strip()
            val = val.replace("\\'", "''")
            val = val.replace('\\"', '"')
            val = val.replace('\\\\', '\\')
            col_name = selected_columns[j].lower()
            if col_name in boolean_cols and re.match(r'^(0|1|TRUE|FALSE)$', val, re.I):
                picked.append('TRUE' if val.upper() in ('1', 'TRUE') else 'FALSE')
            elif col_name in json_cols and val not in ('NULL', 'null', ''):
                if val.startswith("'") and val.endswith("'"):
                    val = val[1:-1]
                picked.append(f"'{val}'")
            else:
                picked.append(val)
        new_rows.append("(" + ", ".join(picked) + ")")

    cols_sql = ", ".join(f'"{col}"' for col in selected_columns)
    rows_sql = ", ".join(new_rows)
    return f'INSERT INTO "{table}" ({cols_sql}) VALUES {rows_sql} ON CONFLICT DO NOTHING;'


def explode_insert_rows(stmt: str) -> List[str]:
    """Convierte INSERT multi-row en INSERT de una sola fila."""
    m = re.match(
        r'^(INSERT\s+INTO\s+"[^"]+"(?:\s*\([^)]+\))?\s+VALUES)\s*(.+);$',
        stmt.strip(),
        flags=re.I | re.S,
    )
    if not m:
        return [stmt]

    prefix = m.group(1)
    values_block = m.group(2).strip()
    rows = split_top_level_commas(values_block)
    if len(rows) <= 1:
        return [stmt]

    one_by_one: List[str] = []
    for row in rows:
        row = row.strip()
        if row.startswith("(") and row.endswith(")"):
            one_by_one.append(f"{prefix} {row};")
        else:
            return [stmt]
    return one_by_one


FK_CONSTRAINTS = {
    "Departamentos": [
        ('"ID_Place"', '"Places"', '"ID_Place"', 'CASCADE', 'CASCADE'),
    ],
    "UnidadOperativa": [
        ('"ID_Place"', '"Places"', '"ID_Place"', 'CASCADE', 'CASCADE'),
    ],
    "GrupoPresupuestal": [
        ('"ID_UnidadOperativa"', '"UnidadOperativa"', '"ID_UnidadOperativa"', 'CASCADE', 'SET NULL'),
    ],
    "Usuarios": [
        ('"ID_Dpto"', '"Departamentos"', '"ID_Dpto"', 'CASCADE', 'CASCADE'),
        ('"ID_RazonSocial"', '"Razon_Social"', '"ID_RazonSocial"', 'CASCADE', 'CASCADE'),
    ],
    "segmento_negocio": [
        ('"id_razon_social"', '"Razon_Social"', '"ID_RazonSocial"', 'CASCADE', 'CASCADE'),
    ],
    "BancoDpto": [
        ('"ID_RazonSocial"', '"Razon_Social"', '"ID_RazonSocial"', 'CASCADE', 'CASCADE'),
    ],
    "PresupuestoMensual": [
        ('"ID_UnidadOperativa"', '"UnidadOperativa"', '"ID_UnidadOperativa"', 'CASCADE', 'SET NULL'),
        ('"ID_GrupoPresupuestal"', '"GrupoPresupuestal"', '"ID_GrupoPresupuestal"', 'CASCADE', 'SET NULL'),
    ],
    "User_Tokens": [
        ('"ID_Usuario"', '"Usuarios"', '"ID_Usuario"', 'CASCADE', 'CASCADE'),
    ],
    "Solicitud": [
        ('"ID_Usuario"', '"Usuarios"', '"ID_Usuario"', 'CASCADE', 'CASCADE'),
        ('"ID_Proveedor"', '"Proveedor"', '"ID_Proveedor"', 'CASCADE', 'SET NULL'),
        ('"ID_UnidadOperativa"', '"UnidadOperativa"', '"ID_UnidadOperativa"', 'CASCADE', 'SET NULL'),
    ],
    "Solicitud_Producto": [
        ('"ID_Solicitud"', '"Solicitud"', '"ID_Solicitud"', 'CASCADE', 'CASCADE'),
    ],
    "Solicitud_Servicios": [
        ('"ID_Solicitud"', '"Solicitud"', '"ID_Solicitud"', 'CASCADE', 'CASCADE'),
    ],
    "Detalle_Servicio": [
        ('"ID_SolicitudServ"', '"Solicitud_Servicios"', '"ID_SolicitudServ"', 'CASCADE', 'CASCADE'),
    ],
    "Cotizacion": [
        ('"ID_Solicitud"', '"Solicitud"', '"ID_Solicitud"', 'CASCADE', 'CASCADE'),
        ('"ID_Proveedor"', '"Proveedor"', '"ID_Proveedor"', 'CASCADE', 'CASCADE'),
    ],
    "OrdenCompra": [
        ('"ID_Cotizacion"', '"Cotizacion"', '"ID_Cotizacion"', 'CASCADE', 'CASCADE'),
        ('"ID_Proveedor"', '"Proveedor"', '"ID_Proveedor"', 'CASCADE', 'CASCADE'),
        ('"ID_GrupoPresupuestal"', '"GrupoPresupuestal"', '"ID_GrupoPresupuestal"', 'CASCADE', 'SET NULL'),
    ],
    "Pago": [
        ('"ID_OrdenCompra"', '"OrdenCompra"', '"ID_OrdenCompra"', 'CASCADE', 'CASCADE'),
    ],
    "Entregas": [
        ('"ID_Solicitud"', '"Solicitud"', '"ID_Solicitud"', 'CASCADE', 'CASCADE'),
    ],
    "DetalleEntrega": [
        ('"ID_Entrega"', '"Entregas"', '"ID_Entrega"', 'CASCADE', 'CASCADE'),
        ('"ID_Producto"', '"Producto"', '"ID_Producto"', 'CASCADE', 'CASCADE'),
    ],
    "proveedor_archivos": [
        ('"ID_Proveedor"', '"Proveedor"', '"ID_Proveedor"', 'CASCADE', 'CASCADE'),
    ],
}


def recreate_foreign_keys(cur, conn) -> None:
    """Recrea todas las llaves foraneas basadas en las migraciones."""
    for table, fks in FK_CONSTRAINTS.items():
        for col, ref_table, ref_col, on_delete, on_update in fks:
            constraint_name = f"fk_{table.lower()}_{col.lower().replace('"', '')}"
            sql = f'ALTER TABLE "{table}" ADD CONSTRAINT "{constraint_name}" FOREIGN KEY ({col}) REFERENCES {ref_table} ({ref_col}) ON DELETE {on_delete} ON UPDATE {on_update}'
            try:
                cur.execute(sql)
            except Exception as exc:
                pgcode = getattr(exc, 'pgcode', None)
                if pgcode == '2350b' or 'already exists' in str(exc).lower():
                    pass
                else:
                    print(f"Aviso: No se pudo crear FK {constraint_name}: {exc}", file=sys.stderr)
    conn.commit()
    print("Llaves foraneas recreadas")


def cleanup_create_table(stmt: str) -> str:
    stmt = stmt.replace("`", '"')

    stmt = re.sub(r"\bbigint\(\d+\)", "bigint", stmt, flags=re.I)
    stmt = re.sub(r"\bsmallint\(\d+\)", "smallint", stmt, flags=re.I)
    stmt = re.sub(r"\bmediumint\(\d+\)", "integer", stmt, flags=re.I)
    stmt = re.sub(r"\bint\(\d+\)", "integer", stmt, flags=re.I)
    stmt = re.sub(r"\btimestamp\(\d+\)", "timestamp", stmt, flags=re.I)
    stmt = re.sub(r"\btinyint\(\d+\)", "smallint", stmt, flags=re.I)
    stmt = re.sub(r"\bdatetime\b", "timestamp", stmt, flags=re.I)
    stmt = re.sub(r"\bchar\(\d{3,}\)", "text", stmt, flags=re.I)
    stmt = re.sub(r"\bvarchar\(\d{3,}\)", "text", stmt, flags=re.I)
    stmt = re.sub(r"\blongtext\b", "text", stmt, flags=re.I)
    stmt = re.sub(r"\bmediumtext\b", "text", stmt, flags=re.I)
    stmt = re.sub(r"\btinytext\b", "text", stmt, flags=re.I)
    stmt = re.sub(r"\blongblob\b", "bytea", stmt, flags=re.I)
    stmt = re.sub(r"\bmediumblob\b", "bytea", stmt, flags=re.I)
    stmt = re.sub(r"\btinyblob\b", "bytea", stmt, flags=re.I)
    stmt = re.sub(r"\bblob\b", "bytea", stmt, flags=re.I)
    stmt = re.sub(r"\bvarbinary\(\d+\)", "bytea", stmt, flags=re.I)
    stmt = re.sub(r"\bbit\(\d+\)", "bit", stmt, flags=re.I)
    stmt = re.sub(r"\benum\([^)]+\)", "varchar(255)", stmt, flags=re.I)
    stmt = re.sub(r"\bset\([^)]+\)", "text", stmt, flags=re.I)
    stmt = re.sub(r"\bcurrent_timestamp\(\d*\)", "CURRENT_TIMESTAMP", stmt, flags=re.I)
    stmt = re.sub(r"'0000-00-00 00:00:00(?:\.\d+)?'", "'1970-01-01 00:00:00'", stmt, flags=re.I)

    stmt = re.sub(r"\bunsigned\b", "", stmt, flags=re.I)
    stmt = re.sub(r"\bAUTO_INCREMENT\b", "GENERATED BY DEFAULT AS IDENTITY", stmt, flags=re.I)

    stmt = re.sub(r"\s+COMMENT\s+'(?:[^'\\]|\\.)*'", "", stmt, flags=re.I)
    stmt = re.sub(r"\s+ON\s+UPDATE\s+CURRENT_TIMESTAMP", "", stmt, flags=re.I)
    stmt = re.sub(r"\s+CHARACTER SET\s+\w+", "", stmt, flags=re.I)
    stmt = re.sub(r"\s+COLLATE\s+\w+", "", stmt, flags=re.I)

    stmt = re.sub(
        r'^\s*CONSTRAINT\s+(?:"[^"]+"|`[^`]+`)\s+FOREIGN KEY\s*\([^)]+\)\s+REFERENCES\s+(?:"[^"]+"|`[^`]+`)\s*\([^)]+\)(?:\s+ON DELETE\s+[A-Z ]+)?(?:\s+ON UPDATE\s+[A-Z ]+)?,?\s*$',
        "",
        stmt,
        flags=re.I | re.M,
    )
    stmt = re.sub(
        r'^\s*CONSTRAINT\s+[a-zA-Z_]+\s+FOREIGN KEY\s*\([^)]+\)\s+REFERENCES\s+[a-zA-Z_]+\s*\([^)]+\)(?:\s+ON DELETE\s+[A-Z ]+)?(?:\s+ON UPDATE\s+[A-Z ]+)?,?\s*$',
        "",
        stmt,
        flags=re.I | re.M,
    )

    stmt = re.sub(
        r'^\s*(?:UNIQUE\s+)?KEY\s+(?:"[^"]+"|`[^`]+`)\s*\(([^)]+)\),?\s*$',
        r"  UNIQUE (\1),",
        stmt,
        flags=re.I | re.M,
    )

    stmt = re.sub(r'^\s*KEY\s+(?:"[^"]+"|`[^`]+`)\s*\([^)]+\),?\s*$', "", stmt, flags=re.I | re.M)

    stmt = re.sub(r"CHECK\s*\(json_valid\([^)]+\)\)", "", stmt, flags=re.I)
    stmt = re.sub(r"CHECK\s*\(`[^`]+`\s*>=?\s*0\)", "", stmt, flags=re.I)
    stmt = re.sub(r"CHECK\s*\(`[^`]+`\s*IS\s+NULL\)", "", stmt, flags=re.I)
    stmt = re.sub(r",\s*\)", "\n)", stmt, flags=re.S)
    stmt = re.sub(r"\n{3,}", "\n\n", stmt)

    return stmt.strip().rstrip(";") + ";"


def transform_statement(stmt: str) -> str | None:
    upper = stmt.lstrip().upper()
    if upper.startswith(SKIP_PREFIXES):
        return None

    if upper.startswith("DROP TABLE IF EXISTS"):
        stmt = stmt.replace("`", '"').strip().rstrip(";")
        if not re.search(r"\bCASCADE\b", stmt, flags=re.I):
            stmt = f"{stmt} CASCADE"
        return stmt + ";"

    if upper.startswith("CREATE TABLE"):
        if MYSQL_SYSTEM_TABLES_RE.match(stmt):
            return None
        return cleanup_create_table(stmt)

    if MYSQL_SYSTEM_TABLES_RE.match(stmt):
        return None

    stmt = stmt.replace("`", '"')
    return stmt.strip().rstrip(";") + ";"


def filter_mode(statements: Iterable[Tuple[int, str]], mode: str) -> List[Tuple[int, str]]:
    if mode == "full":
        return list(statements)

    filtered: List[Tuple[int, str]] = []
    for line, stmt in statements:
        if stmt.lstrip().upper().startswith("INSERT INTO"):
            filtered.append((line, stmt))
    return filtered


def collect_insert_tables(statements: Iterable[Tuple[int, str]]) -> List[str]:
    tables: List[str] = []
    seen: set[str] = set()
    for _, st in statements:
        tname = extract_insert_table_name(st)
        if tname and tname.lower() not in seen:
            tables.append(tname)
            seen.add(tname.lower())
    return tables


def get_existing_base_tables(cur) -> set[str]:
    cur.execute(
        """
        SELECT table_name FROM information_schema.tables
        WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
        """
    )
    return {r[0] for r in cur.fetchall()}


def collect_fk_constraints(cur, tables: List[str]) -> List[Tuple[str, str, str]]:
    if not tables:
        return []

    cur.execute(
        """
        SELECT c.relname AS table_name, con.conname, pg_get_constraintdef(con.oid)
        FROM pg_constraint con
        JOIN pg_class c ON c.oid = con.conrelid
        JOIN pg_namespace n ON n.oid = c.relnamespace
        WHERE con.contype = 'f'
          AND n.nspname = 'public'
          AND c.relname = ANY(%s)
        ORDER BY c.relname, con.conname
        """,
        (tables,),
    )
    return [(r[0], r[1], r[2]) for r in cur.fetchall()]


def drop_fk_constraints(cur, fk_constraints: List[Tuple[str, str, str]]) -> None:
    for table_name, conname, _ in fk_constraints:
        cur.execute(f'ALTER TABLE "{table_name}" DROP CONSTRAINT "{conname}"')


def restore_fk_constraints_not_valid(cur, fk_constraints: List[Tuple[str, str, str]]) -> None:
    for table_name, conname, condef in fk_constraints:
        cur.execute(
            f'ALTER TABLE "{table_name}" ADD CONSTRAINT "{conname}" {condef} NOT VALID'
        )


def validate_fk_constraints_one_by_one(cur, conn, fk_constraints: List[Tuple[str, str, str]]) -> List[Tuple[str, str, str]]:
    failed: List[Tuple[str, str, str]] = []
    validated = 0

    for table_name, conname, _ in fk_constraints:
        cur.execute("SAVEPOINT fk_validate_sp")
        try:
            cur.execute(f'ALTER TABLE "{table_name}" VALIDATE CONSTRAINT "{conname}"')
            cur.execute("RELEASE SAVEPOINT fk_validate_sp")
            validated += 1
        except Exception as exc:
            cur.execute("ROLLBACK TO SAVEPOINT fk_validate_sp")
            cur.execute("RELEASE SAVEPOINT fk_validate_sp")
            failed.append((table_name, conname, str(exc).replace("\n", " ")))

    conn.commit()
    print(f"Validacion FK por constraint: validadas={validated}, con_errores={len(failed)}")

    max_report = 12
    for table_name, conname, err in failed[:max_report]:
        print(f"FK inconsistente: {table_name}.{conname} -> {err}", file=sys.stderr)

    if len(failed) > max_report:
        print(
            f"... y {len(failed) - max_report} FK inconsistentes adicionales.",
            file=sys.stderr,
        )

    return failed


def main() -> int:
    parser = argparse.ArgumentParser(description="Importador MariaDB dump -> PostgreSQL")
    parser.add_argument("--dump", required=True, help="Ruta del .sql origen (MariaDB/MySQL)")
    parser.add_argument("--host", default="127.0.0.1", help="Host PostgreSQL")
    parser.add_argument("--port", type=int, default=5432, help="Puerto PostgreSQL")
    parser.add_argument("--db", default="mb_compras", help="Base de datos destino")
    parser.add_argument("--user", default="mbuser", help="Usuario PostgreSQL")
    parser.add_argument("--password", default="mbuser123", help="Password PostgreSQL")
    parser.add_argument(
        "--mode",
        choices=["data-only", "full"],
        default="data-only",
        help="data-only: solo INSERT. full: ejecuta DDL + INSERT (sin FKs).",
    )
    parser.add_argument("--commit-every", type=int, default=200, help="Commit cada N sentencias")
    parser.add_argument(
        "--truncate-first",
        action="store_true",
        help="(data-only) vacia las tablas destino antes de insertar (TRUNCATE ... RESTART IDENTITY CASCADE).",
    )
    parser.add_argument(
        "--strict",
        action="store_true",
        help="No omite filas/tablas incompatibles ni pendientes por FK; aborta con error.",
    )
    parser.add_argument(
        "--keep-fk-validated",
        action="store_true",
        help="En data-only, falla si alguna FK restaurada no puede validarse.",
    )
    args = parser.parse_args()

    dump_path = Path(args.dump)
    if not dump_path.exists():
        print(f"No existe el dump: {dump_path}", file=sys.stderr)
        return 1

    raw = dump_path.read_text(encoding="utf-8", errors="replace")
    cleaned = remove_comments(raw)
    all_statements = split_statements(cleaned)
    source_columns = extract_source_columns(all_statements)
    statements = filter_mode(all_statements, args.mode)

    transformed: List[Tuple[int, str]] = []
    for line, stmt in statements:
        out = transform_statement(stmt)
        if out:
            transformed.append((line, out))

    if args.mode == "data-only":
        transformed = sort_insert_statements_by_priority(transformed)

    tables_in_dump = collect_insert_tables(transformed)

    if not transformed:
        print("No se encontraron sentencias para ejecutar.")
        return 0

    print(f"Sentencias a ejecutar: {len(transformed)} (modo={args.mode})")

    conn = psycopg2.connect(
        host=args.host,
        port=args.port,
        dbname=args.db,
        user=args.user,
        password=args.password,
    )
    conn.autocommit = False

    ok = 0
    check_constraints: List[str] = []
    fk_validation_errors: List[Tuple[str, str, str]] = []
    try:
        with conn.cursor() as cur:
            cur.execute("SET standard_conforming_strings = on;")
            target_cols_cache: Dict[str, List[Dict[str, str]]] = {}
            skipped = 0
            dropped_fk_constraints: List[Tuple[str, str, str]] = []

            try:
                if args.mode == "data-only":
                    cur.execute("""
                        SELECT conname FROM pg_constraint
                        WHERE conrelid = '"Proveedor"'::regclass AND contype = 'c'
                    """)
                    check_constraints = [r[0] for r in cur.fetchall()]
                    if check_constraints:
                        for conname in check_constraints:
                            cur.execute(f'ALTER TABLE "Proveedor" DROP CONSTRAINT "{conname}"')
                        conn.commit()
                        print(f"CHECK constraints de Proveedor eliminados: {len(check_constraints)}")

                    existing = get_existing_base_tables(cur)
                    tables_presentes = [t for t in tables_in_dump if t in existing]
                    dropped_fk_constraints = collect_fk_constraints(cur, tables_presentes)
                    if dropped_fk_constraints:
                        drop_fk_constraints(cur, dropped_fk_constraints)
                        conn.commit()
                        print(f"FK constraints deshabilitadas temporalmente: {len(dropped_fk_constraints)}")

                if args.mode == "data-only" and args.truncate_first:
                    if tables_in_dump:
                        existing = get_existing_base_tables(cur)
                        tables_to_truncate = [t for t in tables_in_dump if t in existing]
                        if tables_to_truncate:
                            q_tables = ", ".join(f'"{t}"' for t in tables_to_truncate)
                            cur.execute(f"TRUNCATE TABLE {q_tables} RESTART IDENTITY CASCADE;")
                            conn.commit()
                            print(f"Tablas truncadas: {len(tables_to_truncate)}")

                pending: List[Tuple[int, str]] = list(transformed)
                max_rounds = 8 if args.mode == "data-only" else 1
                round_no = 0
                commit_tick = 0

                while pending and round_no < max_rounds:
                    round_no += 1
                    progressed = 0
                    next_pending: List[Tuple[int, str]] = []

                    for idx, (line, stmt) in enumerate(pending, start=1):
                        exec_stmt = stmt
                        if args.mode == "data-only":
                            table_name = extract_insert_table_name(stmt)
                            if table_name:
                                cur.execute(
                                    "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = %s",
                                    (table_name,),
                                )
                                if cur.fetchone() is None:
                                    skipped += 1
                                    continue

                            try:
                                exec_stmt = adapt_insert_to_existing_table(
                                    stmt,
                                    cur,
                                    source_columns,
                                    target_cols_cache,
                                    args.strict,
                                )
                            except RuntimeError as exc:
                                print(f"\nError en sentencia #{idx} (linea aprox. {line}):", file=sys.stderr)
                                print(str(exc), file=sys.stderr)
                                preview = stmt[:600].replace("\n", " ")
                                print(f"SQL: {preview}", file=sys.stderr)
                                return 2

                        if exec_stmt is None:
                            skipped += 1
                            continue

                        row_statements = [exec_stmt]
                        if args.mode == "data-only":
                            row_statements = explode_insert_rows(exec_stmt)

                        for row_stmt in row_statements:
                            cur.execute("SAVEPOINT import_sp")
                            try:
                                cur.execute(row_stmt)
                                cur.execute("RELEASE SAVEPOINT import_sp")
                                ok += 1
                                progressed += 1
                                commit_tick += 1
                            except Exception as exc:
                                cur.execute("ROLLBACK TO SAVEPOINT import_sp")
                                cur.execute("RELEASE SAVEPOINT import_sp")

                                pgcode = getattr(exc, "pgcode", None)
                                if args.mode == "data-only" and pgcode == "23503":
                                    next_pending.append((line, row_stmt))
                                    continue

                                print(f"\nError en sentencia #{idx} (linea aprox. {line}):", file=sys.stderr)
                                print(str(exc), file=sys.stderr)
                                preview = row_stmt[:600].replace("\n", " ")
                                print(f"SQL: {preview}", file=sys.stderr)
                                return 2

                            if commit_tick >= max(args.commit_every, 1):
                                conn.commit()
                                commit_tick = 0

                    conn.commit()
                    commit_tick = 0

                    if not next_pending:
                        pending = []
                        break

                    if progressed == 0:
                        if args.strict:
                            print(
                                f"\nError: {len(next_pending)} filas no pudieron insertarse por dependencias FK/datos faltantes.",
                                file=sys.stderr,
                            )
                            sample = next_pending[0][1][:600].replace("\n", " ")
                            print(f"SQL ejemplo: {sample}", file=sys.stderr)
                            return 2

                        skipped += len(next_pending)
                        print(
                            f"Aviso: {len(next_pending)} filas no pudieron insertarse por dependencias FK o datos faltantes.",
                            file=sys.stderr,
                        )
                        pending = []
                        break

                    print(
                        f"Progreso ronda {round_no}: ejecutadas={ok}, pendientes_por_fk={len(next_pending)}"
                    )
                    pending = next_pending

                conn.commit()

                if check_constraints and args.mode == "data-only":
                    conn.rollback()
                    cur.execute("ALTER TABLE \"Proveedor\" DROP CONSTRAINT IF EXISTS \"chk_clabe_format\"")
                    cur.execute("ALTER TABLE \"Proveedor\" DROP CONSTRAINT IF EXISTS \"chk_cuenta_format\"")
                    conn.commit()
                    print("CHECK constraints de Proveedor eliminados (lenientes)")

                if args.mode == "full":
                    recreate_foreign_keys(cur, conn)
            finally:
                if dropped_fk_constraints:
                    try:
                        conn.rollback()
                        restore_fk_constraints_not_valid(cur, dropped_fk_constraints)
                        conn.commit()
                        print(
                            f"FK constraints restauradas como NOT VALID: {len(dropped_fk_constraints)}"
                        )

                        fk_validation_errors = validate_fk_constraints_one_by_one(
                            cur,
                            conn,
                            dropped_fk_constraints,
                        )
                    except Exception as exc:
                        conn.rollback()
                        print(
                            f"Aviso: no se pudieron restaurar algunas FK constraints: {exc}",
                            file=sys.stderr,
                        )
    finally:
        conn.close()

    if args.mode == "data-only" and fk_validation_errors and args.keep_fk_validated:
        print(
            "Error: existen FK inconsistentes tras la importacion y se solicito mantenerlas validadas (--keep-fk-validated).",
            file=sys.stderr,
        )
        return 2

    if args.mode == "data-only":
        print(f"Importacion completada. Ejecutadas: {ok}. Omitidas: {skipped}")
    else:
        print(f"Importacion completada. Ejecutadas: {ok}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
