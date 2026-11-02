<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../helpers/EmailHelper.php";

Session::iniciar();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
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
            $_SESSION['pending_registration']['nome'] = $nome;
            $_SESSION['pending_registration']['senha_hash'] = User::hashSenha($senha);
            $_SESSION['verificar_tipo'] = 'Cadastro';
            Session::liberarLock();

            echo json_encode([
                'success' => true,
                'action' => 'verify',
                'message' => 'Um código já foi enviado recentemente para seu e-mail. Digite-o abaixo para confirmar o cadastro.'
            ]);
            exit;
        }

        // Gera o código de 6 dígitos via CSPRNG e armazena Salted HMAC-SHA256 vinculado ao contexto do e-mail
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

        // Libera o lock de arquivo da sessão antes do I/O de rede (SMTP) para não travar requisições paralelas
        Session::liberarLock();

        $emailHelper = new EmailHelper();
        $enviado = $emailHelper->enviarConfirmacaoCadastro($email, $nome, $codigo);

        if (!$enviado) {
            Session::iniciar();
            unset($_SESSION['pending_registration'], $_SESSION['verificar_tipo']);
            Session::liberarLock();

            echo json_encode([
                'success' => false,
                'message' => 'Não foi possível enviar o e-mail de confirmação no momento. Verifique seu endereço de e-mail ou tente novamente em instantes.'
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'action' => 'verify',
            'message' => 'Enviamos um código de verificação para seu e-mail. Confirme-o para concluir o cadastro!'
        ]);
        exit;
    } catch (\Throwable $t) {
        Session::liberarLock();
        log_message('error', '[AjaxCadastro] Erro inesperado: ' . $t->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erro temporário ao processar o cadastro. Tente novamente em instantes.'
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Método inválido.']);
