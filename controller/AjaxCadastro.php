<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../helpers/EmailHelper.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    $nome = trim($data['nome'] ?? '');
    $email = trim($data['email'] ?? '');
    $senha = $data['senha'] ?? '';

    if (empty($nome) || empty($email) || empty($senha)) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Informe um endereço de e-mail válido.']);
        exit;
    }

    $usuario = new User($pdo);

    // Verifica se o e-mail já está confirmado e cadastrado no banco de dados
    if ($usuario->emailExiste($email)) {
        echo json_encode(['success' => false, 'message' => 'Este e-mail já foi cadastrado e verificado! Faça login.']);
        exit;
    }

    // Proteção contra sobrecarga: evita reenvio repetido em menos de 30 segundos para o mesmo e-mail
    if (
        isset($_SESSION['pending_registration']) &&
        strcasecmp($_SESSION['pending_registration']['email'] ?? '', $email) === 0 &&
        isset($_SESSION['pending_registration']['sent_at']) &&
        (time() - $_SESSION['pending_registration']['sent_at']) < 30 &&
        (time() < ($_SESSION['pending_registration']['expires_at'] ?? 0))
    ) {
        // Atualiza nome/senha na sessão caso o usuário tenha corrigido, mantendo o código já enviado recentemente
        $_SESSION['pending_registration']['nome'] = $nome;
        $_SESSION['pending_registration']['senha_hash'] = password_hash($senha, PASSWORD_DEFAULT);
        $_SESSION['verificar_tipo'] = 'Cadastro';

        echo json_encode([
            'success' => true,
            'action' => 'verify',
            'message' => 'Um código já foi enviado recentemente para seu e-mail. Digite-o abaixo para confirmar o cadastro.'
        ]);
        exit;
    }

    // Gera o código de 6 dígitos e guarda os dados APENAS na sessão até a confirmação
    $codigo = (string) random_int(100000, 999999);

    $_SESSION['pending_registration'] = [
        'nome'       => $nome,
        'email'      => $email,
        'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
        'token_hash' => password_hash($codigo, PASSWORD_DEFAULT),
        'sent_at'    => time(),
        'expires_at' => time() + (15 * 60), // Expira em 15 minutos
        'attempts'   => 0
    ];
    $_SESSION['verificar_tipo'] = 'Cadastro';
    unset($_SESSION['verificar_user_id']);

    EmailHelper::enviarCodigo($email, $codigo, 'Cadastro');

    echo json_encode([
        'success' => true,
        'action' => 'verify',
        'message' => 'Enviamos um código de verificação para seu e-mail. Confirme-o para concluir o cadastro!'
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método inválido.']);
