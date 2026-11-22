<?php
require_once __DIR__ . '/../config/config.php';

// Carrega via Composer Autoload (se instalado via vendor/) ou importação nativa em libs/PHPMailer/
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * EmailHelper — Serviço Centralizado e Resiliente de E-mails Transacionais via PHPMailer (SMTP)
 * Suporta:
 * - Reutilização de conexão TLS (SMTPKeepAlive) para envios em lote
 * - Controle de concorrência (Semáforo de I/O) contra rejeição por flood no servidor SMTP
 * - Fila assíncrona no banco de dados (Outbox Pattern em `email_queue` quando SMTP_ASYNC_QUEUE=true)
 * - Timeouts estritos e captura integral de exceções para nunca interromper a requisição HTTP
 */
class EmailHelper
{
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpSecure;
    private bool $smtpAuth;
    private string $smtpUser;
    private string $smtpPass;
    private string $fromEmail;
    private string $fromName;
    private int $smtpTimeout;
    private int $smtpDebug;
    private bool $asyncQueue;
    private string $lastError = '';
    private ?PHPMailer $mailerInstance = null;

    public function __construct(?bool $forceDirect = null)
    {
        $this->smtpHost    = defined('SMTP_HOST') ? (string)SMTP_HOST : (string)(getenv('SMTP_HOST') ?: 'smtp.gmail.com');
        $this->smtpPort    = defined('SMTP_PORT') ? (int)SMTP_PORT : (int)(getenv('SMTP_PORT') ?: 587);
        $this->smtpSecure  = defined('SMTP_SECURE') ? (string)SMTP_SECURE : (string)(getenv('SMTP_SECURE') ?: 'tls');
        $this->smtpAuth    = defined('SMTP_AUTH') ? (bool)SMTP_AUTH : filter_var(getenv('SMTP_AUTH') !== false ? getenv('SMTP_AUTH') : true, FILTER_VALIDATE_BOOLEAN);
        $this->smtpUser    = defined('SMTP_USER') ? (string)SMTP_USER : (string)(getenv('SMTP_USER') ?: '');
        $this->smtpPass    = defined('SMTP_PASS') ? (string)SMTP_PASS : (string)(getenv('SMTP_PASS') ?: '');
        $this->fromEmail   = defined('SMTP_FROM_EMAIL') && SMTP_FROM_EMAIL !== '' ? (string)SMTP_FROM_EMAIL : $this->smtpUser;
        $this->fromName    = defined('SMTP_FROM_NAME') ? (string)SMTP_FROM_NAME : (string)(getenv('SMTP_FROM_NAME') ?: 'MB Studio');
        $this->smtpTimeout = defined('SMTP_TIMEOUT') ? (int)SMTP_TIMEOUT : (int)(getenv('SMTP_TIMEOUT') ?: 8);
        $this->smtpDebug   = defined('SMTP_DEBUG') ? (int)SMTP_DEBUG : (int)(getenv('SMTP_DEBUG') ?: 0);
        $this->asyncQueue  = $forceDirect === true
            ? false
            : (defined('SMTP_ASYNC_QUEUE') ? (bool)SMTP_ASYNC_QUEUE : false);
    }

    public function __destruct()
    {
        $this->fecharConexaoSmtp();
    }

    public function fecharConexaoSmtp(): void
    {
        if ($this->mailerInstance instanceof PHPMailer) {
            try {
                $this->mailerInstance->smtpClose();
            } catch (\Throwable $t) {
                // Ignora falha ao encerrar socket no destrutor
            }
            $this->mailerInstance = null;
        }
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Método estático de fachada para compatibilidade direta com chamadas EmailHelper::enviarCodigo()
     */
    public static function enviarCodigo(string $email, string $codigo, string $tipo = 'Cadastro', string $nome = ''): bool
    {
        $mailer = new self();

        if ($tipo === 'Cadastro') {
            return $mailer->enviarConfirmacaoCadastro($email, $nome, $codigo);
        }

        if ($tipo === 'RecuperarSenha') {
            return $mailer->enviarRecuperacaoSenha($email, $nome, $codigo);
        }

        return $mailer->enviarCodigo2FA($email, $nome, $codigo);
    }

    /**
     * Inicializa ou reutiliza instância PHPMailer com SMTPKeepAlive habilitado.
     */
    private function obterMailer(): PHPMailer
    {
        if ($this->mailerInstance instanceof PHPMailer) {
            $this->mailerInstance->clearAllRecipients();
            $this->mailerInstance->clearAttachments();
            $this->mailerInstance->clearCustomHeaders();
            return $this->mailerInstance;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host          = $this->smtpHost;
        $mail->Port          = $this->smtpPort;
        $mail->SMTPAuth      = $this->smtpAuth;
        $mail->Username      = $this->smtpUser;
        $mail->Password      = $this->smtpPass;
        $mail->Timeout       = $this->smtpTimeout;
        $mail->SMTPKeepAlive = true;
        $mail->CharSet       = PHPMailer::CHARSET_UTF8;

        $langDir = __DIR__ . '/../libs/PHPMailer/language/';
        if (is_dir($langDir)) {
            $mail->setLanguage('pt_br', $langDir);
        } else {
            $mail->setLanguage('pt_br');
        }

        $secureLower = strtolower(trim($this->smtpSecure));
        if ($secureLower === 'ssl' || $secureLower === 'smtps' || $this->smtpPort === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($secureLower === 'tls' || $secureLower === 'starttls' || $this->smtpPort === 587) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            // Segurança por padrão: força STARTTLS caso uma porta padrão ou configuração ambígua seja fornecida
            $mail->SMTPSecure  = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPAutoTLS = true;
        }

        // Restringe o transporte exclusivamente a protocolos modernos (TLS 1.2 e TLS 1.3), bloqueando SSLv3/TLS 1.0/1.1
        $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }

        $appEnv = strtolower((string)(getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production')));
        $verifyPeerEnv = getenv('SMTP_VERIFY_PEER') !== false
            ? getenv('SMTP_VERIFY_PEER')
            : ($_ENV['SMTP_VERIFY_PEER'] ?? null);

        // Verifica se o PHP possui um bundle de Autoridades Certificadoras (CA) configurado no sistema
        $caFile = (string)(getenv('SMTP_CAFILE') ?: ($_ENV['SMTP_CAFILE'] ?? (ini_get('openssl.cafile') ?: ini_get('curl.cainfo'))));
        $hasCaBundle = ($caFile !== '' && is_file($caFile));

        // Em produção (ou quando SMTP_VERIFY_PEER=true), exige validação estrita do certificado X.509 contra ataques MitM.
        // Em ambiente local Windows/Laragon sem bundle CA configurado no php.ini, ajusta de forma controlada.
        if ($verifyPeerEnv !== null && $verifyPeerEnv !== '') {
            $verifyPeer = filter_var($verifyPeerEnv, FILTER_VALIDATE_BOOLEAN);
        } else {
            $verifyPeer = ($appEnv !== 'local') || $hasCaBundle;
        }

        $sslOptions = [
            'verify_peer'       => $verifyPeer,
            'verify_peer_name'  => $verifyPeer,
            'allow_self_signed' => !$verifyPeer,
            'crypto_method'     => $cryptoMethod,
        ];

        if ($hasCaBundle) {
            $sslOptions['cafile'] = $caFile;
        }

        $mail->SMTPOptions = [
            'ssl' => $sslOptions,
        ];

        if ($this->smtpDebug > 0) {
            $mail->SMTPDebug   = $this->smtpDebug;
            $mail->Debugoutput = static function (string $str, int $level): void {
                log_message('info', "[PHPMailer Debug L{$level}] " . trim($str));
            };
        }

        $remetenteEmail = $this->fromEmail !== '' ? $this->fromEmail : $this->smtpUser;
        $mail->setFrom($remetenteEmail, $this->fromName);

        $this->mailerInstance = $mail;
        return $this->mailerInstance;
    }

    /**
     * Enfileira o e-mail na tabela `email_queue` (Outbox Pattern) para processamento assíncrono em lote.
     */
    public function enfileirar(string $paraEmail, string $paraNome, string $assunto, string $html, string $altBody = ''): bool
    {
        try {
            require_once __DIR__ . '/../core/Database.php';
            $pdo = Database::getInstance()->conectar();

            $sql = "
                INSERT INTO email_queue (Recipient_email, Recipient_name, Subject, Body_html, Body_alt, Status)
                VALUES (?, ?, ?, ?, ?, 'Pendente')
            ";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                trim($paraEmail),
                trim($paraNome) !== '' ? trim($paraNome) : explode('@', $paraEmail)[0],
                $assunto,
                $html,
                $altBody !== '' ? $altBody : trim(preg_replace('/\s+/', ' ', strip_tags($html)))
            ]);

            if ($ok) {
                $partes    = explode('@', $paraEmail);
                $emailMask = substr($partes[0], 0, 2) . '***@' . ($partes[1] ?? 'dominio');
                log_message('info', "[EmailHelper][Queue] E-mail enfileirado em email_queue | Destinatário: {$emailMask} | Assunto: {$assunto}");
                return true;
            }
        } catch (\Throwable $t) {
            // Se a tabela email_queue ainda não existir no banco local, faz fallback transparente para envio direto
            log_message('error', "[EmailHelper][Queue] Fallback para envio direto (tabela email_queue indisponível): " . $t->getMessage());
        }

        return $this->enviarDireto($paraEmail, $paraNome, $assunto, $html, $altBody);
    }

    /**
     * Envia e-mail transacional (via fila se SMTP_ASYNC_QUEUE=true, ou via SMTP direto com semáforo).
     */
    public function enviar(string $paraEmail, string $paraNome, string $assunto, string $html, string $altBody = ''): bool
    {
        $this->lastError = '';
        $paraEmail = trim($paraEmail);
        $paraNome  = trim($paraNome) !== '' ? trim($paraNome) : explode('@', $paraEmail)[0];

        if (!filter_var($paraEmail, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = 'Endereço de e-mail do destinatário inválido.';
            log_message('error', "[EmailHelper] Destinatário inválido ({$paraEmail}): {$this->lastError}");
            return false;
        }

        if ($this->asyncQueue) {
            return $this->enfileirar($paraEmail, $paraNome, $assunto, $html, $altBody);
        }

        return $this->enviarDireto($paraEmail, $paraNome, $assunto, $html, $altBody);
    }

    /**
     * Realiza o disparo SMTP real com semáforo de concorrência para evitar tempestade de conexões TLS sob carga.
     */
    public function enviarDireto(string $paraEmail, string $paraNome, string $assunto, string $html, string $altBody = ''): bool
    {
        $this->lastError = '';
        $paraEmail = trim($paraEmail);
        $paraNome  = trim($paraNome) !== '' ? trim($paraNome) : explode('@', $paraEmail)[0];

        if ($this->smtpHost === '' || ($this->smtpAuth && ($this->smtpUser === '' || $this->smtpPass === ''))) {
            $this->lastError = 'Credenciais SMTP (SMTP_HOST, SMTP_USER ou SMTP_PASS) não configuradas no arquivo .env.';
            log_message('error', "[EmailHelper] Falha ao enviar para {$paraEmail}: {$this->lastError}");
            return false;
        }

        // Semáforo distribuído por slot (4 canais simultâneos) para evitar bloqueio 421 do Gmail em picos de 50 usuários
        $lockHandle = $this->adquirirSemaforoSmtp();

        try {
            $mail = $this->obterMailer();
            $mail->addAddress($paraEmail, $paraNome);
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body    = $html;
            $mail->AltBody = $altBody !== ''
                ? $altBody
                : trim(preg_replace('/\s+/', ' ', strip_tags($html)));

            $mail->send();

            $partes    = explode('@', $paraEmail);
            $emailMask = substr($partes[0], 0, 2) . '***@' . ($partes[1] ?? 'dominio');
            log_message('info', "[EmailHelper][PHPMailer] E-mail enviado via SMTP ({$this->smtpHost}:{$this->smtpPort}) | Destinatário: {$emailMask} | Assunto: {$assunto}");

            return true;
        } catch (PHPMailerException $e) {
            $this->lastError = ($this->mailerInstance && $this->mailerInstance->ErrorInfo !== '')
                ? $this->mailerInstance->ErrorInfo
                : $e->getMessage();
            $this->fecharConexaoSmtp();
            log_message('error', "[EmailHelper][PHPMailer] Erro SMTP ao enviar para {$paraEmail} | Assunto: {$assunto} | Detalhe: {$this->lastError}");
            return false;
        } catch (\Throwable $t) {
            $this->lastError = $t->getMessage();
            $this->fecharConexaoSmtp();
            log_message('error', "[EmailHelper][PHPMailer] Falha inesperada ao enviar para {$paraEmail} | Assunto: {$assunto} | Detalhe: {$this->lastError}");
            return false;
        } finally {
            $this->liberarSemaforoSmtp($lockHandle);
        }
    }

    /**
     * Adquire um dos 4 slots de semáforo de arquivo para limitar conexões SMTP concorrentes ao provedor externo.
     * Realiza varredura não-bloqueante (LOCK_NB) nos slots para eliminar contenção artificial sob carga simultânea.
     *
     * @return resource|null
     */
    private function adquirirSemaforoSmtp()
    {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        // 1. Tenta adquirir imediatamente qualquer um dos 4 slots livres sem bloqueio de thread
        for ($slot = 1; $slot <= 4; $slot++) {
            $lockFile = $logDir . "/.smtp_slot_{$slot}.lock";
            $fp = @fopen($lockFile, 'c+');
            if ($fp !== false) {
                if (@flock($fp, LOCK_EX | LOCK_NB)) {
                    return $fp;
                }
                @fclose($fp);
            }
        }

        // 2. Se todos os 4 canais estiverem ocupados, seleciona um slot aleatório e aguarda com bloqueio
        $slot = random_int(1, 4);
        $lockFile = $logDir . "/.smtp_slot_{$slot}.lock";
        $fp = @fopen($lockFile, 'c+');
        if ($fp !== false) {
            @flock($fp, LOCK_EX);
            return $fp;
        }
        return null;
    }

    /**
     * Libera o slot do semáforo SMTP.
     *
     * @param resource|null $fp
     */
    private function liberarSemaforoSmtp($fp): void
    {
        if (is_resource($fp)) {
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
    }

    /**
     * Processa um lote de e-mails pendentes na tabela `email_queue` reutilizando a mesma conexão SMTP (Keep-Alive).
     */
    public function processarFila(int $limite = 25): array
    {
        $resumo = ['processados' => 0, 'enviados' => 0, 'falhas' => 0];

        try {
            require_once __DIR__ . '/../core/Database.php';
            $pdo = Database::getInstance()->conectar();

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                SELECT *
                FROM email_queue
                WHERE Status IN ('Pendente', 'Falha')
                  AND Attempts < 3
                ORDER BY Created_at ASC
                LIMIT :limite
                FOR UPDATE
            ");
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($jobs)) {
                $pdo->commit();
                return $resumo;
            }

            $ids = array_column($jobs, 'Queue_id');
            $in  = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("UPDATE email_queue SET Status = 'Processando' WHERE Queue_id IN ($in)")->execute($ids);
            $pdo->commit();

            foreach ($jobs as $job) {
                $resumo['processados']++;
                $ok = $this->enviarDireto(
                    (string)$job['Recipient_email'],
                    (string)$job['Recipient_name'],
                    (string)$job['Subject'],
                    (string)$job['Body_html'],
                    (string)($job['Body_alt'] ?? '')
                );

                if ($ok) {
                    $resumo['enviados']++;
                    $upd = $pdo->prepare("
                        UPDATE email_queue
                        SET Status = 'Enviado',
                            Attempts = Attempts + 1,
                            Processed_at = NOW(),
                            Last_error = NULL
                        WHERE Queue_id = ?
                    ");
                    $upd->execute([$job['Queue_id']]);
                } else {
                    $resumo['falhas']++;
                    $upd = $pdo->prepare("
                        UPDATE email_queue
                        SET Status = 'Falha',
                            Attempts = Attempts + 1,
                            Last_error = ?
                        WHERE Queue_id = ?
                    ");
                    $upd->execute([$this->lastError, $job['Queue_id']]);
                }
            }
        } catch (\Throwable $t) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->lastError = $t->getMessage();
            log_message('error', "[EmailHelper][QueueWorker] Erro ao processar fila: " . $this->lastError);
        } finally {
            $this->fecharConexaoSmtp();
        }

        return $resumo;
    }

    /**
     * 1. Template de Confirmação de Cadastro e Ativação de Conta
     */
    public function enviarConfirmacaoCadastro(string $destinatarioEmail, string $destinatarioNome, string $linkConfirmacao): bool
    {
        $nomeSafe  = htmlspecialchars($destinatarioNome ?: 'Cliente', ENT_QUOTES, 'UTF-8');
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

        $altBody = "Olá, {$destinatarioNome}! Bem-vindo(a) ao MB Studio. Seu código de ativação de cadastro é: {$linkConfirmacao} (válido por 15 minutos).";

        return $this->enviar($destinatarioEmail, $destinatarioNome, $assunto, $html, $altBody);
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

        $altBody = "Olá, {$destinatarioNome}! Seu código de verificação em duas etapas (2FA) no MB Studio é: {$codigo2FA} (válido por 15 minutos).";

        return $this->enviar($destinatarioEmail, $destinatarioNome, $assunto, $html, $altBody);
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

        $altBody = "Olá, {$destinatarioNome}! Seu código de recuperação de senha no MB Studio é: {$linkRedefinicao} (válido por 15 minutos).";

        return $this->enviar($destinatarioEmail, $destinatarioNome, $assunto, $html, $altBody);
    }

    /**
     * 4. Template Unificado de Notificação de Agendamento (Cliente e Profissional)
     */
    public function enviarNotificacaoAgendamento(
        string $destinatarioEmail,
        string $destinatarioNome,
        string $assunto,
        string $eyebrow,
        string $titulo,
        string $mensagemIntro,
        string $statusLabel,
        string $statusColor,
        array $dadosAgendamento
    ): bool {
        $nomeSafe    = htmlspecialchars($destinatarioNome ?: 'Usuário', ENT_QUOTES, 'UTF-8');
        $eyebrowSafe = htmlspecialchars($eyebrow, ENT_QUOTES, 'UTF-8');
        $tituloSafe  = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
        $introSafe   = htmlspecialchars($mensagemIntro, ENT_QUOTES, 'UTF-8');
        $statusSafe  = htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8');

        $servicoSafe = htmlspecialchars((string)($dadosAgendamento['Ser_name'] ?? 'Serviço'), ENT_QUOTES, 'UTF-8');
        $clienteSafe = htmlspecialchars((string)($dadosAgendamento['client_name'] ?? 'Cliente'), ENT_QUOTES, 'UTF-8');
        $profSafe    = htmlspecialchars((string)($dadosAgendamento['employee_name'] ?? 'Profissional'), ENT_QUOTES, 'UTF-8');
        $dataRaw     = (string)($dadosAgendamento['Appo_date'] ?? '');
        $dataFormat  = $dataRaw !== '' ? date('d/m/Y', strtotime($dataRaw)) : '—';
        $horaRaw     = (string)($dadosAgendamento['Appo_start'] ?? '');
        $horaFormat  = $horaRaw !== '' ? substr($horaRaw, 0, 5) : '—';
        $preco       = isset($dadosAgendamento['Ser_price']) ? 'R$ ' . number_format((float)$dadosAgendamento['Ser_price'], 2, ',', '.') : '';
        $pagamento   = htmlspecialchars((string)($dadosAgendamento['Appo_payment_method'] ?? ''), ENT_QUOTES, 'UTF-8');
        $motivo      = htmlspecialchars(trim((string)($dadosAgendamento['Appo_cancel_reason'] ?? '')), ENT_QUOTES, 'UTF-8');
        $obs         = htmlspecialchars(trim((string)($dadosAgendamento['Appo_observation'] ?? '')), ENT_QUOTES, 'UTF-8');

        $linhasExtras = '';
        if ($preco !== '') {
            $linhasExtras .= "<tr><td style='padding: 8px 0; color: #64748B; font-size: 13.5px; border-bottom: 1px solid #E2E8F0;'>Valor</td><td style='padding: 8px 0; color: #0F172A; font-size: 13.5px; font-weight: 700; text-align: right; border-bottom: 1px solid #E2E8F0;'>{$preco}</td></tr>";
        }
        if ($pagamento !== '') {
            $linhasExtras .= "<tr><td style='padding: 8px 0; color: #64748B; font-size: 13.5px; border-bottom: 1px solid #E2E8F0;'>Pagamento</td><td style='padding: 8px 0; color: #0F172A; font-size: 13.5px; font-weight: 700; text-align: right; border-bottom: 1px solid #E2E8F0;'>{$pagamento}</td></tr>";
        }
        if ($obs !== '') {
            $linhasExtras .= "<tr><td style='padding: 8px 0; color: #64748B; font-size: 13.5px; border-bottom: 1px solid #E2E8F0;'>Observação</td><td style='padding: 8px 0; color: #0F172A; font-size: 13.5px; text-align: right; border-bottom: 1px solid #E2E8F0;'>{$obs}</td></tr>";
        }
        if ($motivo !== '') {
            $linhasExtras .= "<tr><td style='padding: 8px 0; color: #991B1B; font-size: 13.5px; font-weight: 600;'>Motivo</td><td style='padding: 8px 0; color: #991B1B; font-size: 13.5px; text-align: right;'>{$motivo}</td></tr>";
        }

        $html = "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <body style='margin: 0; padding: 0; background-color: #F4F1EA; font-family: Arial, Helvetica, sans-serif; color: #1E293B;'>
            <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0' style='background-color: #F4F1EA; padding: 32px 16px;'>
                <tr>
                    <td align='center'>
                        <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0' style='max-width: 560px; background-color: #FFFFFF; border-radius: 16px; overflow: hidden; border: 1px solid #E2E8F0; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);'>
                            <tr>
                                <td style='background-color: #0F172A; padding: 28px 32px; text-align: center; border-bottom: 3px solid #D4AF37;'>
                                    <span style='display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; color: #D4AF37; margin-bottom: 6px;'>{$eyebrowSafe}</span>
                                    <h1 style='margin: 0; font-size: 22px; font-weight: 700; color: #FFFFFF; font-family: Georgia, serif;'>{$tituloSafe}</h1>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 32px;'>
                                    <p style='margin: 0 0 14px 0; font-size: 16px; color: #0F172A;'>Olá, <strong>{$nomeSafe}</strong>,</p>
                                    <p style='margin: 0 0 20px 0; font-size: 15px; line-height: 1.6; color: #475569;'>{$introSafe}</p>

                                    <div style='margin: 0 0 20px 0; text-align: center;'>
                                        <span style='display: inline-block; padding: 6px 16px; border-radius: 999px; background-color: {$statusColor}; color: #FFFFFF; font-size: 12px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;'>Status: {$statusSafe}</span>
                                    </div>

                                    <div style='background-color: #FAF6EC; border: 1px solid #E6D5A7; border-radius: 12px; padding: 18px 20px;'>
                                        <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0'>
                                            <tr>
                                                <td style='padding: 8px 0; color: #64748B; font-size: 13.5px; border-bottom: 1px solid #E2E8F0;'>Serviço</td>
                                                <td style='padding: 8px 0; color: #0F172A; font-size: 13.5px; font-weight: 700; text-align: right; border-bottom: 1px solid #E2E8F0;'>{$servicoSafe}</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 8px 0; color: #64748B; font-size: 13.5px; border-bottom: 1px solid #E2E8F0;'>Data e Horário</td>
                                                <td style='padding: 8px 0; color: #0F172A; font-size: 13.5px; font-weight: 700; text-align: right; border-bottom: 1px solid #E2E8F0;'>{$dataFormat} às {$horaFormat}</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 8px 0; color: #64748B; font-size: 13.5px; border-bottom: 1px solid #E2E8F0;'>Cliente</td>
                                                <td style='padding: 8px 0; color: #0F172A; font-size: 13.5px; font-weight: 700; text-align: right; border-bottom: 1px solid #E2E8F0;'>{$clienteSafe}</td>
                                            </tr>
                                            <tr>
                                                <td style='padding: 8px 0; color: #64748B; font-size: 13.5px; border-bottom: 1px solid #E2E8F0;'>Profissional</td>
                                                <td style='padding: 8px 0; color: #0F172A; font-size: 13.5px; font-weight: 700; text-align: right; border-bottom: 1px solid #E2E8F0;'>{$profSafe}</td>
                                            </tr>
                                            {$linhasExtras}
                                        </table>
                                    </div>
                                </td>
                            </tr>
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

        $altBody = "Olá, {$destinatarioNome}! {$mensagemIntro} Serviço: {$dadosAgendamento['Ser_name']} | Data: {$dataFormat} às {$horaFormat} | Status: {$statusLabel}.";

        return $this->enviar($destinatarioEmail, $destinatarioNome, $assunto, $html, $altBody);
    }

    /**
     * Orquestra as notificações por e-mail para Cliente e Profissional conforme o evento do agendamento.
     * Eventos suportados: 'criado', 'confirmado', 'em_atendimento', 'concluido', 'nao_compareceu', 'cancelado'.
     */
    public function notificarEventoAgendamento(array $agendamento, string $evento, array $extra = []): array
    {
        $resultado = ['cliente' => false, 'funcionario' => false];
        $dados = array_merge($agendamento, $extra);

        $cliEmail  = trim((string)($dados['client_email'] ?? ''));
        $cliNome   = (string)($dados['client_name'] ?? 'Cliente');
        $funcEmail = trim((string)($dados['employee_email'] ?? ''));
        $funcNome  = (string)($dados['employee_name'] ?? 'Profissional');
        $actorPerm = (string)($extra['actor_perm'] ?? '');

        $notificacoes = [];

        switch ($evento) {
            case 'criado':
                $notificacoes['cliente'] = [
                    'assunto' => 'Solicitação de Agendamento Recebida — MB Studio',
                    'eyebrow' => '📅 NOVO AGENDAMENTO',
                    'titulo'  => 'Solicitação Recebida',
                    'intro'   => 'Recebemos sua solicitação de agendamento no MB Studio. Em breve você receberá a confirmação do profissional.',
                    'status'  => 'Pendente',
                    'cor'     => '#D97706',
                ];
                $notificacoes['funcionario'] = [
                    'assunto' => 'Novo Agendamento na Sua Agenda — MB Studio',
                    'eyebrow' => '🔔 NOVA SOLICITAÇÃO',
                    'titulo'  => 'Novo Agendamento Recebido',
                    'intro'   => 'Um cliente solicitou um novo horário na sua agenda. Acesse o painel para confirmar ou gerenciar o atendimento.',
                    'status'  => 'Pendente',
                    'cor'     => '#D97706',
                ];
                break;

            case 'confirmado':
                $notificacoes['cliente'] = [
                    'assunto' => 'Agendamento Confirmado! — MB Studio',
                    'eyebrow' => '✅ CONFIRMADO',
                    'titulo'  => 'Seu Horário Está Confirmado',
                    'intro'   => 'Ótima notícia! Seu agendamento foi confirmado. Esperamos por você no estúdio no dia e horário marcados.',
                    'status'  => 'Confirmado',
                    'cor'     => '#059669',
                ];
                if ($actorPerm === 'A') {
                    $notificacoes['funcionario'] = [
                        'assunto' => 'Agendamento Confirmado pela Administração — MB Studio',
                        'eyebrow' => '✅ AGENDA ATUALIZADA',
                        'titulo'  => 'Agendamento Confirmado',
                        'intro'   => 'A administração confirmou o agendamento abaixo na sua agenda.',
                        'status'  => 'Confirmado',
                        'cor'     => '#059669',
                    ];
                }
                break;

            case 'em_atendimento':
                $notificacoes['cliente'] = [
                    'assunto' => 'Atendimento Iniciado — MB Studio',
                    'eyebrow' => '✂️ EM ATENDIMENTO',
                    'titulo'  => 'Seu Atendimento Começou',
                    'intro'   => 'Seu atendimento no MB Studio foi iniciado. Desejamos uma excelente experiência!',
                    'status'  => 'Em Atendimento',
                    'cor'     => '#2563EB',
                ];
                break;

            case 'concluido':
                $notificacoes['cliente'] = [
                    'assunto' => 'Atendimento Concluído — MB Studio',
                    'eyebrow' => '✨ CONCLUÍDO',
                    'titulo'  => 'Obrigado pela Preferência!',
                    'intro'   => 'Seu atendimento foi finalizado com sucesso. Confira abaixo o resumo do serviço realizado.',
                    'status'  => 'Concluído',
                    'cor'     => '#047857',
                ];
                break;

            case 'nao_compareceu':
                $notificacoes['cliente'] = [
                    'assunto' => 'Registro de Ausência — MB Studio',
                    'eyebrow' => '⚠️ AUSÊNCIA REGISTRADA',
                    'titulo'  => 'Não Comparecimento',
                    'intro'   => 'Informamos que o seu agendamento foi registrado como não comparecimento. Caso precise remarcar, acesse o sistema.',
                    'status'  => 'Não Compareceu',
                    'cor'     => '#64748B',
                ];
                break;

            case 'cancelado':
                $cancelBy = (string)($dados['Appo_cancel_by'] ?? '');
                if ($cancelBy === 'Cliente') {
                    $notificacoes['cliente'] = [
                        'assunto' => 'Agendamento Cancelado — MB Studio',
                        'eyebrow' => '❌ CANCELAMENTO',
                        'titulo'  => 'Agendamento Cancelado',
                        'intro'   => 'Confirmamos o cancelamento do seu agendamento conforme sua solicitação.',
                        'status'  => 'Cancelado pelo Cliente',
                        'cor'     => '#DC2626',
                    ];
                    $notificacoes['funcionario'] = [
                        'assunto' => 'Cliente Cancelou Agendamento — MB Studio',
                        'eyebrow' => '❌ HORÁRIO LIBERADO',
                        'titulo'  => 'Agendamento Cancelado pelo Cliente',
                        'intro'   => 'O cliente cancelou o agendamento abaixo. Este horário voltou a ficar livre na sua agenda.',
                        'status'  => 'Cancelado pelo Cliente',
                        'cor'     => '#DC2626',
                    ];
                } elseif ($cancelBy === 'Funcionario') {
                    $notificacoes['cliente'] = [
                        'assunto' => 'Agendamento Cancelado pelo Profissional — MB Studio',
                        'eyebrow' => '❌ CANCELAMENTO',
                        'titulo'  => 'Agendamento Cancelado',
                        'intro'   => 'Infelizmente, o profissional precisou cancelar o seu agendamento. Você pode escolher um novo horário pelo sistema.',
                        'status'  => 'Cancelado pelo Profissional',
                        'cor'     => '#DC2626',
                    ];
                    $notificacoes['funcionario'] = [
                        'assunto' => 'Confirmação de Cancelamento — MB Studio',
                        'eyebrow' => '❌ CANCELADO',
                        'titulo'  => 'Agendamento Cancelado',
                        'intro'   => 'Confirmamos o cancelamento do agendamento abaixo em sua agenda. O cliente já foi notificado.',
                        'status'  => 'Cancelado pelo Profissional',
                        'cor'     => '#DC2626',
                    ];
                } else {
                    $notificacoes['cliente'] = [
                        'assunto' => 'Agendamento Cancelado pela Administração — MB Studio',
                        'eyebrow' => '❌ CANCELAMENTO',
                        'titulo'  => 'Agendamento Cancelado',
                        'intro'   => 'Seu agendamento foi cancelado pela administração do MB Studio. Caso deseje, realize um novo agendamento.',
                        'status'  => 'Cancelado pelo Administrador',
                        'cor'     => '#DC2626',
                    ];
                    $notificacoes['funcionario'] = [
                        'assunto' => 'Agendamento Cancelado pela Administração — MB Studio',
                        'eyebrow' => '❌ AGENDA ATUALIZADA',
                        'titulo'  => 'Agendamento Cancelado',
                        'intro'   => 'A administração cancelou o agendamento abaixo na sua agenda.',
                        'status'  => 'Cancelado pelo Administrador',
                        'cor'     => '#DC2626',
                    ];
                }
                break;
        }

        if (isset($notificacoes['cliente']) && filter_var($cliEmail, FILTER_VALIDATE_EMAIL)) {
            $n = $notificacoes['cliente'];
            $resultado['cliente'] = $this->enviarNotificacaoAgendamento(
                $cliEmail,
                $cliNome,
                $n['assunto'],
                $n['eyebrow'],
                $n['titulo'],
                $n['intro'],
                $n['status'],
                $n['cor'],
                $dados
            );
        }

        if (isset($notificacoes['funcionario']) && filter_var($funcEmail, FILTER_VALIDATE_EMAIL)) {
            $n = $notificacoes['funcionario'];
            $resultado['funcionario'] = $this->enviarNotificacaoAgendamento(
                $funcEmail,
                $funcNome,
                $n['assunto'],
                $n['eyebrow'],
                $n['titulo'],
                $n['intro'],
                $n['status'],
                $n['cor'],
                $dados
            );
        }

        return $resultado;
    }
}
?>
