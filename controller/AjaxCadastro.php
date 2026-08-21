<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../core/Session.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    $nome = trim($data['nome'] ?? '');
    $email = trim($data['email'] ?? '');
    $senha = $data['senha'] ?? '';

    if (empty($nome) || empty($email) || empty($senha)) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios.']);
        exit;
    }

    $usuario = new User($pdo);

    if ($usuario->emailExiste($email)) {
        echo json_encode(['success' => false, 'message' => 'Este email já foi cadastrado!']);
        exit;
    }

    $cadastro = $usuario->cadastrar($nome, $email, $senha);

    if ($cadastro) {
        // Se desejar já logar o usuário automaticamente após o cadastro,
        // pode-se fazer o login aqui mesmo e retornar os dados na resposta.
        // Por simplicidade, vamos retornar sucesso e pedir pro usuário logar.
        echo json_encode(['success' => true, 'message' => 'Cadastro realizado com sucesso! Você já pode fazer login.']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Erro ao realizar cadastro. Tente novamente mais tarde.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método inválido.']);
