<?php
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../helpers/EmailHelper.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $usuario = new User($pdo);
    $dados = $usuario->login($email);

    if($dados){
        if(password_verify($senha, $dados['User_pass'])){
            
            if(session_status() == PHP_SESSION_NONE) { session_start(); }

            if ($dados['User_active'] == 0) {
                // Email ainda não validado (Cadastro)
                $codigo = $usuario->gerarCodigoVerificacao($dados['User_id'], 'Cadastro');
                EmailHelper::enviarCodigo($email, $codigo, 'Cadastro');
                
                $_SESSION['verificar_user_id'] = $dados['User_id'];
                $_SESSION['verificar_tipo'] = 'Cadastro';
                
                echo "<script>alert('Sua conta ainda não foi ativada. Um novo código foi enviado para seu e-mail.'); window.location='../View/VerificarCodigo.php';</script>";
                exit;
            } else {
                // Usuário ativo, gerar código 2FA
                $codigo = $usuario->gerarCodigoVerificacao($dados['User_id'], 'Login2FA');
                EmailHelper::enviarCodigo($email, $codigo, 'Login2FA');
                
                $_SESSION['verificar_user_id'] = $dados['User_id'];
                $_SESSION['verificar_tipo'] = 'Login2FA';
                // Salva temporariamente os dados para logar depois
                $_SESSION['temp_login_dados'] = $dados;
                
                echo "<script>alert('Para sua segurança, enviamos um código de verificação para o seu e-mail.'); window.location='../View/VerificarCodigo.php';</script>";
                exit;
            }

        }
    }

    echo "
    <script>
    alert('Email ou senha incorretos');
    window.location='../Index.php';
    </script>
    ";
}
?>