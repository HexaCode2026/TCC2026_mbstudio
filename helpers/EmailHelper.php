<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

class EmailHelper {
    public static function enviarCodigo($email, $codigo, $tipo = 'Cadastro') {
        if ($tipo == 'RecuperarSenha') {
            $assunto = 'Recuperação de Senha - MB Studio';
            $textoIntro = 'Você solicitou a recuperação da sua senha. Seu código de segurança é:';
        } else {
            $assunto = $tipo == 'Cadastro' ? 'Confirme seu e-mail - MB Studio' : 'Seu código de acesso (2FA) - MB Studio';
            $textoIntro = 'Seu código de verificação é:';
        }
        
        $codigoSafe = htmlspecialchars((string)$codigo, ENT_QUOTES, 'UTF-8');
        $mensagem = "
        <div style='font-family: Arial, sans-serif; text-align: center; color: #333;'>
            <h2>MB Studio</h2>
            <p>$textoIntro</p>
            <h1 style='letter-spacing: 5px; color: #b98527; font-size: 32px;'>$codigoSafe</h1>
            <p>Este código expira em 15 minutos.</p>
            <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
            <p style='font-size: 12px; color: #777;'>Se não foi você que solicitou, pode ignorar este e-mail.</p>
        </div>
        ";

        $mail = new PHPMailer(true);

        try {
            // =========================================================
            // CONFIGURAÇÕES DO SERVIDOR SMTP VIA VARIÁVEIS DE AMBIENTE (.env)
            // =========================================================
            $smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            $smtpPort = (int)(getenv('SMTP_PORT') ?: 587);
            $smtpUser = getenv('SMTP_USER') ?: '';
            $smtpPass = getenv('SMTP_PASS') ?: '';
            $smtpFrom = getenv('SMTP_FROM') ?: $smtpUser;
            $smtpName = getenv('SMTP_FROM_NAME') ?: 'MB Studio';

            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $smtpPort;
            $mail->CharSet    = 'UTF-8';
            $mail->setLanguage('pt_br');

            // =========================================================
            // CONFIGURAÇÕES DA MENSAGEM
            // =========================================================
            $mail->setFrom($smtpFrom, $smtpName);
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body    = $mensagem;
            $mail->AltBody = "Seu código de verificação é: $codigoSafe. Expira em 15 minutos.";

            $mail->send();
            
            // Log de auditoria SEGURO: mascara o destinatário e NUNCA grava o código 2FA/OTP em texto puro
            $partesEmail = explode('@', $email);
            $emailMascarado = substr($partesEmail[0], 0, 2) . '***@' . ($partesEmail[1] ?? 'dominio');
            $logStr = "[" . date('Y-m-d H:i:s') . "] DESTINATARIO: $emailMascarado | TIPO: $tipo | STATUS: ENVIADO\n";
            @file_put_contents(__DIR__ . '/../logs/emails_enviados.log', $logStr, FILE_APPEND);
            
            return true;
        } catch (Exception $e) {
            $logErro = "[" . date('Y-m-d H:i:s') . "] ERRO EMAIL: " . $mail->ErrorInfo . "\n";
            @file_put_contents(__DIR__ . '/../logs/erros_email.log', $logErro, FILE_APPEND);
            return false;
        }
    }
}
?>
