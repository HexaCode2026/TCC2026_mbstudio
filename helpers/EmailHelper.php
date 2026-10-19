<?php
require_once __DIR__ . '/BrevoEmailService.php';

/**
 * EmailHelper — Fachada de compatibilidade que delega 100% dos envios para o BrevoEmailService (API v3 HTTPS).
 * Nenhuma dependência do PHPMailer ou SMTP nativo é utilizada.
 */
class EmailHelper
{
    public static function enviarCodigo($email, $codigo, $tipo = 'Cadastro', $nome = '')
    {
        $brevo = new BrevoEmailService();

        if ($tipo === 'Cadastro') {
            return $brevo->enviarConfirmacaoCadastro((string)$email, (string)$nome, (string)$codigo);
        }

        if ($tipo === 'RecuperarSenha') {
            return $brevo->enviarRecuperacaoSenha((string)$email, (string)$nome, (string)$codigo);
        }

        return $brevo->enviarCodigo2FA((string)$email, (string)$nome, (string)$codigo);
    }
}
?>
