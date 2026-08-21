<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../core/Session.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Tenta ler o input como JSON, caso o JS envie raw json
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Fallback para form-data
    if (!$data) {
        $data = $_POST;
    }

    $email = $data['email'] ?? '';
    $senha = $data['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        echo json_encode(['success' => false, 'message' => 'Email e senha são obrigatórios.']);
        exit;
    }

    $usuario = new User($pdo);
    $dados = $usuario->login($email);

    if ($dados && password_verify($senha, $dados['User_pass'])) {
        Session::login($dados);
        
        // Define para onde o usuário poderia ser redirecionado se fosse um login comum
        // Mas no modal, a ação é ditada pelo frontend.
        echo json_encode([
            'success' => true,
            'message' => 'Login realizado com sucesso!',
            'perm' => $dados['User_perm']
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Email ou senha incorretos.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método inválido.']);
