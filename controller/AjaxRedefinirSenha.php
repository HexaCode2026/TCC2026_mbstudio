<?php
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../model/User.php';

Session::iniciar();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

Session::validarCsrf();

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$codigo = trim($input['codigo'] ?? '');
$novaSenha = $input['senha'] ?? '';

if (empty($email) || empty($codigo) || empty($novaSenha)) {
    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios.']);
    exit;
}

if (mb_strlen($novaSenha) < 6) {
    echo json_encode(['success' => false, 'message' => 'A nova senha deve conter pelo menos 6 caracteres.']);
    exit;
}

$userModel = new User($pdo);
$userData = $userModel->login($email);

if (!$userData) {
    echo json_encode(['success' => false, 'message' => 'E-mail ou código inválido.']);
    exit;
}

$validacao = $userModel->validarCodigoVerificacao($userData['User_id'], $codigo, 'RecuperarSenha');

if ($validacao['status'] === true) {
    $userModel->atualizarSenha($userData['User_id'], $novaSenha);
    echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso! Você já pode fazer login.']);
} else {
    echo json_encode(['success' => false, 'message' => $validacao['msg']]);
}
