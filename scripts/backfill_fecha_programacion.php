#!/usr/bin/env php
<?php
/**
 * Script one-off seguro para backfill FechaProgramacion en producción.
 * Compatible PostgreSQL + MariaDB (Query Builder), 3 días hábiles (Lun-Vie) desde Fecha.
 * Seguro: dry-run por defecto, solo UPDATE donde FechaProgramacion IS NULL, transacción, idempotente.
 *
 * Uso producción:
 *   php scripts/backfill_fecha_programacion.php              // dry-run preview
 *   php scripts/backfill_fecha_programacion.php --execute    // escritura real
 *   php scripts/backfill_fecha_programacion.php --execute --limit=100
 *   php scripts/backfill_fecha_programacion.php --execute --force  // sin confirmación
 */

// Bootstrap CodeIgniter
define('FCPATH', __DIR__ . '/../public/');
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
use Config\Paths;
$paths = new Paths();
require $paths->systemDirectory . '/Boot.php';
use CodeIgniter\Boot;
Boot::bootSpark($paths); // inicializa autoloader y config

use Config\Database;
use App\Libraries\Status;

$options = getopt('', ['execute', 'limit:', 'force', 'help']);
$isExecute = isset($options['execute']);
$limit = isset($options['limit']) ? (int)$options['limit'] : 0;
$force = isset($options['force']);
if (isset($options['help'])) {
    echo "Uso: php scripts/backfill_fecha_programacion.php [--execute] [--limit=N] [--force]\n";
    echo "  Sin --execute: dry-run (preview sin escritura)\n";
    exit(0);
}

$db = Database::connect();

// Verificar columna existe
if (!$db->fieldExists('FechaProgramacion', 'OrdenCompra')) {
    fwrite(STDERR, "ERROR: OrdenCompra.FechaProgramacion NO existe. Ejecuta: php spark migrate\n");
    exit(1);
}

$estados = [
    Status::Programada, Status::Por_Pagar, Status::Pagada, Status::En_Proceso_Pago,
    Status::Espera_Programacion,
    'Programada', 'Por Pagar', 'Pagada', 'En Proceso de Pago', 'En_Proceso_Pago',
    'Espera_Programacion',
];
$estados = array_values(array_unique($estados));

function addBusinessDays($fechaStr, $days) {
    try {
        $tz = new DateTimeZone('America/Mexico_City');
        $dt = new DateTime($fechaStr, $tz);
        $dt->setTimezone($tz);
        $added = 0;
        while ($added < $days) {
            $dt->modify('+1 day');
            if ((int)$dt->format('N') < 6) $added++;
        }
        return $dt;
    } catch (Throwable $e) {
        return null;
    }
}

$fpNull = $db->protectIdentifiers('OrdenCompra.FechaProgramacion') . ' IS NULL';
$fechaNotNull = $db->protectIdentifiers('OrdenCompra.Fecha') . ' IS NOT NULL';

$total = (int) $db->table('OrdenCompra')
    ->whereIn('Estado', $estados)
    ->where($fpNull, null, false)
    ->where($fechaNotNull, null, false)
    ->countAllResults(false);

$preview = $db->table('OrdenCompra')
    ->select('ID_OrdenCompra, Estado, Fecha, FechaProgramacion')
    ->whereIn('Estado', $estados)
    ->where($fpNull, null, false)
    ->where($fechaNotNull, null, false)
    ->orderBy('ID_OrdenCompra', 'ASC')
    ->limit(5)->get()->getResultArray();

echo "========================================\n";
echo " Backfill FechaProgramacion = Fecha + 3 días hábiles\n";
echo "========================================\n";
echo "Estados: " . implode(', ', $estados) . "\n";
echo "Total a actualizar: $total\n";
echo "Zona: America/Mexico_City (Lun-Vie)\n";
echo "Modo: " . ($isExecute ? "EXECUTE" : "DRY-RUN") . ($limit ? " Limit:$limit" : "") . "\n";
if (!empty($preview)) {
    echo "Preview 5 filas:\n";
    foreach ($preview as $row) {
        $calc = addBusinessDays($row['Fecha'], 3);
        $calcStr = $calc ? $calc->format('Y-m-d H:i:s') : 'ERROR';
        echo "  ID {$row['ID_OrdenCompra']} | {$row['Estado']} | {$row['Fecha']} => $calcStr\n";
    }
}
if (!$isExecute) {
    echo "--- DRY-RUN: Sin escritura. Usa --execute para aplicar ---\n";
    exit(0);
}
if ($total === 0) {
    echo "Nada que actualizar.\n";
    exit(0);
}
if (!$force) {
    echo "¿Confirmas actualizar $total filas? (s/N): ";
    $h = fopen("php://stdin", "r");
    $ans = trim(fgets($h));
    if (strtolower($ans) !== 's' && strtolower($ans) !== 'y') {
        echo "Cancelado.\n";
        exit(0);
    }
}

$builder = $db->table('OrdenCompra')
    ->select('ID_OrdenCompra, Fecha')
    ->whereIn('Estado', $estados)
    ->where($fpNull, null, false)
    ->where($fechaNotNull, null, false)
    ->orderBy('ID_OrdenCompra', 'ASC');
if ($limit > 0) $builder->limit($limit);
$rows = $builder->get()->getResultArray();

echo "Transacción iniciada...\n";
$db->transStart();
$updated = 0; $errors = 0;
foreach ($rows as $row) {
    $id = $row['ID_OrdenCompra'];
    $dt = addBusinessDays($row['Fecha'], 3);
    if (!$dt) { echo " ID $id: Fecha inválida {$row['Fecha']} skip\n"; $errors++; continue; }
    $nueva = $dt->format('Y-m-d H:i:s');
    try {
        $db->table('OrdenCompra')->where('ID_OrdenCompra', $id)->where($fpNull, null, false)->update(['FechaProgramacion' => $nueva]);
        if ($db->affectedRows() > 0) {
            $updated++;
            if ($updated <= 5 || $updated % 100 === 0) echo "  Updated $id: {$row['Fecha']} => $nueva\n";
        }
    } catch (Throwable $e) {
        echo "  Error ID $id: " . $e->getMessage() . "\n";
        $errors++;
    }
}
$db->transComplete();
if ($db->transStatus() === false) {
    echo "Transacción falló, rollback.\n";
    exit(1);
}
echo "========================================\n";
echo "Completado. Actualizadas: $updated / $total | Errores: $errors\n";
echo "Verifica: SELECT Estado, Fecha, FechaProgramacion FROM \"OrdenCompra\" WHERE \"Estado\" IN ('Programada','Por Pagar','Pagada') LIMIT 5;\n";
