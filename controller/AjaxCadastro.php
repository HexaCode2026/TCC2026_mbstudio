<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../helpers/EmailHelper.php";

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
    $userExistente = $usuario->login($email);

    if ($userExistente) {
        if ($userExistente['User_active'] == 1) {
            echo json_encode(['success' => false, 'message' => 'Este email já foi cadastrado e verificado! Faça login.']);
            exit;
        } else {
            // E-mail existe, mas não foi verificado. Atualizar os dados e reenviar o código.
            $userId = $userExistente['User_id'];
            $usuario->atualizarDadosInativos($userId, $nome, $senha);
            
            $codigo = $usuario->gerarCodigoVerificacao($userId, 'Cadastro');
            EmailHelper::enviarCodigo($email, $codigo, 'Cadastro');

            if(session_status() == PHP_SESSION_NONE) { session_start(); }
            $_SESSION['verificar_user_id'] = $userId;
            $_SESSION['verificar_tipo'] = 'Cadastro';

            echo json_encode([
                'success' => true, 
                'action' => 'verify',
                'message' => 'Seu cadastro estava pendente. Um novo código de verificação foi enviado para seu e-mail!'
            ]);
            exit;
        }
    }

    $userId = $usuario->cadastrar($nome, $email, $senha);

    if ($userId) {
        $codigo = $usuario->gerarCodigoVerificacao($userId, 'Cadastro');
        EmailHelper::enviarCodigo($email, $codigo, 'Cadastro');

        if(session_status() == PHP_SESSION_NONE) { session_start(); }
        $_SESSION['verificar_user_id'] = $userId;
        $_SESSION['verificar_tipo'] = 'Cadastro';

        echo json_encode([
            'success' => true, 
            'action' => 'verify',
            'message' => 'Cadastro realizado! Verifique seu e-mail para ativar a conta.'
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Erro ao realizar cadastro. Tente novamente mais tarde.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método inválido.']);
