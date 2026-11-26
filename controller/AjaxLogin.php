<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../helpers/EmailHelper.php";

Session::iniciar();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    Session::validarCsrf();

    try {
        // Rate limiting básico contra força bruta (máx. 5 tentativas falhas em 5 minutos)
        $agora = time();
        $tentativasLogin = $_SESSION['login_rate_limit'] ?? ['count' => 0, 'blocked_until' => 0];
        if (($tentativasLogin['blocked_until'] ?? 0) > $agora) {
            $restante = (int)ceil(($tentativasLogin['blocked_until'] - $agora) / 60);
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => "Muitas tentativas de login incorretas. Aguarde {$restante} minuto(s) e tente novamente."
            ]);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }

        $email = trim($data['email'] ?? '');
        $senha = $data['senha'] ?? '';

        if (empty($email) || empty($senha)) {
            echo json_encode(['success' => false, 'message' => 'Email e senha são obrigatórios.']);
            exit;
        }

        if (
            isset($_SESSION['pending_registration']) &&
            strcasecmp($_SESSION['pending_registration']['email'] ?? '', $email) === 0 &&
            time() < ($_SESSION['pending_registration']['expires_at'] ?? 0)
        ) {
            echo json_encode([
                'success' => false,
                'message' => 'O cadastro deste e-mail ainda não foi confirmado. Digite o código enviado ao seu e-mail na aba de cadastro para concluir.'
            ]);
            exit;
        }

        $usuario = new User($pdo);
        $dados = $usuario->findByEmail($email);

        if ($dados && password_verify($senha, $dados['User_pass'])) {
            unset($_SESSION['login_rate_limit']);

            if ((int)$dados['User_active'] === 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Sua conta encontra-se desativada. Entre em contato com a administração.'
                ]);
                exit;
            }

            // Rehash transparente caso a senha do usuário esteja com custo Bcrypt legado (< 12) ou algoritmo antigo
            if (User::precisaRehash($dados['User_pass'])) {
                $novoHash = User::hashSenha($senha);
                $usuario->atualizarHashSenha((int)$dados['User_id'], $novoHash);
                $dados['User_pass'] = $novoHash;
            }

            // Se User_must_change_pass == 1, inicia sessão restrita e redireciona diretamente para a tela de primeiro acesso
            if (!empty($dados['User_must_change_pass'])) {
                Session::login($dados);
                $basePath = Session::getBasePath();
                Session::liberarLock();
                echo json_encode([
                    'success'          => true,
                    'must_change_pass' => true,
                    'redirect'         => $basePath . '/View/PrimeiroAcesso.php',
                    'perm'             => $dados['User_perm'],
                    'message'          => 'Primeiro acesso detectado. Redirecionando para definição de nova senha...'
                ]);
                exit;
            }

            // Proteção contra sobrecarga: se um código 2FA já foi enviado há menos de 30s para este usuário, reutiliza a tela de verificação
            if (
                (
                    isset($_SESSION['verificar_user_id']) &&
                    $_SESSION['verificar_user_id'] == $dados['User_id'] &&
                    ($_SESSION['verificar_tipo'] ?? '') === 'Login2FA' &&
                    isset($_SESSION['login2fa_sent_at']) &&
                    (time() - $_SESSION['login2fa_sent_at']) < 30
                ) ||
                $usuario->possuiCodigoRecente($dados['User_id'], 'Login2FA', 30)
            ) {
                $_SESSION['verificar_user_id'] = $dados['User_id'];
                $_SESSION['verificar_tipo'] = 'Login2FA';
                $_SESSION['temp_login_dados'] = $dados;
                Session::liberarLock();

                echo json_encode([
                    'success' => true,
                    'action'  => 'verify',
                    'message' => 'Um código 2FA já foi enviado recentemente para seu e-mail. Digite-o abaixo.'
                ]);
                exit;
            }

            $codigo = $usuario->gerarCodigoVerificacao($dados['User_id'], 'Login2FA');

            // Grava dados temporários na sessão e libera imediatamente o lock do arquivo de sessão antes do I/O SMTP
            $_SESSION['verificar_user_id'] = $dados['User_id'];
            $_SESSION['verificar_tipo'] = 'Login2FA';
            $_SESSION['temp_login_dados'] = $dados;
            $_SESSION['login2fa_sent_at'] = time();
            Session::liberarLock();

            $emailHelper = new EmailHelper();
            $enviado = $emailHelper->enviarCodigo2FA($email, $dados['User_name'] ?? '', $codigo);

            if (!$enviado) {
                Session::iniciar();
                unset($_SESSION['verificar_user_id'], $_SESSION['verificar_tipo'], $_SESSION['temp_login_dados'], $_SESSION['login2fa_sent_at']);
                Session::liberarLock();

                $usuario->invalidarCodigosPendentes($dados['User_id'], 'Login2FA');
                echo json_encode([
                    'success' => false,
                    'message' => 'Não foi possível enviar o código 2FA para seu e-mail no momento. Tente novamente em instantes.'
                ]);
                exit;
            }

            echo json_encode([
                'success' => true,
                'action'  => 'verify',
                'message' => 'Para sua segurança, enviamos um código 2FA para seu e-mail.'
            ]);
            exit;
        }

        $tentativasLogin['count'] = ($tentativasLogin['count'] ?? 0) + 1;
        if ($tentativasLogin['count'] >= 5) {
            $tentativasLogin['blocked_until'] = time() + 300;
            $tentativasLogin['count'] = 0;
        }
        $_SESSION['login_rate_limit'] = $tentativasLogin;

        echo json_encode(['success' => false, 'message' => 'Email ou senha incorretos.']);
        exit;
    } catch (\Throwable $t) {
        Session::liberarLock();
        log_message('error', '[AjaxLogin] Erro inesperado: ' . $t->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erro temporário ao realizar login. Tente novamente em instantes.'
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Método inválido.']);
