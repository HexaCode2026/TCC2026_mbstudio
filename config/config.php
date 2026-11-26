<?php
// Carregador nativo e seguro do arquivo .env (compatível com Windows/Laragon e Linux)
if (!function_exists('mb_load_env')) {
    function mb_load_env($envPath) {
        if (!file_exists($envPath)) {
            return;
        }
        $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            if ((substr($val, 0, 1) === '"' && substr($val, -1) === '"') ||
                (substr($val, 0, 1) === "'" && substr($val, -1) === "'")) {
                $val = substr($val, 1, -1);
            }
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
            putenv("$key=$val");
        }
    }
}

// Carrega o arquivo .env localizado dentro da pasta config/ (padrão MVC do projeto)
mb_load_env(__DIR__ . '/.env');
// Fallback caso exista um .env na raiz
mb_load_env(__DIR__ . '/../.env');

// Define o fuso horário padrão da aplicação para sincronia de logs e expiração de tokens
date_default_timezone_set(getenv('APP_TIMEZONE') ?: ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo'));

// Função global para registro centralizado de logs com bloqueio exclusivo (LOCK_EX) contra concorrência
if (!function_exists('log_message')) {
    function log_message($level, $message) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $levelUpper = strtoupper((string)$level);
        $entry = "[" . date('Y-m-d H:i:s') . "] [{$levelUpper}] " . $message . PHP_EOL;
        $targetFile = ($levelUpper === 'ERROR') ? ($logDir . '/erros_email.log') : ($logDir . '/emails_enviados.log');
        @file_put_contents($targetFile, $entry, FILE_APPEND | LOCK_EX);
    }
}

// Chave secreta de aplicação (256-bit) para HMAC-SHA256 de tokens efêmeros (sem fallback previsível)
$rawAppSecret = (string)(getenv("APP_SECRET") ?: ($_ENV["APP_SECRET"] ?? ""));
if ($rawAppSecret === "" || $rawAppSecret === "mbstudio_tcc_2026_hmac_secret_key") {
    $rawAppSecret = hash('sha256', __DIR__ . '|mbstudio_appsec_hmac_key_v2');
}
if (!defined("APP_SECRET")) define("APP_SECRET", $rawAppSecret);

// Configurações de Banco de Dados via Variáveis de Ambiente
if (!defined("DB_HOST"))    define("DB_HOST", getenv("DB_HOST") ?: ($_ENV["DB_HOST"] ?? "localhost"));
if (!defined("DB_NAME"))    define("DB_NAME", getenv("DB_NAME") ?: ($_ENV["DB_NAME"] ?? "tcc_agendamento"));
if (!defined("DB_USER"))    define("DB_USER", getenv("DB_USER") ?: ($_ENV["DB_USER"] ?? "root"));
if (!defined("DB_PASS"))    define("DB_PASS", getenv("DB_PASS") !== false ? getenv("DB_PASS") : ($_ENV["DB_PASS"] ?? ""));
if (!defined("DB_TIMEOUT")) define("DB_TIMEOUT", (int)(getenv("DB_TIMEOUT") ?: ($_ENV["DB_TIMEOUT"] ?? 5)));

// Configurações de Servidor SMTP (PHPMailer) via Variáveis de Ambiente
if (!defined("SMTP_HOST"))        define("SMTP_HOST", (string)(getenv("SMTP_HOST") ?: ($_ENV["SMTP_HOST"] ?? "smtp.gmail.com")));
if (!defined("SMTP_PORT"))        define("SMTP_PORT", (int)(getenv("SMTP_PORT") ?: ($_ENV["SMTP_PORT"] ?? 587)));
if (!defined("SMTP_SECURE"))      define("SMTP_SECURE", (string)(getenv("SMTP_SECURE") ?: ($_ENV["SMTP_SECURE"] ?? "tls")));
if (!defined("SMTP_AUTH"))        define("SMTP_AUTH", filter_var(getenv("SMTP_AUTH") !== false ? getenv("SMTP_AUTH") : ($_ENV["SMTP_AUTH"] ?? true), FILTER_VALIDATE_BOOLEAN));
if (!defined("SMTP_USER"))        define("SMTP_USER", (string)(getenv("SMTP_USER") ?: ($_ENV["SMTP_USER"] ?? "")));
if (!defined("SMTP_PASS"))        define("SMTP_PASS", (string)(getenv("SMTP_PASS") ?: ($_ENV["SMTP_PASS"] ?? "")));
if (!defined("SMTP_FROM_EMAIL"))  define("SMTP_FROM_EMAIL", (string)(getenv("SMTP_FROM_EMAIL") ?: (getenv("SMTP_FROM") ?: ($_ENV["SMTP_FROM_EMAIL"] ?? ($_ENV["SMTP_FROM"] ?? SMTP_USER)))));
if (!defined("SMTP_FROM_NAME"))   define("SMTP_FROM_NAME", (string)(getenv("SMTP_FROM_NAME") ?: ($_ENV["SMTP_FROM_NAME"] ?? "MB Studio")));
if (!defined("SMTP_TIMEOUT"))     define("SMTP_TIMEOUT", (int)(getenv("SMTP_TIMEOUT") ?: ($_ENV["SMTP_TIMEOUT"] ?? 8)));
if (!defined("SMTP_DEBUG"))       define("SMTP_DEBUG", (int)(getenv("SMTP_DEBUG") ?: ($_ENV["SMTP_DEBUG"] ?? 0)));
if (!defined("SMTP_ASYNC_QUEUE")) define("SMTP_ASYNC_QUEUE", filter_var(getenv("SMTP_ASYNC_QUEUE") !== false ? getenv("SMTP_ASYNC_QUEUE") : ($_ENV["SMTP_ASYNC_QUEUE"] ?? false), FILTER_VALIDATE_BOOLEAN));

$isHttps = (
    (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') ||
    (($_SERVER['SERVER_PORT'] ?? null) == 443) ||
    (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') ||
    (strtolower((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on')
);
$protocol = $isHttps ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$rawDocRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
$realDocRoot = $rawDocRoot !== '' ? (realpath($rawDocRoot) ?: $rawDocRoot) : '';
$docRoot = rtrim(str_replace('\\', '/', $realDocRoot), '/');
$projectRoot = rtrim(str_replace('\\', '/', realpath(__DIR__ . '/../') ?: (__DIR__ . '/..')), '/');
$basePath = '';
if ($docRoot !== '' && stripos($projectRoot, $docRoot) === 0) {
    $basePath = substr($projectRoot, strlen($docRoot));
}

if (!defined("BASE_URL")) {
    define("BASE_URL", $protocol . "://" . $host . $basePath . "/");
}
?>
