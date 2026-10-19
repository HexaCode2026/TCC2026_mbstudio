<?php
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../helpers/BrevoEmailService.php";

Session::iniciar();

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
    $dados = $usuario->findByEmail($email);

    if ($dados && password_verify($senha, $dados['User_pass'])) {
        if ((int)$dados['User_active'] === 0) {
            echo "
            <script>
            alert('Sua conta encontra-se desativada. Entre em contato com a administração.');
            window.location='../Index.php';
            </script>
            ";
            exit;
        }

        // Se for primeiro acesso com troca obrigatória de senha (User_must_change_pass == 1),
        // autentica na sessão restrita e redireciona diretamente para /primeiro-acesso
        if (!empty($dados['User_must_change_pass'])) {
            Session::login($dados);
            header("Location: " . Session::getBasePath() . "/primeiro-acesso");
            exit;
        }

        $codigo = $usuario->gerarCodigoVerificacao($dados['User_id'], 'Login2FA');

        $brevoService = new BrevoEmailService();
        $enviado = $brevoService->enviarCodigo2FA($email, $dados['User_name'] ?? '', $codigo);

        if (!$enviado) {
            echo "
            <script>
            alert('Não foi possível enviar o código 2FA para o seu e-mail no momento. Tente novamente em instantes.');
            window.location='../Index.php';
            </script>
            ";
            exit;
        }

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