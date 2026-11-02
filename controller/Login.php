<?php
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../helpers/EmailHelper.php";

Session::iniciar();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if (
            isset($_SESSION['pending_registration']) &&
            strcasecmp($_SESSION['pending_registration']['email'] ?? '', $email) === 0 &&
            time() < ($_SESSION['pending_registration']['expires_at'] ?? 0)
        ) {
            Session::liberarLock();
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
                Session::liberarLock();
                echo "
                <script>
                alert('Sua conta encontra-se desativada. Entre em contato com a administração.');
                window.location='../Index.php';
                </script>
                ";
                exit;
            }

            // Rehash transparente caso a senha do usuário esteja com custo Bcrypt legado (< 12)
            if (User::precisaRehash($dados['User_pass'])) {
                $novoHash = User::hashSenha($senha);
                $usuario->atualizarHashSenha((int)$dados['User_id'], $novoHash);
                $dados['User_pass'] = $novoHash;
            }

            if (!empty($dados['User_must_change_pass'])) {
                Session::login($dados);
                $basePath = Session::getBasePath();
                Session::liberarLock();
                header("Location: " . $basePath . "/View/PrimeiroAcesso.php");
                exit;
            }

            if ($usuario->possuiCodigoRecente($dados['User_id'], 'Login2FA', 30)) {
                $_SESSION['verificar_user_id'] = $dados['User_id'];
                $_SESSION['verificar_tipo'] = 'Login2FA';
                $_SESSION['temp_login_dados'] = $dados;
                Session::liberarLock();

                echo "<script>alert('Um código 2FA já foi enviado recentemente para o seu e-mail.'); window.location='../View/VerificarCodigo.php';</script>";
                exit;
            }

            $codigo = $usuario->gerarCodigoVerificacao($dados['User_id'], 'Login2FA');

            $_SESSION['verificar_user_id'] = $dados['User_id'];
            $_SESSION['verificar_tipo'] = 'Login2FA';
            $_SESSION['temp_login_dados'] = $dados;
            $_SESSION['login2fa_sent_at'] = time();
            Session::liberarLock();

            $emailHelper = new EmailHelper();
            $enviado = $emailHelper->enviarCodigo2FA($email, $dados['User_name'] ?? '', $codigo);

            if (!$enviado) {
                Session::iniciar();
                unset($_SESSION['verificar_user_id'], $_SESSION['verificar_tipo'], $_SESSION['temp_login_dados'], $_SESSION['login2fa_sent_at']);
                Session::liberarLock();

                $usuario->invalidarCodigosPendentes($dados['User_id'], 'Login2FA');
                echo "
                <script>
                alert('Não foi possível enviar o código 2FA para o seu e-mail no momento. Tente novamente em instantes.');
                window.location='../Index.php';
                </script>
                ";
                exit;
            }

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
    } catch (\Throwable $t) {
        Session::liberarLock();
        log_message('error', '[Login] Erro inesperado: ' . $t->getMessage());
        header("Location: ../Index.php");
        exit;
    }
}
?>
