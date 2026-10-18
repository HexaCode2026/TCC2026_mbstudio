<?php
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../helpers/EmailHelper.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (
        isset($_SESSION['pending_registration']) &&
        strcasecmp($_SESSION['pending_registration']['email'] ?? '', $email) === 0 &&
        time() < ($_SESSION['pending_registration']['expires_at'] ?? 0)
    ) {
        echo "
        <script>
        alert('O cadastro deste e-mail ainda não foi confirmado. Confirme o código enviado para concluir seu cadastro.');
        window.location='../View/VerificarCodigo.php';
        </script>
        ";
        exit;
    }

    $usuario = new User($pdo);
    $dados = $usuario->login($email);

    if ($dados && password_verify($senha, $dados['User_pass'])) {
        if ($dados['User_active'] == 0) {
            echo "
            <script>
            alert('Sua conta encontra-se desativada. Entre em contato com a administração.');
            window.location='../Index.php';
            </script>
            ";
            exit;
        }

        $codigo = $usuario->gerarCodigoVerificacao($dados['User_id'], 'Login2FA');
        EmailHelper::enviarCodigo($email, $codigo, 'Login2FA');

        $_SESSION['verificar_user_id'] = $dados['User_id'];
        $_SESSION['verificar_tipo'] = 'Login2FA';
        $_SESSION['temp_login_dados'] = $dados;
        $_SESSION['login2fa_sent_at'] = time();

        echo "<script>alert('Para sua segurança, enviamos um código de verificação para o seu e-mail.'); window.location='../View/VerificarCodigo.php';</script>";
        exit;
    }

    echo "
    <script>
    alert('Email ou senha incorretos');
    window.location='../Index.php';
    </script>
    ";
    exit;
}
?>