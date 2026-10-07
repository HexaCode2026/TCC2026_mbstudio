<?php
require_once '../config/conexao.php';
require_once '../model/User.php';
require_once '../helpers/EmailHelper.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';

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
$enviado = EmailHelper::enviarCodigo($email, $codigo, 'RecuperarSenha');

if ($enviado) {
    echo json_encode(['success' => true, 'message' => 'Código de verificação enviado para seu e-mail!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao enviar e-mail. Tente novamente mais tarde.']);
}
