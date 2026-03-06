<?php
$conn = pg_connect("host=localhost dbname=MBSPCompras user=mbuser password=r00t2026 port=5432");
if (!$conn) die("Connection failed");

// 1. Drop existing FK (if exists)
pg_query($conn, "ALTER TABLE \"BancoDpto\" DROP CONSTRAINT IF EXISTS \"bancodpto_dpto_fk\"");

// 2. Rename column back to ID_RazonSocial
$res1 = pg_query($conn, "ALTER TABLE \"BancoDpto\" RENAME COLUMN \"ID_Dpto\" TO \"ID_RazonSocial\"");
if (!$res1) {
    echo "Error renaming column: " . pg_last_error($conn) . "\n";
} else {
    echo "Column renamed to ID_RazonSocial successfully.\n";
}

// 3. Add FK back to Razon_Social
$res2 = pg_query($conn, "ALTER TABLE \"BancoDpto\" ADD CONSTRAINT \"bancodpto_razonsocial_fk\" FOREIGN KEY (\"ID_RazonSocial\") REFERENCES \"Razon_Social\"(\"ID_RazonSocial\") ON DELETE CASCADE ON UPDATE CASCADE");
if (!$res2) {
    echo "Error adding constraint: " . pg_last_error($conn) . "\n";
} else {
    echo "Constraint to Razon_Social added successfully.\n";
}
