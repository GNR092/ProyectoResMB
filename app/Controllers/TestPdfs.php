<?php
namespace App\Controllers;

class TestPdfs extends \CodeIgniter\Controller
{
    public function index()
    {
        $model = new \App\Models\EventoArchivosModel();
        $archivos = $model->select('id_archivo, id_evento, nombre_archivo, fecha_subida')
            ->where("nombre_archivo LIKE '%.pdf'", null, false)
            ->orderBy('fecha_subida', 'ASC')
            ->findAll();
        
        echo "Total PDF files in database: " . count($archivos) . "\n\n";
        foreach ($archivos as $row) {
            echo "ID: {$row['id_archivo']} | Evento: {$row['id_evento']} | File: {$row['nombre_archivo']} | Date: {$row['fecha_subida']}\n";
            
            $fullPath = WRITEPATH . 'uploads/eventos/' . $row['nombre_archivo'];
            if (file_exists($fullPath)) {
                $analysis = \App\Libraries\PdfValidator::analyze($fullPath);
                $importCheck = \App\Libraries\PdfValidator::canImportWithFpdi($fullPath);
                echo "  Valid: " . ($analysis['isValid'] ? 'yes' : 'no') . " | Version: " . ($analysis['version'] ?? 'unknown') . " | Encrypted: " . ($analysis['isEncrypted'] ? 'yes' : 'no') . " | FPDI Compatible: " . ($analysis['isFpdiCompatible'] ? 'yes' : 'no') . "\n";
                echo "  FPDI Import: " . ($importCheck['success'] ? 'OK (' . $importCheck['pages'] . ' pages)' : 'FAIL - ' . $importCheck['message']) . "\n";
            } else {
                echo "  FILE MISSING ON DISK!\n";
            }
            echo "\n";
        }
        exit;
    }
}