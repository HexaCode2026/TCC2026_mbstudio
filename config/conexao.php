<?php
require_once __DIR__ . "/config.php";

$host    = DB_HOST;
$banco   = DB_NAME;
$usuario = DB_USER;
$senha   = DB_PASS;

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $erro) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @error_log(
        "[" . date('Y-m-d H:i:s') . "] DB Connection Error: " . $erro->getMessage() . "\n",
        3,
        $logDir . '/erros.log'
    );
    http_response_code(503);
    die("Serviço temporariamente indisponível. Por favor, tente novamente em instantes.");
}
?>