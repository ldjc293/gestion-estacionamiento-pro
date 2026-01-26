<?php
/**
 * Mail Helper Functions
 */

require_once __DIR__ . '/../../config/constants.php';

class MailHelper
{
    /**
     * Send password reset code email
     *
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $code Verification code
     * @return bool True if email was sent successfully, false otherwise
     */
    public static function sendPasswordResetCode(string $email, string $name, string $code): bool
    {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->Port = MAIL_PORT;
            $mail->SMTPSecure = MAIL_ENCRYPTION;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            
            // Recipients
            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($email, $name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Código de recuperación de contraseña';
            
            // Email body
            $mail->Body = self::getPasswordResetTemplate($name, $code);
            $mail->AltBody = "Hola $name,\n\nTu código de recuperación de contraseña es: $code\n\nEste código expirará en " . PASSWORD_RESET_CODE_EXPIRATION . " minutos.\n\nSi no solicitaste este código, por favor ignora este mensaje.\n\nSaludos,\n" . ESTACIONAMIENTO_NOMBRE;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            writeLog("Error al enviar email de recuperación a $email: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Send password changed notification email
     *
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $ip IP address from which the password was changed
     * @return bool True if email was sent successfully, false otherwise
     */
    public static function sendPasswordChanged(string $email, string $name, string $ip): bool
    {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->Port = MAIL_PORT;
            $mail->SMTPSecure = MAIL_ENCRYPTION;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            
            // Recipients
            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($email, $name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Tu contraseña ha sido cambiada';
            
            // Email body
            $mail->Body = self::getPasswordChangedTemplate($name, $ip);
            $mail->AltBody = "Hola $name,\n\nTe informamos que tu contraseña ha sido cambiada exitosamente.\n\nSi no realizaste este cambio, por favor contacta al administrador inmediatamente.\n\nDetalles del cambio:\n- IP: $ip\n- Fecha y hora: " . date('d/m/Y H:i:s') . "\n\nSaludos,\n" . ESTACIONAMIENTO_NOMBRE;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            writeLog("Error al enviar email de cambio de contraseña a $email: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Send payment approved notification email
     *
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $receiptNumber Receipt number
     * @param float $amount Amount paid
     * @param array $months Months paid
     * @return bool True if email was sent successfully, false otherwise
     */
    public static function sendPaymentApproved(string $email, string $name, string $receiptNumber, float $amount, array $months): bool
    {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->Port = MAIL_PORT;
            $mail->SMTPSecure = MAIL_ENCRYPTION;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            
            // Recipients
            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($email, $name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Pago Aprobado - ' . ESTACIONAMIENTO_NOMBRE;
            
            // Email body
            $mail->Body = self::getPaymentApprovedTemplate($name, $receiptNumber, $amount, $months);
            $mail->AltBody = "Hola $name,\n\nTu pago ha sido aprobado exitosamente.\n\nRecibo: $receiptNumber\nMonto: " . formatUSD($amount) . "\n\nGracias por tu pago.\n\nSaludos,\n" . ESTACIONAMIENTO_NOMBRE;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            writeLog("Error al enviar email de aprobación a $email: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Send payment rejected notification email
     *
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $receiptNumber Receipt number (if any)
     * @param float $amount Amount
     * @param string $reason Rejection reason
     * @return bool True if email was sent successfully, false otherwise
     */
    public static function sendPaymentRejected(string $email, string $name, string $receiptNumber, float $amount, string $reason): bool
    {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->Port = MAIL_PORT;
            $mail->SMTPSecure = MAIL_ENCRYPTION;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            
            // Recipients
            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($email, $name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Pago Rechazado - ' . ESTACIONAMIENTO_NOMBRE;
            
            // Email body
            $mail->Body = self::getPaymentRejectedTemplate($name, $receiptNumber, $amount, $reason);
            $mail->AltBody = "Hola $name,\n\nTu pago ha sido rechazado.\n\nRecibo: $receiptNumber\nMonto: " . formatUSD($amount) . "\nMotivo: $reason\n\nPor favor, verifica la información y vuelve a intentarlo.\n\nSaludos,\n" . ESTACIONAMIENTO_NOMBRE;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            writeLog("Error al enviar email de rechazo a $email: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get password reset email template
     *
     * @param string $name Recipient name
     * @param string $code Verification code
     * @return string HTML email body
     */
    private static function getPasswordResetTemplate(string $name, string $code): string
    {
        $expirationMinutes = PASSWORD_RESET_CODE_EXPIRATION;
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Recuperación de Contraseña</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: bold; color: #2c3e50; }
                .content { background-color: #f9f9f9; padding: 30px; border-radius: 5px; }
                .code { font-size: 32px; font-weight: bold; text-align: center; padding: 20px; background-color: #e8f4f8; border-radius: 5px; margin: 20px 0; letter-spacing: 5px; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>" . ESTACIONAMIENTO_NOMBRE . "</div>
                </div>
                <div class='content'>
                    <h2>Recuperación de Contraseña</h2>
                    <p>Hola <strong>$name</strong>,</p>
                    <p>Hemos recibido una solicitud para restablecer tu contraseña. Utiliza el siguiente código para continuar:</p>
                    <div class='code'>$code</div>
                    <p>Este código expirará en <strong>$expirationMinutes minutos</strong>.</p>
                    <p>Si no solicitaste este código, por favor ignora este mensaje.</p>
                </div>
                <div class='footer'>
                    <p>Este es un mensaje automático, por favor no respondas a este email.</p>
                    <p>&copy; " . date('Y') . " " . ESTACIONAMIENTO_NOMBRE . " - " . ESTACIONAMIENTO_DIRECCION . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Get payment approved email template
     *
     * @param string $name Recipient name
     * @param string $receiptNumber Receipt number
     * @param float $amount Amount paid
     * @param array $months Months paid
     * @return string HTML email body
     */
    private static function getPaymentApprovedTemplate(string $name, string $receiptNumber, float $amount, array $months): string
    {
        $monthsStr = implode(', ', array_map(function($m) {
            return $m['mes'] . '/' . $m['anio'];
        }, $months));

        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;'>
            <h2 style='color: #28a745; text-align: center;'>¡Pago Aprobado!</h2>
            <p>Hola <strong>$name</strong>,</p>
            <p>Tu pago ha sido verificado y aprobado exitosamente.</p>
            
            <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <p style='margin: 5px 0;'><strong>Recibo:</strong> $receiptNumber</p>
                <p style='margin: 5px 0;'><strong>Monto:</strong> " . formatUSD($amount) . "</p>
                <p style='margin: 5px 0;'><strong>Concepto:</strong> Pago de mensualidades ($monthsStr)</p>
            </div>
            
            <p>Puedes descargar tu recibo ingresando al sistema.</p>
            
            <p style='text-align: center; margin-top: 30px;'>
                <a href='" . APP_URL . "' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Sistema</a>
            </p>
            
            <hr style='margin-top: 30px; border: 0; border-top: 1px solid #eee;'>
            <p style='color: #6c757d; font-size: 12px; text-align: center;'>" . ESTACIONAMIENTO_NOMBRE . "</p>
        </div>";
    }

    /**
     * Get payment rejected email template
     *
     * @param string $name Recipient name
     * @param string $receiptNumber Receipt number
     * @param float $amount Amount paid
     * @param string $reason Rejection reason
     * @return string HTML email body
     */
    private static function getPaymentRejectedTemplate(string $name, string $receiptNumber, float $amount, string $reason): string
    {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;'>
            <h2 style='color: #dc3545; text-align: center;'>Pago Rechazado</h2>
            <p>Hola <strong>$name</strong>,</p>
            <p>Tu pago no ha podido ser aprobado.</p>
            
            <div style='background-color: #fff3f3; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #dc3545;'>
                <p style='margin: 5px 0;'><strong>Recibo:</strong> $receiptNumber</p>
                <p style='margin: 5px 0;'><strong>Monto:</strong> " . formatUSD($amount) . "</p>
                <p style='margin: 5px 0; color: #dc3545;'><strong>Motivo del rechazo:</strong> $reason</p>
            </div>
            
            <p>Por favor, verifica la información y vuelve a registrar el pago o contacta con la administración.</p>
            
            <p style='text-align: center; margin-top: 30px;'>
                <a href='" . APP_URL . "' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Sistema</a>
            </p>
            
            <hr style='margin-top: 30px; border: 0; border-top: 1px solid #eee;'>
            <p style='color: #6c757d; font-size: 12px; text-align: center;'>" . ESTACIONAMIENTO_NOMBRE . "</p>
        </div>";
    }

    /**
     * Get password changed email template
     *
     * @param string $name Recipient name
     * @param string $ip IP address
     * @return string HTML email body
     */
    private static function getPasswordChangedTemplate(string $name, string $ip): string
    {
        $dateTime = date('d/m/Y H:i:s');
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;'>
            <h2 style='color: #007bff; text-align: center;'>Contraseña Actualizada</h2>
            <p>Hola <strong>$name</strong>,</p>
            <p>Te informamos que tu contraseña ha sido cambiada exitosamente.</p>
            
            <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <p style='margin: 5px 0;'><strong>Fecha:</strong> $dateTime</p>
                <p style='margin: 5px 0;'><strong>Dirección IP:</strong> $ip</p>
            </div>
            
            <p>Si no realizaste este cambio, por favor contacta inmediatamente con la administración.</p>
            
            <hr style='margin-top: 30px; border: 0; border-top: 1px solid #eee;'>
            <p style='color: #6c757d; font-size: 12px; text-align: center;'>" . ESTACIONAMIENTO_NOMBRE . "</p>
        </div>";
    }
}
