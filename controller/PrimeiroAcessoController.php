<?php
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../model/User.php";

Session::iniciar();

class PrimeiroAcessoController
{
    private PDO $pdo;
    private User $userModel;
    private string $basePath;

    public function __construct(PDO $pdo)
    {
        $this->pdo       = $pdo;
        $this->userModel = new User($pdo);
        $this->basePath  = Session::getBasePath();
    }

    /**
     * GET /primeiro-acesso
     * Renderiza a tela isolada de troca obrigatória de senha no primeiro login.
     */
    public function index(): void
    {
        if (empty($_SESSION['User_id'])) {
            header("Location: " . $this->basePath . "/Index.php");
            exit;
        }

        if (empty($_SESSION['User_must_change_pass'])) {
            $this->redirecionarPorPermissao($_SESSION['User_perm'] ?? 'C');
            return;
        }

        $erroFlash    = Session::getFlash('error');
        $sucessoFlash = Session::getFlash('success');
        $basePath     = $this->basePath;

        require __DIR__ . "/../View/PrimeiroAcesso.php";
    }

    /**
     * POST /primeiro-acesso/salvar
     * Valida e persiste a nova senha obrigatória do primeiro acesso.
     */
    public function salvar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/View/PrimeiroAcesso.php");
            exit;
        }

        Session::validarCsrf();

        $userId = (int)($_SESSION['User_id'] ?? 0);
        $mustChange = !empty($_SESSION['User_must_change_pass']);

        if ($userId <= 0 || !$mustChange) {
            header("Location: " . $this->basePath . "/Index.php");
            exit;
        }

        $novaSenha      = (string)($_POST['nova_senha'] ?? '');
        $confirmarSenha = (string)($_POST['confirmar_senha'] ?? '');

        if (mb_strlen($novaSenha) < 8) {
            Session::setFlash('error', 'A nova senha deve conter no mínimo 8 caracteres.');
            header("Location: " . $this->basePath . "/View/PrimeiroAcesso.php");
            exit;
        }

        if ($novaSenha !== $confirmarSenha) {
            Session::setFlash('error', 'A confirmação de senha não coincide com a nova senha digitada.');
            header("Location: " . $this->basePath . "/View/PrimeiroAcesso.php");
            exit;
        }

        if (strtolower(trim($novaSenha)) === 'admin123') {
            Session::setFlash('error', 'Por segurança, você não pode reutilizar a senha temporária padrão (admin123).');
            header("Location: " . $this->basePath . "/View/PrimeiroAcesso.php");
            exit;
        }

        $hashAtual = $this->userModel->findPasswordHashById($userId);
        if ($hashAtual !== null && password_verify($novaSenha, $hashAtual)) {
            Session::setFlash('error', 'A nova senha deve ser diferente da senha temporária atual.');
            header("Location: " . $this->basePath . "/View/PrimeiroAcesso.php");
            exit;
        }

        $novoHash = User::hashSenha($novaSenha);
        $atualizado = $this->userModel->updateFirstPassword($userId, $novoHash);

        if (!$atualizado) {
            Session::setFlash('error', 'Ocorreu um erro ao salvar sua nova senha. Tente novamente.');
            header("Location: " . $this->basePath . "/View/PrimeiroAcesso.php");
            exit;
        }

        $_SESSION['User_must_change_pass'] = false;
        Session::setFlash('success', 'Senha definida com sucesso! Seu acesso administrativo foi liberado.');

        $this->redirecionarPorPermissao($_SESSION['User_perm'] ?? 'A');
    }

    private function redirecionarPorPermissao(string $perm): void
    {
        if ($perm === 'A') {
            header("Location: " . $this->basePath . "/View/admin/Dashboard.php");
        } elseif ($perm === 'F') {
            header("Location: " . $this->basePath . "/View/funcionario/Home.php");
        } else {
            header("Location: " . $this->basePath . "/Index.php");
        }
        exit;
    }
}

$controller = new PrimeiroAcessoController($pdo);
$action = $_GET['action'] ?? ($_SERVER['REQUEST_METHOD'] === 'POST' ? 'salvar' : 'index');

if ($action === 'salvar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->salvar();
} else {
    $controller->index();
}

