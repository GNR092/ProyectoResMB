<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\MBSMail;
use App\Libraries\FPath;

class SendTestEmail extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'MBS';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'email:test';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Envía un correo de prueba para verificar la configuración.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'email:test';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        // Para ejecutar usar php spark email:test
        try {
            CLI::write('Intentando enviar correo de prueba...', 'yellow');

            $mail = new MBSMail();

            $to = 'mail.example.com'; // Reemplaza con la dirección de correo del destinatario
            $subject = 'Prueba de envío desde Comando Spark'; // Reemplaza con el asunto del correo
            $message = '<h1>Hola desde CodeIgniter Spark!</h1><p>Este es un correo de prueba enviado usando un comando de Spark.</p>'; // Reemplaza con el cuerpo del correo en formato HTML

            $options = [
                'fromName' => 'MBS Project', // Reemplaza con el nombre del remitente
                //'attachments' => [FPath::FCOTIZACION.'2025-10-02/cotizacion_1_2025-10-02_06-26-17_0.png'], // Reemplaza con la ruta de los archivos adjuntos
            ];

            if ($mail->send_email($to, $subject, $message, $options)) {
                CLI::write('Correo enviado correctamente.', 'green');
            } else {
                CLI::error('Error al enviar el correo. Revisa el archivo de logs para más detalles.');
            }
        } catch (\Throwable $e) {
            CLI::error('Ocurrió un error inesperado: ' . $e->getMessage());
            log_message('error', '[COMMAND] ' . $e->getTraceAsString());
        }
    }
}