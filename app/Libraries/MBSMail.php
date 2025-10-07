<?php

namespace App\Libraries;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MBSMail
{
    protected $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->configure_mailer();
    }

    /**
     * Configura el objeto PHPMailer con los ajustes del servidor SMTP.
     * Lee la configuración desde las variables de entorno.
     */
    private function configure_mailer()
    {
        $this->mail->isSMTP();
        $this->mail->Host = getenv('EMAIL_HOST');
        $this->mail->SMTPAuth = true;
        $this->mail->Username = getenv('EMAIL_USERNAME');
        $this->mail->Password = getenv('EMAIL_PASSWORD');
        $this->mail->SMTPSecure = getenv('EMAIL_ENCRYPTION') ?: PHPMailer::ENCRYPTION_SMTPS;
        $this->mail->Port = getenv('EMAIL_PORT') ?: 465;
        $this->mail->CharSet = 'UTF-8';
    }

    /**
     * Envía un correo electrónico.
     *
     * @param string $to      La dirección de correo del destinatario.
     * @param string $subject El asunto del correo.
     * @param string $message El cuerpo del correo en formato HTML.
     * @param array  $options Opciones adicionales como 'fromName', 'attachments'.
     * @return bool           Devuelve true si el correo se envió correctamente, false en caso contrario.
     */
    public function send_email($to, $subject, $message, $options = [])
    {
        try {
            // Remitente
            $from_email = getenv('EMAIL_FROM_ADDRESS');
            $from_name = isset($options['fromName']) ? $options['fromName'] : getenv('EMAIL_FROM_NAME');
            $this->mail->setFrom($from_email, $from_name);

            // Destinatario
            $this->mail->addAddress($to);

            // Contenido
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $message;
            $this->mail->AltBody = strip_tags($message);

            // Archivos adjuntos
            if (isset($options['attachments']) && is_array($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    $this->mail->addAttachment($attachment);
                }
            }

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            log_message('error', 'Error al enviar el correo: ' . $this->mail->ErrorInfo);
            return false;
        }
    }
}