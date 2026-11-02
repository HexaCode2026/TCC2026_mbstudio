<?php
/**
 * Worker de Processamento da Fila de E-mails (Outbox Pattern)
 * Pode ser executado via CLI/Cron (`php controller/ProcessarFilaEmail.php`)
 * ou via requisição HTTP local/autenticada.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/EmailHelper.php';

$isCli = (PHP_SAPI === 'cli');
if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $token    = $_GET['token'] ?? $_SERVER['HTTP_X_WORKER_TOKEN'] ?? '';
    if (!in_array($remoteIp, ['127.0.0.1', '::1'], true) && (!defined('APP_SECRET') || !hash_equals(APP_SECRET, (string)$token))) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso restrito ao worker de fila.']);
        exit;
    }
}

$limite = isset($_GET['limite']) ? max(1, min(100, (int)$_GET['limite'])) : 25;
$mailer = new EmailHelper(true);
$resultado = $mailer->processarFila($limite);

if ($isCli) {
    echo sprintf(
        "[%s] Fila processada: %d processados | %d enviados | %d falhas%s",
        date('Y-m-d H:i:s'),
        $resultado['processados'],
        $resultado['enviados'],
        $resultado['falhas'],
        PHP_EOL
    );
    exit(0);
}

echo json_encode([
    'success' => true,
    'summary' => $resultado
]);

