<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Inclui os arquivos do PHPMailer que acabamos de baixar
require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

class EmailHelper {
    public static function enviarCodigo($email, $codigo, $tipo = 'Cadastro') {
        $assunto = $tipo == 'Cadastro' ? 'Confirme seu e-mail - MB Studio' : 'Seu código de acesso (2FA) - MB Studio';
        
        $mensagem = "
        <div style='font-family: Arial, sans-serif; text-align: center; color: #333;'>
            <h2>MB Studio</h2>
            <p>Seu código de verificação é:</p>
            <h1 style='letter-spacing: 5px; color: #b98527; font-size: 32px;'>$codigo</h1>
            <p>Este código expira em 15 minutos.</p>
            <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
            <p style='font-size: 12px; color: #777;'>Se não foi você que solicitou, pode ignorar este e-mail.</p>
        </div>
        ";

        $mail = new PHPMailer(true);

        try {
            // =========================================================
            // CONFIGURAÇÕES DO SERVIDOR SMTP (Ajuste com seus dados!)
            // =========================================================
            
            // Ativa o SMTP
            $mail->isSMTP();
            
            // Host do servidor de e-mail (Ex: smtp.gmail.com)
            $mail->Host       = 'smtp.gmail.com'; 
            
            // Requer autenticação SMTP
            $mail->SMTPAuth   = true;
            
            // Seu E-mail que vai ENVIAR as mensagens
            $mail->Username   = 'Hexa.code2026@gmail.com';
            
            // Sua Senha de Aplicativo (Não é a senha normal do email!)
            $mail->Password   = 'fiiz fmnb vxsf zhik'; 
            
            // Ativa criptografia segura
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            
            // Porta TCP (587 para STARTTLS no Gmail)
            $mail->Port       = 587;
            
            // Configura charset e linguagem
            $mail->CharSet = 'UTF-8';
            $mail->setLanguage('pt_br');

            // =========================================================
            // CONFIGURAÇÕES DA MENSAGEM
            // =========================================================
            
            // Quem está enviando
            $mail->setFrom('Hexa.code2026@gmail.com', 'MB Studio');
            
            // Quem vai receber
            $mail->addAddress($email);
            
            // Formato HTML
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body    = $mensagem;
            $mail->AltBody = "Seu código de verificação é: $codigo. Expira em 15 minutos.";

            $mail->send();
            
            // Mantemos o log apenas para registro de controle interno
            $logStr = "[" . date('Y-m-d H:i:s') . "] REAL-EMAIL: $email | CODIGO: $codigo | TIPO: $tipo\n";
            @file_put_contents(__DIR__ . '/../logs/emails_enviados.log', $logStr, FILE_APPEND);
            
            return true;
        } catch (Exception $e) {
            // Em caso de erro, salva no log de erros para você poder debugar
            $logErro = "[" . date('Y-m-d H:i:s') . "] ERRO EMAIL: " . $mail->ErrorInfo . "\n";
            @file_put_contents(__DIR__ . '/../logs/erros_email.log', $logErro, FILE_APPEND);
            return false;
        }
    }
}
?>
