<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../model/User.php';
require_once __DIR__ . '/../helpers/BrevoEmailService.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}
$email = trim($input['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, informe seu e-mail.']);
    exit;
}

$userModel = new User($pdo);
$userData = $userModel->login($email);

if (!$userData) {
    // Para segurança, não revelamos se o e-mail existe ou não
    echo json_encode(['success' => true, 'message' => 'Se o e-mail estiver cadastrado, um código foi enviado.']);
    exit;
}

$codigo = $userModel->gerarCodigoVerificacao($userData['User_id'], 'RecuperarSenha');

$brevoService = new BrevoEmailService();
$enviado = $brevoService->enviarRecuperacaoSenha($email, $userData['User_name'] ?? '', $codigo);

if ($enviado) {
    echo json_encode(['success' => true, 'message' => 'Código de verificação enviado para seu e-mail!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao enviar e-mail de recuperação. Tente novamente mais tarde.']);
}
