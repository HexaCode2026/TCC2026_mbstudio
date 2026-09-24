<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../helpers/EmailHelper.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Tenta ler o input como JSON, caso o JS envie raw json
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Fallback para form-data
    if (!$data) {
        $data = $_POST;
    }

    $email = $data['email'] ?? '';
    $senha = $data['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        echo json_encode(['success' => false, 'message' => 'Email e senha são obrigatórios.']);
        exit;
    }

    $usuario = new User($pdo);
    $dados = $usuario->login($email);

    if ($dados && password_verify($senha, $dados['User_pass'])) {
        
        if(session_status() == PHP_SESSION_NONE) { session_start(); }

        if ($dados['User_active'] == 0) {
            $codigo = $usuario->gerarCodigoVerificacao($dados['User_id'], 'Cadastro');
            EmailHelper::enviarCodigo($email, $codigo, 'Cadastro');
            
            $_SESSION['verificar_user_id'] = $dados['User_id'];
            $_SESSION['verificar_tipo'] = 'Cadastro';

            echo json_encode([
                'success' => true,
                'action' => 'verify',
                'message' => 'Sua conta ainda não foi ativada. Verifique seu e-mail.'
            ]);
            exit;
        } else {
            $codigo = $usuario->gerarCodigoVerificacao($dados['User_id'], 'Login2FA');
            EmailHelper::enviarCodigo($email, $codigo, 'Login2FA');
            
            $_SESSION['verificar_user_id'] = $dados['User_id'];
            $_SESSION['verificar_tipo'] = 'Login2FA';
            $_SESSION['temp_login_dados'] = $dados;

            echo json_encode([
                'success' => true,
                'action' => 'verify',
                'message' => 'Para sua segurança, enviamos um código 2FA para seu e-mail.'
            ]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Email ou senha incorretos.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método inválido.']);
