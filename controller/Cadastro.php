<?php
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../helpers/EmailHelper.php";

Session::iniciar();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if (empty($nome) || empty($email) || empty($senha)) {
            echo "
            <script>
            alert('Todos os campos são obrigatórios!');
            window.location='../Index.php';
            </script>
            ";
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "
            <script>
            alert('Informe um endereço de e-mail válido!');
            window.location='../Index.php';
            </script>
            ";
            exit;
        }

        $usuario = new User($pdo);

        if ($usuario->emailExiste($email)) {
            echo "
            <script>
            alert('Este email já foi cadastrado!');
            window.location='../Index.php';
            </script>
            ";
            exit;
        }

        if (
            isset($_SESSION['pending_registration']) &&
            strcasecmp($_SESSION['pending_registration']['email'] ?? '', $email) === 0 &&
            isset($_SESSION['pending_registration']['sent_at']) &&
            (time() - $_SESSION['pending_registration']['sent_at']) < 30 &&
            (time() < ($_SESSION['pending_registration']['expires_at'] ?? 0))
        ) {
            $_SESSION['pending_registration']['nome'] = $nome;
            $_SESSION['pending_registration']['senha_hash'] = User::hashSenha($senha);
            $_SESSION['verificar_tipo'] = 'Cadastro';
            Session::liberarLock();

            echo "
            <script>
            alert('Um código já foi enviado recentemente para seu e-mail. Confirme-o para concluir seu cadastro.');
            window.location='../View/VerificarCodigo.php';
            </script>
            ";
            exit;
        }

        $codigo = (string) random_int(100000, 999999);
        $contextoCadastro = strtolower($email) . ':Cadastro';

        $_SESSION['pending_registration'] = [
            'nome'       => $nome,
            'email'      => $email,
            'senha_hash' => User::hashSenha($senha),
            'token_hash' => User::gerarHashCodigo($codigo, $contextoCadastro),
            'sent_at'    => time(),
            'expires_at' => time() + (15 * 60),
            'attempts'   => 0
        ];
        $_SESSION['verificar_tipo'] = 'Cadastro';
        unset($_SESSION['verificar_user_id']);

        // Libera o lock de sessão antes do disparo SMTP
        Session::liberarLock();

        $emailHelper = new EmailHelper();
        $enviado = $emailHelper->enviarConfirmacaoCadastro($email, $nome, $codigo);

        if (!$enviado) {
            Session::iniciar();
            unset($_SESSION['pending_registration'], $_SESSION['verificar_tipo']);
            Session::liberarLock();
            echo "
            <script>
            alert('Não foi possível enviar o e-mail de confirmação no momento. Tente novamente em instantes.');
            window.location='../Index.php';
            </script>
            ";
            exit;
        }

        echo "
        <script>
        alert('Enviamos um código para seu e-mail. Confirme-o para concluir seu cadastro.');
        window.location='../View/VerificarCodigo.php';
        </script>
        ";
        exit;
    } catch (\Throwable $t) {
        Session::liberarLock();
        log_message('error', '[Cadastro] Erro inesperado: ' . $t->getMessage());
        header("Location: ../Index.php");
        exit;
    }
} else {
    header("Location: ../Index.php");
    exit;
}
?>
