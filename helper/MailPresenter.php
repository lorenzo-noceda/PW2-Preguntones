<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailPresenter
{
    private $mailer;

    public function __construct($host, $email, $password, $name, $port = 587)
    {
        $this->mailer = new PHPMailer(true);

        // Configurar el servidor de correo
        $this->mailer->isSMTP();
        $this->mailer->Host = $host;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $email;
        $this->mailer->Password = $password;
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Usar TLS
        $this->mailer->Port = $port;
        $this->mailer->CharSet = 'UTF-8';

        // Configuración predeterminada
        $this->mailer->setFrom($email, $name); // Cambia el nombre por defecto si lo deseas
    }

    public function setRecipient($email, $name = '')
    {
        // Agrega el destinatario
        $this->mailer->addAddress($email, $name);
    }

    public function setSubject($subject)
    {
        // Establecer el asunto del correo
        $this->mailer->Subject = mb_encode_mimeheader($subject, 'UTF-8');
    }

    public function setBody($body, $isHtml = true)
    {
        // Establecer el cuerpo del correo (en formato HTML o texto plano)
        if ($isHtml) {
            $this->mailer->isHTML(true);
        }
        $this->mailer->Body = $body;
    }

    public function sendEmail()
    {
        try {
            // Enviar el correo
            return $this->mailer->send();
        } catch (Exception $e) {
            // Manejar errores en el envío del correo
            throw new Exception("El correo no pudo ser enviado: {$this->mailer->ErrorInfo}");
        }
    }
}