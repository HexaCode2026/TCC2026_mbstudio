<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../core/Session.php";

if(session_status() == PHP_SESSION_NONE) { session_start(); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    if(!isset($_SESSION['verificar_user_id']) || !isset($_SESSION['verificar_tipo'])) {
        echo json_encode(['success' => false, 'message' => 'Sessão expirada. Faça login ou cadastre-se novamente.']);
        exit;
    }

    $codigo = trim($data['codigo'] ?? '');
    $userId = $_SESSION['verificar_user_id'];
    $tipo = $_SESSION['verificar_tipo'];

    if (empty($codigo)) {
        echo json_encode(['success' => false, 'message' => 'Digite o código de verificação.']);
        exit;
    }

    $usuario = new User($pdo);
    $resultado = $usuario->validarCodigoVerificacao($userId, $codigo, $tipo);

    if($resultado['status']) {
        if($tipo == 'Cadastro') {
            unset($_SESSION['verificar_user_id']);
            unset($_SESSION['verificar_tipo']);
            echo json_encode([
                'success' => true,
                'action' => 'login',
                'message' => 'Conta ativada com sucesso! Você já pode fazer login.'
            ]);
            exit;
        } 
        else if ($tipo == 'Login2FA') {
            $dados = $_SESSION['temp_login_dados'] ?? null;
            if (!$dados) {
                echo json_encode(['success' => false, 'message' => 'Dados de login perdidos na sessão.']);
                exit;
            }
            
            Session::login($dados);
            
            unset($_SESSION['verificar_user_id']);
            unset($_SESSION['verificar_tipo']);
            unset($_SESSION['temp_login_dados']);

            echo json_encode([
                'success' => true,
                'perm' => $dados['User_perm'],
                'message' => 'Código validado! Redirecionando...'
            ]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => $resultado['msg']]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Método inválido.']);
