<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../model/User.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../helpers/EmailHelper.php';

Session::iniciar();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Session::liberarLock();
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

Session::validarCsrf();
// Libera o lock de sessão imediatamente após validar o CSRF pois este endpoint não escreve na sessão
Session::liberarLock();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    $email = trim($input['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, informe um endereço de e-mail válido.']);
        exit;
    }

    $userModel = new User($pdo);
    $userData = $userModel->login($email);

    if (!$userData || (int)($userData['User_active'] ?? 0) === 0) {
        // Por segurança, não revelamos se o e-mail existe ou está inativo (mitigação contra User Enumeration)
        echo json_encode(['success' => true, 'message' => 'Se o e-mail estiver cadastrado, um código foi enviado.']);
        exit;
    }

    // Proteção contra sobrecarga: evita múltiplos disparos de recuperação em menos de 30 segundos
    if ($userModel->possuiCodigoRecente($userData['User_id'], 'RecuperarSenha', 30)) {
        echo json_encode([
            'success' => true,
            'message' => 'Um código de recuperação já foi enviado recentemente para seu e-mail. Verifique sua caixa de entrada ou spam.'
        ]);
        exit;
    }

    $codigo = $userModel->gerarCodigoVerificacao($userData['User_id'], 'RecuperarSenha');

    $emailHelper = new EmailHelper();
    $enviado = $emailHelper->enviarRecuperacaoSenha($email, $userData['User_name'] ?? '', $codigo);

    if ($enviado) {
        echo json_encode(['success' => true, 'message' => 'Código de verificação enviado para seu e-mail!']);
    } else {
        $userModel->invalidarCodigosPendentes($userData['User_id'], 'RecuperarSenha');
        echo json_encode(['success' => false, 'message' => 'Erro ao enviar e-mail de recuperação no momento. Tente novamente mais tarde.']);
    }
} catch (\Throwable $t) {
    log_message('error', '[AjaxEsqueciSenha] Erro inesperado: ' . $t->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Serviço temporariamente indisponível. Tente novamente em instantes.'
    ]);
}
