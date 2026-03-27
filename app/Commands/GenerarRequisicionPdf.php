<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class GenerarRequisicionPdf extends BaseCommand
{
    protected $group = 'Generacion';
    protected $name = 'generar:requisicion-pdf';
    protected $description = 'Genera PDFs de requisicion que no existan (excluye canceladas)';
    protected $usage = 'generar:requisicion-pdf';
    protected $arguments = [];
    protected $options = [];

    public function run(array $params)
    {
        $solicitudModel = new \App\Models\SolicitudModel();

        $solicitudes = $solicitudModel
            ->where('Estado !=', 'Cancelada')
            ->findAll();

        $folderPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'pdf_solicitudes';

        if (!is_dir($folderPath)) {
            mkdir($folderPath, 0777, true);
        }

        $generados = 0;
        $errores = 0;
        $yaExistian = 0;

        CLI::write('Total solicitudes: ' . count($solicitudes));

        foreach ($solicitudes as $sol) {
            $fileName = 'Requisicion-' . $sol['No_Folio'] . '.pdf';
            $filePath = $folderPath . DIRECTORY_SEPARATOR . $fileName;

            if (!file_exists($filePath)) {
                try {
                    $controller = new \App\Controllers\GenerarPDF();
                    $controller->generarYGuardarRequisicion((int) $sol['ID_Solicitud'], 0, 1);
                    $generados++;
                    CLI::write('Generado: ' . $sol['No_Folio'], 'green');
                } catch (\Exception $e) {
                    $errores++;
                    CLI::write("Error {$sol['No_Folio']}: " . $e->getMessage(), 'red');
                }
            } else {
                $yaExistian++;
            }
        }

        CLI::write('=============================', 'white');
        CLI::write('PDFs generados: ' . $generados, 'green');
        CLI::write('Ya existian: ' . $yaExistian, 'yellow');
        CLI::write('Errores: ' . $errores, $errores > 0 ? 'red' : 'green');
    }
}
