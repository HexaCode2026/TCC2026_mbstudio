<?php
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../core/Session.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo = trim($_POST['codigo'] ?? '');
    $tipo = $_SESSION['verificar_tipo'] ?? null;
    $usuario = new User($pdo);

    if ($tipo === 'Cadastro') {
        $pending = $_SESSION['pending_registration'] ?? null;

        if (!$pending) {
            echo "<script>alert('Sessão de cadastro expirada. Cadastre-se novamente.'); window.location='../Index.php';</script>";
            exit;
        }

        if (time() > ($pending['expires_at'] ?? 0)) {
            unset($_SESSION['pending_registration'], $_SESSION['verificar_tipo']);
            echo "<script>alert('Código expirado. Realize o cadastro novamente.'); window.location='../Index.php';</script>";
            exit;
        }

        if (($pending['attempts'] ?? 0) >= 5) {
            unset($_SESSION['pending_registration'], $_SESSION['verificar_tipo']);
            echo "<script>alert('Muitas tentativas incorretas. Realize o cadastro novamente.'); window.location='../Index.php';</script>";
            exit;
        }

        if (password_verify($codigo, $pending['token_hash'])) {
            if (!$usuario->emailExiste($pending['email'])) {
                $usuario->cadastrarConfirmado(
                    $pending['nome'],
                    $pending['email'],
                    $pending['senha_hash']
                );
            }
            unset($_SESSION['pending_registration'], $_SESSION['verificar_tipo'], $_SESSION['verificar_user_id']);
            echo "<script>alert('Conta confirmada e ativada com sucesso! Você já pode fazer login.'); window.location='../Index.php';</script>";
            exit;
        } else {
            $_SESSION['pending_registration']['attempts'] = ($pending['attempts'] ?? 0) + 1;
            echo "<script>alert('Código incorreto.'); window.location='../View/VerificarCodigo.php';</script>";
            exit;
        }
    }

    if (!isset($_SESSION['verificar_user_id']) || !$tipo) {
        echo "<script>alert('Sessão expirada. Faça login novamente.'); window.location='../Index.php';</script>";
        exit;
    }

    $userId = $_SESSION['verificar_user_id'];
    $resultado = $usuario->validarCodigoVerificacao($userId, $codigo, $tipo);

    if ($resultado['status']) {
        if ($tipo === 'Login2FA') {
            $dados = $_SESSION['temp_login_dados'];

            Session::login($dados);

            unset($_SESSION['verificar_user_id'], $_SESSION['verificar_tipo'], $_SESSION['temp_login_dados']);

            if (!empty($dados['User_must_change_pass'])) {
                header("Location: " . Session::getBasePath() . "/primeiro-acesso");
                exit;
            }

            switch ($dados['User_perm']) {
                case "A": header("Location: ../View/admin/Home.php"); break;
                case "F": header("Location: ../View/funcionario/Home.php"); break;
                case "C": header("Location: ../Index.php"); break;
            }
            exit;
        }
    } else {
        $msg = addslashes($resultado['msg']);
        echo "<script>alert('$msg'); window.location='../View/VerificarCodigo.php';</script>";
        exit;
    }
}
?>
