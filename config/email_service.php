<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libs/PHPMailer/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';

class EmailService {
    public static function sendVerificationCode($to, $code) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'wassimselama@gmail.com';
            $mail->Password   = 'xgdxxizbpfkqsjwd';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('wassimselama@gmail.com', 'USTHB Portal');
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = 'Verify your USTHB Account';
            $mail->Body    = "<h1>Welcome!</h1><p>Your verification code is: <b style='font-size: 24px;'>$code</b></p>";

            $mail->send();
            return true;
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/../../scratch/email_log.txt', "[" . date('Y-m-d H:i:s') . "] Error sending to $to: {$mail->ErrorInfo}\n", FILE_APPEND);
            return false;
        }
    }
    public static function sendActivationEmail($to, $name, $officialEmail) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'wassimselama@gmail.com';
            $mail->Password   = 'xgdxxizbpfkqsjwd';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('wassimselama@gmail.com', 'USTHB Portal');
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = 'USTHB Portal - Account Activated';
            $mail->Body    = "<h1>Hello $name!</h1>
                             <p>Your institutional account has been approved and activated.</p>
                             <p>You can now log in using your new official email:</p>
                             <p><b style='font-size: 20px; color: #0A2B8E;'>$officialEmail</b></p>
                             <p>Use the password you chose during registration.</p>";

            $mail->send();
            return true;
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/../../scratch/email_log.txt', "[" . date('Y-m-d H:i:s') . "] Error sending activation to $to: {$mail->ErrorInfo}\n", FILE_APPEND);
            return false;
        }
    }
}
?>
