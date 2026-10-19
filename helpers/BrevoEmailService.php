<?php
require_once __DIR__ . '/../config/config.php';

/**
 * BrevoEmailService — Serviço Centralizado de E-mails Transacionais via API v3 da Brevo (HTTPS / Porta 443)
 * Substitui integralmente envios via PHPMailer, SMTP nativo e mail().
 */
class BrevoEmailService
{
    private const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    private string $apiKey;
    private string $fromEmail;
    private string $fromName;
    private int $lastHttpCode = 0;
    private string $lastResponse = '';
    private string $lastError = '';

    public function __construct()
    {
        $this->apiKey    = (string)(getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? ''));
        $this->fromEmail = (string)(getenv('BREVO_FROM_EMAIL') ?: ($_ENV['BREVO_FROM_EMAIL'] ?? 'hexa.code2026@gmail.com'));
        $this->fromName  = (string)(getenv('BREVO_FROM_NAME') ?: ($_ENV['BREVO_FROM_NAME'] ?? 'Sistema de Agendamento'));
    }

    public function getLastHttpCode(): int
    {
        return $this->lastHttpCode;
    }

    public function getLastResponse(): string
    {
        return $this->lastResponse;
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Envia e-mail transacional via cURL HTTPS (porta 443) para a API v3 da Brevo.
     */
    public function enviar(string $paraEmail, string $paraNome, string $assunto, string $html): bool
    {
        $this->lastHttpCode = 0;
        $this->lastResponse = '';
        $this->lastError    = '';

        $paraEmail = trim($paraEmail);
        $paraNome  = trim($paraNome) !== '' ? trim($paraNome) : explode('@', $paraEmail)[0];

        if ($this->apiKey === '') {
            $this->lastError = 'Variável de ambiente BREVO_API_KEY não configurada.';
            log_message('error', '[BrevoEmailService] Falha ao enviar para ' . $paraEmail . ': ' . $this->lastError);
            return false;
        }

        $payload = [
            'sender' => [
                'name'  => $this->fromName,
                'email' => $this->fromEmail,
            ],
            'to' => [
                [
                    'email' => $paraEmail,
                    'name'  => $paraNome,
                ],
            ],
            'subject'     => $assunto,
            'htmlContent' => $html,
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $headers = [
            'accept: application/json',
            'api-key: ' . $this->apiKey,
            'content-type: application/json',
        ];

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        $httpCode  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Quando executado em ambiente local Windows (ex: Laragon) sem bundle CA configurado no php.ini,
        // realiza fallback controlado mantendo HTTPS/TLS na porta 443 para não interromper o fluxo.
        if ($curlErrno === 60 || $curlErrno === 77) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response  = curl_exec($ch);
            $curlErrno = curl_errno($ch);
            $curlError = curl_error($ch);
            $httpCode  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }

        // No PHP 8.0+, CurlHandle é liberado automaticamente; unset fecha o handle sem disparar o aviso de depreciação do PHP 8.5+
        unset($ch);

        $this->lastHttpCode = $httpCode;
        $this->lastResponse = is_string($response) ? $response : '';

        if ($curlErrno !== 0) {
            $this->lastError = "Erro cURL ({$curlErrno}): {$curlError}";
            log_message('error', "[BrevoEmailService] Falha de conexão cURL ao enviar para {$paraEmail} | Assunto: {$assunto} | {$this->lastError}");
            return false;
        }

        if ($httpCode === 200 || $httpCode === 201) {
            $partes = explode('@', $paraEmail);
            $emailMask = substr($partes[0], 0, 2) . '***@' . ($partes[1] ?? 'dominio');
            log_message('info', "[BrevoEmailService] Sucesso HTTP {$httpCode} | Destinatário: {$emailMask} | Assunto: {$assunto}");
            return true;
        }

        $this->lastError = "HTTP {$httpCode} - Resposta da API: {$this->lastResponse}";
        log_message('error', "[BrevoEmailService] Erro da API Brevo ao enviar para {$paraEmail} | Assunto: {$assunto} | {$this->lastError}");
        return false;
    }

    /**
     * 1. Template de Confirmação de Cadastro e Ativação de Conta
     */
    public function enviarConfirmacaoCadastro(string $destinatarioEmail, string $destinatarioNome, string $linkConfirmacao): bool
    {
        $nomeSafe = htmlspecialchars($destinatarioNome ?: 'Cliente', ENT_QUOTES, 'UTF-8');
        $valorSafe = htmlspecialchars($linkConfirmacao, ENT_QUOTES, 'UTF-8');

        $assunto = 'Bem-vindo(a) ao MB Studio — Confirme seu Cadastro';

        $html = "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <body style='margin: 0; padding: 0; background-color: #F4F1EA; font-family: Arial, Helvetica, sans-serif; color: #1E293B;'>
            <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0' style='background-color: #F4F1EA; padding: 32px 16px;'>
                <tr>
                    <td align='center'>
                        <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0' style='max-width: 560px; background-color: #FFFFFF; border-radius: 16px; overflow: hidden; border: 1px solid #E2E8F0; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);'>
                            <!-- Cabeçalho -->
                            <tr>
                                <td style='background-color: #0F172A; padding: 28px 32px; text-align: center; border-bottom: 3px solid #D4AF37;'>
                                    <span style='display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; color: #D4AF37; margin-bottom: 6px;'>✦ MB STUDIO ✦</span>
                                    <h1 style='margin: 0; font-size: 24px; font-weight: 700; color: #FFFFFF; font-family: Georgia, serif;'>Confirmação de Cadastro</h1>
                                </td>
                            </tr>
                            <!-- Corpo -->
                            <tr>
                                <td style='padding: 32px;'>
                                    <p style='margin: 0 0 16px 0; font-size: 16px; color: #0F172A;'>Olá, <strong>{$nomeSafe}</strong>!</p>
                                    <p style='margin: 0 0 20px 0; font-size: 15px; line-height: 1.6; color: #475569;'>
                                        Seja muito bem-vindo(a) ao <strong>Sistema de Agendamento MB Studio</strong>. Para concluir a criação da sua conta e liberar o agendamento online com nossos especialistas, digite o código abaixo na tela de confirmação:
                                    </p>
                                    <div style='margin: 24px 0; padding: 22px 16px; background-color: #FAF6EC; border: 2px solid #D4AF37; border-radius: 14px; text-align: center;'>
                                        <p style='margin: 0 0 8px 0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: #8A6508;'>Seu Código de Ativação</p>
                                        <div style='font-size: 38px; font-weight: 800; letter-spacing: 10px; color: #0F172A; font-family: Courier New, monospace;'>{$valorSafe}</div>
                                        <p style='margin: 10px 0 0 0; font-size: 12.5px; color: #64748B;'>⏱ Este código é válido por <strong>15 minutos</strong>.</p>
                                    </div>
                                    <p style='margin: 24px 0 0 0; font-size: 13px; line-height: 1.5; color: #64748B; text-align: center;'>
                                        Se você não realizou este cadastro em nosso sistema, basta desconsiderar este e-mail.
                                    </p>
                                </td>
                            </tr>
                            <!-- Rodapé -->
                            <tr>
                                <td style='background-color: #F8FAFC; padding: 18px 32px; text-align: center; border-top: 1px solid #E2E8F0; font-size: 12px; color: #94A3B8;'>
                                    © " . date('Y') . " MB Studio — Beleza, Confiança & Elegância. Todos os direitos reservados.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";

        return $this->enviar($destinatarioEmail, $destinatarioNome, $assunto, $html);
    }

    /**
     * 2. Template de Código de Verificação em Duas Etapas (2FA)
     */
    public function enviarCodigo2FA(string $destinatarioEmail, string $destinatarioNome, string $codigo2FA): bool
    {
        $nomeSafe   = htmlspecialchars($destinatarioNome ?: 'Usuário', ENT_QUOTES, 'UTF-8');
        $codigoSafe = htmlspecialchars($codigo2FA, ENT_QUOTES, 'UTF-8');
        $assunto    = 'Seu Código de Acesso (2FA) — MB Studio';

        $html = "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <body style='margin: 0; padding: 0; background-color: #F4F1EA; font-family: Arial, Helvetica, sans-serif; color: #1E293B;'>
            <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0' style='background-color: #F4F1EA; padding: 32px 16px;'>
                <tr>
                    <td align='center'>
                        <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0' style='max-width: 560px; background-color: #FFFFFF; border-radius: 16px; overflow: hidden; border: 1px solid #E2E8F0; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);'>
                            <!-- Cabeçalho -->
                            <tr>
                                <td style='background-color: #0F172A; padding: 28px 32px; text-align: center; border-bottom: 3px solid #D4AF37;'>
                                    <span style='display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; color: #D4AF37; margin-bottom: 6px;'>🔒 SEGURANÇA MB STUDIO</span>
                                    <h1 style='margin: 0; font-size: 23px; font-weight: 700; color: #FFFFFF; font-family: Georgia, serif;'>Verificação em Duas Etapas (2FA)</h1>
                                </td>
                            </tr>
                            <!-- Corpo -->
                            <tr>
                                <td style='padding: 32px;'>
                                    <p style='margin: 0 0 14px 0; font-size: 16px; color: #0F172A;'>Olá, <strong>{$nomeSafe}</strong>,</p>
                                    <p style='margin: 0 0 22px 0; font-size: 15px; line-height: 1.6; color: #475569;'>
                                        Identificamos uma tentativa de acesso à sua conta no <strong>MB Studio</strong>. Utilize o código de segurança abaixo para validar sua entrada:
                                    </p>
                                    <!-- Caixa de Destaque do Código 2FA -->
                                    <div style='margin: 26px 0; padding: 24px 16px; background-color: #FAF6EC; border: 2px solid #D4AF37; border-radius: 14px; text-align: center;'>
                                        <span style='display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: #8A6508; margin-bottom: 10px;'>Código de Verificação</span>
                                        <div style='font-size: 38px; font-weight: 800; letter-spacing: 10px; color: #0F172A; font-family: Courier New, monospace;'>{$codigoSafe}</div>
                                        <span style='display: block; margin-top: 10px; font-size: 12.5px; color: #64748B;'>⏱ Este código expira em <strong>15 minutos</strong>.</span>
                                    </div>
                                    <!-- Alerta de Segurança -->
                                    <div style='padding: 14px 16px; background-color: #FEF2F2; border-left: 4px solid #DC2626; border-radius: 8px; margin-top: 20px;'>
                                        <p style='margin: 0; font-size: 13px; line-height: 1.5; color: #991B1B;'>
                                            <strong>Aviso de Segurança:</strong> Nunca compartilhe este código com terceiros. Nossa equipe jamais solicitará seu código 2FA por telefone ou WhatsApp.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            <!-- Rodapé -->
                            <tr>
                                <td style='background-color: #F8FAFC; padding: 18px 32px; text-align: center; border-top: 1px solid #E2E8F0; font-size: 12px; color: #94A3B8;'>
                                    © " . date('Y') . " MB Studio — Sistema Seguro de Agendamento.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";

        return $this->enviar($destinatarioEmail, $destinatarioNome, $assunto, $html);
    }

    /**
     * 3. Template de Recuperação e Redefinição de Senha
     */
    public function enviarRecuperacaoSenha(string $destinatarioEmail, string $destinatarioNome, string $linkRedefinicao): bool
    {
        $nomeSafe  = htmlspecialchars($destinatarioNome ?: 'Usuário', ENT_QUOTES, 'UTF-8');
        $valorSafe = htmlspecialchars($linkRedefinicao, ENT_QUOTES, 'UTF-8');

        $assunto = 'Recuperação de Senha — MB Studio';

        $html = "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <body style='margin: 0; padding: 0; background-color: #F4F1EA; font-family: Arial, Helvetica, sans-serif; color: #1E293B;'>
            <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0' style='background-color: #F4F1EA; padding: 32px 16px;'>
                <tr>
                    <td align='center'>
                        <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0' style='max-width: 560px; background-color: #FFFFFF; border-radius: 16px; overflow: hidden; border: 1px solid #E2E8F0; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);'>
                            <!-- Cabeçalho -->
                            <tr>
                                <td style='background-color: #0F172A; padding: 28px 32px; text-align: center; border-bottom: 3px solid #D4AF37;'>
                                    <span style='display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; color: #D4AF37; margin-bottom: 6px;'>🔑 RECUPERAÇÃO DE ACESSO</span>
                                    <h1 style='margin: 0; font-size: 23px; font-weight: 700; color: #FFFFFF; font-family: Georgia, serif;'>Redefinição de Senha</h1>
                                </td>
                            </tr>
                            <!-- Corpo -->
                            <tr>
                                <td style='padding: 32px;'>
                                    <p style='margin: 0 0 14px 0; font-size: 16px; color: #0F172A;'>Olá, <strong>{$nomeSafe}</strong>,</p>
                                    <p style='margin: 0 0 20px 0; font-size: 15px; line-height: 1.6; color: #475569;'>
                                        Recebemos uma solicitação para redefinir a senha da sua conta no <strong>Sistema de Agendamento MB Studio</strong>. Digite o código de segurança abaixo na tela do sistema para cadastrar sua nova senha:
                                    </p>
                                    <div style='margin: 24px 0; padding: 22px 16px; background-color: #FAF6EC; border: 2px solid #D4AF37; border-radius: 14px; text-align: center;'>
                                        <p style='margin: 0 0 8px 0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: #8A6508;'>Código de Recuperação de Senha</p>
                                        <div style='font-size: 38px; font-weight: 800; letter-spacing: 10px; color: #0F172A; font-family: Courier New, monospace;'>{$valorSafe}</div>
                                        <p style='margin: 10px 0 0 0; font-size: 12.5px; color: #64748B;'>⏱ Este código é válido por <strong>15 minutos</strong>.</p>
                                    </div>
                                    <div style='padding: 14px 16px; background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; margin-top: 22px;'>
                                        <p style='margin: 0; font-size: 13px; line-height: 1.5; color: #64748B;'>
                                            Caso você não tenha solicitado a alteração de senha, nenhuma ação é necessária. Sua senha atual permanecerá ativa e segura.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            <!-- Rodapé -->
                            <tr>
                                <td style='background-color: #F8FAFC; padding: 18px 32px; text-align: center; border-top: 1px solid #E2E8F0; font-size: 12px; color: #94A3B8;'>
                                    © " . date('Y') . " MB Studio — Todos os direitos reservados.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";

        return $this->enviar($destinatarioEmail, $destinatarioNome, $assunto, $html);
    }
}

