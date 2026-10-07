<?php
require_once '../config/conexao.php';
require_once '../model/User.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$codigo = $input['codigo'] ?? '';
$novaSenha = $input['senha'] ?? '';

if (empty($email) || empty($codigo) || empty($novaSenha)) {
    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios.']);
    exit;
}

$userModel = new User($pdo);
$userData = $userModel->login($email);

if (!$userData) {
    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
    exit;
}

$validacao = $userModel->validarCodigoVerificacao($userData['User_id'], $codigo, 'RecuperarSenha');

if ($validacao['status'] === true) {
    // Atualiza a senha
    $userModel->atualizarSenha($userData['User_id'], $novaSenha);
    echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso! Você já pode fazer login.']);
} else {
    echo json_encode(['success' => false, 'message' => $validacao['msg']]);
}
