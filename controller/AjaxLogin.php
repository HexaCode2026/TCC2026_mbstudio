<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../helpers/EmailHelper.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    $email = trim($data['email'] ?? '');
    $senha = $data['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        echo json_encode(['success' => false, 'message' => 'Email e senha são obrigatórios.']);
        exit;
    }

    // Se o usuário iniciou um cadastro nesta sessão e ainda não confirmou o código,
    // bloqueia o login imediatamente sem enviar novo e-mail nem sobrecarregar o banco
    if (
        isset($_SESSION['pending_registration']) &&
        strcasecmp($_SESSION['pending_registration']['email'] ?? '', $email) === 0 &&
        time() < ($_SESSION['pending_registration']['expires_at'] ?? 0)
    ) {
        echo json_encode([
            'success' => false,
            'message' => 'O cadastro deste e-mail ainda não foi confirmado. Digite o código enviado ao seu e-mail na aba de cadastro para concluir.'
        ]);
        exit;
    }

    $usuario = new User($pdo);
    $dados = $usuario->login($email);

    if ($dados && password_verify($senha, $dados['User_pass'])) {
        if ($dados['User_active'] == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Sua conta encontra-se desativada. Entre em contato com a administração.'
            ]);
            exit;
        }

        // Proteção contra sobrecarga: se um código 2FA já foi enviado há menos de 30s para este usuário, reutiliza a tela de verificação
        if (
            isset($_SESSION['verificar_user_id']) &&
            $_SESSION['verificar_user_id'] == $dados['User_id'] &&
            ($_SESSION['verificar_tipo'] ?? '') === 'Login2FA' &&
            isset($_SESSION['login2fa_sent_at']) &&
            (time() - $_SESSION['login2fa_sent_at']) < 30
        ) {
            $_SESSION['temp_login_dados'] = $dados;
            echo json_encode([
                'success' => true,
                'action'  => 'verify',
                'message' => 'Um código 2FA já foi enviado recentemente para seu e-mail. Digite-o abaixo.'
            ]);
            exit;
        }

        $codigo = $usuario->gerarCodigoVerificacao($dados['User_id'], 'Login2FA');
        EmailHelper::enviarCodigo($email, $codigo, 'Login2FA');

        $_SESSION['verificar_user_id'] = $dados['User_id'];
        $_SESSION['verificar_tipo'] = 'Login2FA';
        $_SESSION['temp_login_dados'] = $dados;
        $_SESSION['login2fa_sent_at'] = time();

        echo json_encode([
            'success' => true,
            'action'  => 'verify',
            'message' => 'Para sua segurança, enviamos um código 2FA para seu e-mail.'
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Email ou senha incorretos.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método inválido.']);
