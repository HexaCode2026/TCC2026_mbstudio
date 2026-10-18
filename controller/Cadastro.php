<?php
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../helpers/EmailHelper.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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

    $usuario = new User($pdo);

    // Verificar se e-mail já existe no banco
    if ($usuario->emailExiste($email)) {
        echo "
        <script>
        alert('Este email já foi cadastrado!');
        window.location='../Index.php';
        </script>
        ";
        exit;
    }

    // Não salva no banco ainda: guarda os dados na sessão até confirmar o código
    $codigo = (string) random_int(100000, 999999);

    $_SESSION['pending_registration'] = [
        'nome'       => $nome,
        'email'      => $email,
        'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
        'token_hash' => password_hash($codigo, PASSWORD_DEFAULT),
        'sent_at'    => time(),
        'expires_at' => time() + (15 * 60),
        'attempts'   => 0
    ];
    $_SESSION['verificar_tipo'] = 'Cadastro';
    unset($_SESSION['verificar_user_id']);

    EmailHelper::enviarCodigo($email, $codigo, 'Cadastro');

    echo "
    <script>
    alert('Enviamos um código para seu e-mail. Confirme-o para concluir seu cadastro.');
    window.location='../View/VerificarCodigo.php';
    </script>
    ";
    exit;
} else {
    header("Location: ../Index.php");
    exit;
}
?>