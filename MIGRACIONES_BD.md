# Spec — Pantalla "Traspasos y Migraciones de Presupuesto"

Especificación técnica para implementar, dentro de la UI de CodeIgniter 4, una opción que permita realizar desde pantalla los traspasos/migraciones que hasta ahora se ejecutaban manualmente por SQL contra MariaDB (`compras_db`).

**Contexto / caso validado:** migración del Hotel Ciudad Blanca ejecutada el 2026-08-05 vía procedimiento almacenado (`migrar_hotel`, en `/tmp/opencode/migracion_hotel/migracion.sql`): Place 29 → UO 65 → 18 partidas (374-391) → 89 productos (IDs 919-1007), presupuesto ago-dic 2026 (85 filas, 499,597.14), 3 departamentos movidos, `PresupuestoAnual` recalculado. Este documento describe cómo exponer esas operaciones en pantalla.

---

## 1. Objetivo

- **Operación A — Copiar productos de una partida a otra:** permite clonar productos de una partida (`GrupoPresupuestal`) origen hacia una partida destino.
- **Operación B — Migración de estructura completa:** replica el flujo del Hotel Ciudad Blanca: crear un `Place` + `UnidadOperativa` nuevos, clonar partidas, copiar productos, trasladar presupuesto por rango de meses, mover departamentos (opcional) y recalcular `PresupuestoAnual`.

Ambas operaciones deben ejecutarse en **transacción con aserciones** (rollback automático si los conteos no cuadran), con **dry-run previo obligatorio**, **backup previo** y **registro en bitácora**.

---

## 2. Acceso

**Solo los roles `Administración` y `Contaduría` pueden ver la opción y consumir sus endpoints.**

> Nota: `Direccion` es un rol distinto a `Contaduría`. `Direccion` y el rol `Presupuestos` quedan **excluidos** explícitamente.

Esto aplica en **dos capas**:

1. **Menú (ocultar):**
   - En `app/Config/MenuOptions.php` agregar el ítem `MigracionesDatos` dentro del bloque de la sección Presupuestos (`TituloPresupuestos`).
   - En `app/Controllers/Home.php`:
     - `Administración` ya recibe todo automáticamente (`array_keys($opcionesDisponibles)`); no se toca.
     - Agregar `'MigracionesDatos'` al array `$permisosPorDepto['Contaduría']`.
     - Agregar `'MigracionesDatos' => 'TituloPresupuestos'` al `$mapaTitulos`.
     - **No** agregar la clave en `Direccion`, `Presupuestos`, `Compras`, `Tesoreria` ni `default`.

2. **Backend (obligatorio):** el controlador debe validar el rol del usuario antes de **cualquier** endpoint (render y ejecución), leyendo el departamento del usuario desde sesión (`session('departamento_usuario')`) o resolviendo `ID_Dpto` → `Departamentos.Nombre`. Respuesta `403` si el rol no es `Administración` ni `Contaduría`. **Ocultar el menú no es suficiente.**

---

## 3. Ubicación del menú

- Ítem de **primer nivel dentro de la sección "Presupuestos"** del menú lateral (junto a `UnidadOperativa`, `GrupoPresupuestal`, `PresupuestoMensual`, `GastoManual`).
- **No** es un submenú de ninguna pantalla.
- Label propuesto: **"Traspasos y Migraciones"**.
- Icono: reutilizar uno existente en `public/icons/icons.svg` (p. ej. `#CambiosPresupuesto`) o agregar uno nuevo.

---

## 4. Piezas a crear

| Archivo | Propósito |
|---|---|
| `app/Config/MenuOptions.php` | Ítem `MigracionesDatos` en la sección Presupuestos |
| `app/Controllers/Home.php` | Permiso solo `Contaduría` (+ Admin heredado) y entrada en `$mapaTitulos` |
| `app/Controllers/Modales.php` | `case 'MigracionesDatos':` → render de la vista (patrón `PresupuestoMensual`) |
| `app/Views/modales/control/Migraciones.php` | Pantalla UI (Tailwind, patrón `app/Views/modales/crud_places.php`) |
| `app/Controllers/MigracionesController.php` | Endpoints JSON (extiende `ResourceController`) |
| `app/Services/MigracionesService.php` | Lógica transaccional + aserciones + recálculo anual |
| `app/Config/Routes.php` | Rutas dentro del grupo `$routes->group('/', ['filter' => ['auth', 'mantenimiento']], ...)` |
| `public/js/migraciones.js` | Lógica frontend (selectores en cascada, preview, ejecutar) |

### Rutas propuestas

```
GET  modales/migraciones                          -> render pantalla
POST api/migraciones/copiar-productos/preview     -> dry-run (no escribe)
POST api/migraciones/copiar-productos/ejecutar    -> ejecuta transacción
POST api/migraciones/migrar-estructura/preview    -> dry-run (no escribe)
POST api/migraciones/migrar-estructura/ejecutar   -> ejecuta transacción
```

---

## 5. Operación A — Copiar productos de una partida a otra

### UI
- Selectores en cascada (igual que `PresupuestoMensual`): `Razón Social → Place → UnidadOperativa → Partida` (origen) y los mismos para **destino**.
- Tabla de **preview** con los productos que se copiarán (nombre, grupo origen/destino).
- Botón **"Ejecutar"** deshabilitado hasta pasar el preview; confirmación obligatoria con texto de riesgo.
- Resultado: conteos de productos copiados + checksum del origen (debe permanecer intacto).

### Lógica (generalización de la FASE 2 del `migracion.sql` de hoy)

```sql
START TRANSACTION;

INSERT INTO Catalogo_Productos (ID_RazonSocial, id_segmento, ID_Place, ID_Dpto, ID_GrupoPresupuestal, Nombre, created_at, updated_at)
SELECT cp.ID_RazonSocial, cp.id_segmento, cp.ID_Place, cp.ID_Dpto, <GRUPO_DESTINO>, cp.Nombre, NOW(), NOW()
FROM Catalogo_Productos cp
WHERE cp.ID_GrupoPresupuestal = <GRUPO_ORIGEN>;

-- ASERCIÓN: filas copiadas == conteo del preview; si no, ROLLBACK
IF ROW_COUNT() <> <ESPERADO> THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'conteo productos no coincide'; END IF;

COMMIT;
```

### Reglas / conflictos
- Si la partida destino ya tiene productos, **error** (política por defecto) o confirmación explícita de mezcla.
- El preview y la ejecución deben correr sobre los **mismos IDs** (evitar TOCTOU): validar que origen/destino existan y que no cambiaron entre preview y ejecución.
- Los productos se copian tal cual (`ID_Place`/`ID_Dpto` heredados del origen); si el destino está en otra UO/Place, se actualizan esos campos al destino.

---

## 6. Operación B — Migración de estructura completa

### Wizard de 3 pasos

**Paso 1 — Configuración:**
- Origen: `Place` + `UnidadOperativa` a migrar.
- Destino: `Razón Social` de llegada, `Segmento`, `Nombre_Corto`/`Nombre_Completo` del nuevo Place, nombre de la nueva `UnidadOperativa`.
- Rango de meses a trasladar (`Anio` + `Mes Desde`/`Mes Hasta`). Ej. hotel: 2026, ago–dic.
- Checkbox **"Mover departamentos"** (migra los `Departamentos` del origen hacia el nuevo Place/UO).
- Advertencia si hay requisiciones abiertas referenciando la UO origen (no se tocan; se informan).

**Paso 2 — Dry-run (preview, sin escribir):**
- Conteos y montos: partidas a clonar, productos a copiar, filas y monto de presupuesto en el rango, departamentos a mover.
- Validaciones: nombres de partida **únicos** dentro de la UO origen (necesario para construir el mapa), Place/UO destino no existente (nombre libre), etc.

**Paso 3 — Confirmar y ejecutar:** transacción con aserciones (mismo algoritmo del `migracion.sql`).

### Algoritmo (referencia validada 2026-08-05)

```sql
START TRANSACTION;
-- 1) Place nuevo + UO nueva (usar LAST_INSERT_ID)
INSERT INTO Places (Nombre_Corto, Nombre_Completo, ID_RazonSocial, id_segmento, activo) VALUES (...);
INSERT INTO UnidadOperativa (Nombre, ID_Place, activo) VALUES (..., <NUEVO_PLACE>, 1);

-- 2) Clonar partidas y construir mapa viejo->nuevo por Nombre
INSERT INTO GrupoPresupuestal (Nombre, Descripcion, ID_UnidadOperativa, activo, es_manual)
SELECT Nombre, Descripcion, <NUEVA_UO>, activo, es_manual
FROM GrupoPresupuestal WHERE ID_UnidadOperativa = <UO_ORIGEN>;
-- tmp_map: viejo -> nuevo (JOIN por Nombre dentro de la nueva UO)

-- 3) Copiar productos vía tmp_map
INSERT INTO Catalogo_Productos (ID_RazonSocial, id_segmento, ID_Place, ID_Dpto, ID_GrupoPresupuestal, Nombre, created_at, updated_at)
SELECT <RS_DEST>, <SEG>, <NUEVO_PLACE>, <NUEVA_UO>, m.nuevo, cp.Nombre, NOW(), NOW()
FROM Catalogo_Productos cp JOIN tmp_map m ON m.viejo = cp.ID_GrupoPresupuestal
WHERE cp.ID_Dpto = <UO_ORIGEN>;

-- 4) Trasladar presupuesto del rango de meses (INSERT en nueva UO + DELETE en origen)
INSERT INTO PresupuestoMensual (ID_UnidadOperativa, ID_GrupoPresupuestal, Anio, Mes, Monto_Asignado, Monto_Comprometido, Monto_Ejecutado)
SELECT <NUEVA_UO>, m.nuevo, pm.Anio, pm.Mes, pm.Monto_Asignado, pm.Monto_Comprometido, pm.Monto_Ejecutado
FROM PresupuestoMensual pm JOIN tmp_map m ON m.viejo = pm.ID_GrupoPresupuestal
WHERE pm.ID_UnidadOperativa = <UO_ORIGEN> AND pm.Anio = <ANIO> AND pm.Mes BETWEEN <DESDE> AND <HASTA>;
DELETE pm FROM PresupuestoMensual pm JOIN tmp_map m ON m.viejo = pm.ID_GrupoPresupuestal
WHERE pm.ID_UnidadOperativa = <UO_ORIGEN> AND pm.Anio = <ANIO> AND pm.Mes BETWEEN <DESDE> AND <HASTA>;

-- 5) Mover departamentos (si se marcó) y recalcular PresupuestoAnual
UPDATE Departamentos SET ID_UnidadOperativa = <NUEVA_UO>, ID_Place = <NUEVO_PLACE> WHERE ID_Place = <PLACE_ORIGEN> AND ID_UnidadOperativa = <UO_ORIGEN>;
UPDATE Departamentos SET ID_Place = <NUEVO_PLACE> WHERE ID_Place = <PLACE_ORIGEN> AND ID_UnidadOperativa IS NULL;

-- 6) Recalcular PresupuestoAnual (ver sección 7)
-- 7) ASERCIONES: partidas clonadas, mapeo, productos, presupuesto insertado/borrado, deptos == conteos del preview; si no, ROLLBACK
COMMIT;
```

### Restricciones documentadas
- Nombres de partida **únicos** dentro de la UO origen (el mapa se construye por `Nombre`).
- No se tocan `Solicitud` ni `Solicitud_Producto`/`Solicitud_Servicios`: las requisiciones abiertas quedan en la cadena origen (decisión del caso hotel).
- Si el destino ya tiene UO con el mismo nombre o Place con el mismo nombre corto, error.

---

## 7. Recalculo de `PresupuestoAnual`

`PresupuestoAnual` es una tabla **derivada** (mismo cálculo que `ejecutarPresupuestoMasivo` en `PresupuestoApiController.php:646-652`). Tras cualquier operación A o B que mueva presupuesto se debe recalcular para las Razones Sociales afectadas:

```sql
UPDATE PresupuestoAnual pa
SET Monto = (SELECT ROUND(SUM(pm.Monto_Asignado), 2)
             FROM PresupuestoMensual pm
             JOIN UnidadOperativa u ON u.ID_UnidadOperativa = pm.ID_UnidadOperativa
             JOIN Places p ON p.ID_Place = u.ID_Place
             JOIN GrupoPresupuestal gp ON gp.ID_GrupoPresupuestal = pm.ID_GrupoPresupuestal
             WHERE pm.Anio = pa.Anio AND p.ID_RazonSocial = pa.ID_RazonSocial AND gp.activo = true)
WHERE pa.Anio = <ANIO> AND pa.ID_RazonSocial IN (<RS_ORIGEN>, <RS_DESTINO>);
```

Nota: `syncPresupuestoMensualSequenceIfNeeded` (`PresupuestoApiController.php:485-498`) es **solo PostgreSQL**; no aplicar en MariaDB.

---

## 8. Protocolo de seguridad transversal

1. **Backup previo obligatorio:**
   - Toggle **"Backup automático"**: si el entorno permite `exec()`/`shell_exec()`, el servicio ejecuta `mysqldump --single-transaction --routines --triggers --events --databases compras_db` antes de escribir y guarda el archivo con timestamp.
   - Si el entorno **no** lo permite, el botón de ejecutar exige **confirmación escrita de backup manual** (campo de texto) y la ruta del backup se registra en bitácora.
   - Referencia de backup correcto: `/tmp/opencode/migracion_hotel/compras_db_full_20260805_130119.sql` (pre-migración).
2. **Dry-run obligatorio** en ambas operaciones antes de ejecutar; ejecutar sin preview = error.
3. **Transacción + aserciones:** todas las operaciones en `START TRANSACTION` con `EXIT HANDLER`/`SIGNAL` → `ROLLBACK` si los conteos no coinciden con el preview.
4. **Checksums:** MD5 del conjunto origen antes y después; deben coincidir (patrón `snapshot_pre.txt` de hoy).
5. **Bitácora:** registrar en `BitacoraService` usuario, operación, IDs viejos/nuevos (Place/UO/partidas/productos), conteos y resultado.
6. **Validación:** IDs numéricos existentes en BD, CSRF, y re-chequeo de rol en cada endpoint.

---

## 9. Restauración

- Restaurar toda la BD desde backup:
  ```bash
  docker exec -i codeigniter_db_mariadb mysql -uci_user -pci_password_segura < compras_db_full_<TIMESTAMP>.sql
  ```
- El backup (automático o manual) se conserva hasta confirmar la operación en pantalla; su ruta queda en bitácora para poder revertir.
- Restauración = punto de restauración total (el dump incluye rutinas/triggers/eventos). Aplica a operación A y B por igual.

---

## 10. Verificación y criterios de aceptación

Casos de prueba:

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | Copiar productos de una partida a otra (misma UO) | Conteos correctos, origen intacto |
| 2 | Copiar productos entre UOs distintas | Campos `ID_Place`/`ID_Dpto` = destino |
| 3 | Migración completa con presupuesto y deptos (caso hotel) | Place/UO/partidas/productos/presupuesto/deptos nuevos; anual recalculado |
| 4 | Migración completa sin presupuesto | PresupuestoMensual no trasladado; anual solo destino afectado |
| 5 | Nombres de partida duplicados en UO origen | Error claro en preview |
| 6 | Destino ya poblado (A) o nombre de Place/UO en uso (B) | Error, no escribe |
| 7 | Usuario rol `Direccion` / `Presupuestos` / `Compras` | Opción oculta en menú **y** endpoint devuelve 403 |
| 8 | Usuario rol `Contaduría` | Opción visible y operativa |
| 9 | Backups | Existe archivo (auto) o ack manual obligatorio antes de ejecutar |
| 10 | Rollback | Si una aserción falla, no quedan datos a medias |

Checklist de aceptación:
- [ ] Menú visible solo para `Administración` y `Contaduría`, dentro de la sección Presupuestos, como ítem de primer nivel.
- [ ] Endpoints rechazan roles no autorizados (403), incluyendo `Direccion` y `Presupuestos`.
- [ ] Dry-run no escribe nada; ejecutar requiere preview y backup (auto o ack).
- [ ] Transacción con aserciones: cualquier desajuste hace rollback total.
- [ ] `PresupuestoAnual` recalculado para RS origen y destino; verificado contra `SUM(Monto_Asignado)` de la app.
- [ ] Bitácora registra cada operación con IDs y conteos.

---

## 11. Referencias de código actual

- `app/Config/MenuOptions.php` — sección `TituloPresupuestos` (líneas 145-177) donde insertar el ítem.
- `app/Controllers/Home.php` — `$permisosPorDepto` (líneas 22-167) y `$mapaTitulos` (líneas 231-271).
- `app/Controllers/Modales.php` — `mostrar()`; patrón `case 'PresupuestoMensual'` (líneas 584-592) y `case 'crud_places'` para el render de la vista.
- `app/Views/modales/crud_places.php` — patrón de pantalla (listas, formularios, Tailwind).
- `app/Controllers/PresupuestoApiController.php` — `ejecutarPresupuestoMasivo` (646-652, recálculo anual) y `syncPresupuestoMensualSequenceIfNeeded` (485-498, solo Postgres).
- `app/Libraries/Rest.php` — `buscarProductosCatalogo` (1884-1900) acotada por lugar/UO; `resolvePlaceId` usa `$idPlace` primero (1826-1852).
- `app/Libraries/BitacoraService.php` — registro de eventos.
- `/tmp/opencode/migracion_hotel/migracion.sql` — procedimiento validado (base de la Operación B).
- `/tmp/opencode/migracion_hotel/snapshot_pre.txt` — patrón de checksums pre/post.

### Notas de contexto
- Las pantallas de presupuesto leen los datos **en vivo** (sin caché): los cambios se reflejan sin pasos extra.
- `Modales::editarPlace` clona UO/partidas al cambiar segmento pero **no** copia productos ni presupuesto; esta pantalla cubre exactamente esa carencia.
- Los productos del catálogo se acotan por lugar al buscar desde el frontend (`id_place`), por lo que no hay cruce entre cadenas con el mismo nombre de UO ("Gastos" en RS 2 y RS 8).
