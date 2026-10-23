<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../core/Session.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    $codigo = trim($data['codigo'] ?? '');
    if (empty($codigo)) {
        echo json_encode(['success' => false, 'message' => 'Digite o código de verificação.']);
        exit;
    }

    $tipo = $_SESSION['verificar_tipo'] ?? null;
    $usuario = new User($pdo);

    // =========================================================================
    // 1. CONFIRMAÇÃO DE CADASTRO (SÓ SALVA NO BANCO APÓS VALIDAR O CÓDIGO)
    // =========================================================================
    if ($tipo === 'Cadastro') {
        $pending = $_SESSION['pending_registration'] ?? null;

        if (!$pending) {
            echo json_encode(['success' => false, 'message' => 'Sessão de cadastro expirada. Preencha o cadastro novamente.']);
            exit;
        }

        if (time() > ($pending['expires_at'] ?? 0)) {
            unset($_SESSION['pending_registration'], $_SESSION['verificar_tipo']);
            echo json_encode(['success' => false, 'message' => 'Código expirado. Realize o cadastro novamente para gerar um novo código.']);
            exit;
        }

        if (($pending['attempts'] ?? 0) >= 5) {
            unset($_SESSION['pending_registration'], $_SESSION['verificar_tipo']);
            echo json_encode(['success' => false, 'message' => 'Muitas tentativas incorretas. Realize o cadastro novamente.']);
            exit;
        }

        if (password_verify($codigo, $pending['token_hash'])) {
            // Verifica se o e-mail já não foi inserido nesse meio tempo
            if ($usuario->emailExiste($pending['email'])) {
                unset($_SESSION['pending_registration'], $_SESSION['verificar_tipo']);
                echo json_encode([
                    'success' => true,
                    'action'  => 'login',
                    'email'   => $pending['email'],
                    'message' => 'Conta já estava ativa! Você já pode fazer login.'
                ]);
                exit;
            }

            // SOMENTE AGORA persiste o usuário no banco de dados como ativo (User_active = 1)
            $novoUserId = $usuario->cadastrarConfirmado(
                $pending['nome'],
                $pending['email'],
                $pending['senha_hash']
            );

            if ($novoUserId) {
                $confirmedEmail = $pending['email'];
                unset($_SESSION['pending_registration'], $_SESSION['verificar_tipo'], $_SESSION['verificar_user_id']);

                echo json_encode([
                    'success' => true,
                    'action'  => 'login',
                    'email'   => $confirmedEmail,
                    'message' => 'E-mail confirmado e cadastro concluído com sucesso! Você já pode fazer login.'
                ]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao salvar cadastro no banco de dados. Tente novamente.']);
                exit;
            }
        } else {
            $_SESSION['pending_registration']['attempts'] = ($pending['attempts'] ?? 0) + 1;
            $restantes = max(0, 5 - $_SESSION['pending_registration']['attempts']);
            if ($restantes === 0) {
                unset($_SESSION['pending_registration'], $_SESSION['verificar_tipo']);
                echo json_encode(['success' => false, 'message' => 'Limite de tentativas excedido. Cadastre-se novamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => "Código incorreto. Tentativas restantes: {$restantes}."]);
            }
            exit;
        }
    }

    // =========================================================================
    // 2. VERIFICAÇÃO DE DUAS ETAPAS NO LOGIN (Login2FA)
    // =========================================================================
    if (!isset($_SESSION['verificar_user_id']) || !$tipo) {
        echo json_encode(['success' => false, 'message' => 'Sessão expirada. Faça login ou cadastre-se novamente.']);
        exit;
    }

    $userId = $_SESSION['verificar_user_id'];
    $resultado = $usuario->validarCodigoVerificacao($userId, $codigo, $tipo);

    if ($resultado['status']) {
        if ($tipo === 'Login2FA') {
            $dados = $_SESSION['temp_login_dados'] ?? null;
            if (!$dados) {
                echo json_encode(['success' => false, 'message' => 'Dados de login perdidos na sessão.']);
                exit;
            }

            Session::login($dados);

            unset($_SESSION['verificar_user_id'], $_SESSION['verificar_tipo'], $_SESSION['temp_login_dados']);

            $mustChange = !empty($dados['User_must_change_pass']);
            $basePath = Session::getBasePath();

            echo json_encode([
                'success'          => true,
                'perm'             => $dados['User_perm'],
                'must_change_pass' => $mustChange,
                'redirect'         => $mustChange ? ($basePath . '/View/PrimeiroAcesso.php') : null,
                'message'          => 'Código validado! Redirecionando...'
            ]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => $resultado['msg']]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Método inválido.']);
