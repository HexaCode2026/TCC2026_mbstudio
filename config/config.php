<?php
// Carregador nativo e seguro do arquivo .env (sem dependências externas)
if (!function_exists('mb_load_env')) {
    function mb_load_env($envPath) {
        if (!is_readable($envPath)) {
            return;
        }
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $val;
                putenv("$key=$val");
            }
        }
    }
}

mb_load_env(__DIR__ . '/../.env');

// Configurações do sistema via Variáveis de Ambiente
if (!defined("DB_HOST")) define("DB_HOST", getenv("DB_HOST") ?: "localhost");
if (!defined("DB_NAME")) define("DB_NAME", getenv("DB_NAME") ?: "tcc_agendamento");
if (!defined("DB_USER")) define("DB_USER", getenv("DB_USER") ?: "root");
if (!defined("DB_PASS")) define("DB_PASS", getenv("DB_PASS") !== false ? getenv("DB_PASS") : "");

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/../'));
$basePath = $docRoot ? str_ireplace($docRoot, '', $projectRoot) : '';

if (!defined("BASE_URL")) {
    define("BASE_URL", $protocol . "://" . $host . $basePath . "/");
}
?>