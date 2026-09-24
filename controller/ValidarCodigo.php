<?php
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../core/Session.php";

if(session_status() == PHP_SESSION_NONE) { session_start(); }

if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    if(!isset($_SESSION['verificar_user_id']) || !isset($_SESSION['verificar_tipo'])) {
        echo "<script>alert('Sessão expirada. Faça login novamente.'); window.location='../Index.php';</script>";
        exit;
    }

    $codigo = trim($_POST['codigo']);
    $userId = $_SESSION['verificar_user_id'];
    $tipo = $_SESSION['verificar_tipo'];

    $usuario = new User($pdo);
    $resultado = $usuario->validarCodigoVerificacao($userId, $codigo, $tipo);

    if($resultado['status']) {
        // Código validado com sucesso!
        
        if($tipo == 'Cadastro') {
            // Conta ativada
            unset($_SESSION['verificar_user_id']);
            unset($_SESSION['verificar_tipo']);
            echo "<script>alert('Conta ativada com sucesso! Você já pode fazer login.'); window.location='../Index.php';</script>";
            exit;
        } 
        else if ($tipo == 'Login2FA') {
            // Fazer o login de fato usando os dados que salvamos temporariamente
            $dados = $_SESSION['temp_login_dados'];
            
            Session::login($dados);
            
            unset($_SESSION['verificar_user_id']);
            unset($_SESSION['verificar_tipo']);
            unset($_SESSION['temp_login_dados']);

            switch($dados['User_perm']){
                case "A": header("Location: ../View/admin/Home.php"); break;
                case "F": header("Location: ../View/funcionario/Home.php"); break;
                case "C": header("Location: ../Index.php"); break;
            }
            exit;
        }

    } else {
        // Erro
        $msg = $resultado['msg'];
        echo "<script>alert('$msg'); window.location='../View/VerificarCodigo.php';</script>";
        exit;
    }
}
?>
