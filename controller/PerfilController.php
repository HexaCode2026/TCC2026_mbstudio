<?php
require_once __DIR__ . "/../middleware/AdminAuth.php";
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";

class PerfilController
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
     * GET /admin/perfil
     * Busca os dados do administrador logado (sem o hash da senha) e renderiza a View.
     */
    public function index(): void
    {
        $userId = (int)($_SESSION['User_id'] ?? 0);
        $dadosAdmin = $this->userModel->findById($userId);

        if (!$dadosAdmin || ($dadosAdmin['User_perm'] ?? '') !== 'A') {
            header("Location: " . $this->basePath . "/Index.php");
            exit;
        }

        $erroFlash    = Session::getFlash('error');
        $sucessoFlash = Session::getFlash('success');
        $basePath     = $this->basePath;

        require __DIR__ . "/../View/admin/Perfil.php";
    }

    /**
     * POST /admin/perfil/atualizar
     * Atualiza o nome e opcionalmente a senha do administrador logado, mantendo User_email e User_perm travados.
     */
    public function atualizar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/View/admin/Perfil.php");
            exit;
        }

        Session::validarCsrf();

        $userId = (int)($_SESSION['User_id'] ?? 0);
        $dadosAdmin = $this->userModel->findById($userId);

        if (!$dadosAdmin || ($dadosAdmin['User_perm'] ?? '') !== 'A') {
            header("Location: " . $this->basePath . "/Index.php");
            exit;
        }

        // Sanitiza User_name e ignora qualquer tentativa de envio de User_email ou User_perm
        $nomeBruto = trim(strip_tags((string)($_POST['User_name'] ?? '')));
        $nomeLimpo = preg_replace('/\s+/', ' ', $nomeBruto);

        if (mb_strlen($nomeLimpo) < 3 || mb_strlen($nomeLimpo) > 100) {
            Session::setFlash('error', 'O nome deve conter entre 3 e 100 caracteres válidos.');
            header("Location: " . $this->basePath . "/View/admin/Perfil.php");
            exit;
        }

        $senhaAtual     = (string)($_POST['senha_atual'] ?? '');
        $novaSenha      = (string)($_POST['nova_senha'] ?? '');
        $confirmarSenha = (string)($_POST['confirmar_senha'] ?? '');

        $novoHash = null;
        $desejaAlterarSenha = ($senhaAtual !== '' || $novaSenha !== '' || $confirmarSenha !== '');

        if ($desejaAlterarSenha) {
            if ($senhaAtual === '') {
                Session::setFlash('error', 'Informe sua Senha Atual para autorizar a alteração de senha.');
                header("Location: " . $this->basePath . "/View/admin/Perfil.php");
                exit;
            }

            $hashBanco = $this->userModel->findPasswordHashById($userId);
            if ($hashBanco === null || !password_verify($senhaAtual, $hashBanco)) {
                Session::setFlash('error', 'A Senha Atual informada está incorreta.');
                header("Location: " . $this->basePath . "/View/admin/Perfil.php");
                exit;
            }

            if (mb_strlen($novaSenha) < 8) {
                Session::setFlash('error', 'A Nova Senha deve ter pelo menos 8 caracteres.');
                header("Location: " . $this->basePath . "/View/admin/Perfil.php");
                exit;
            }

            if ($novaSenha !== $confirmarSenha) {
                Session::setFlash('error', 'A confirmação da nova senha não coincide.');
                header("Location: " . $this->basePath . "/View/admin/Perfil.php");
                exit;
            }

            if (password_verify($novaSenha, $hashBanco)) {
                Session::setFlash('error', 'A Nova Senha deve ser diferente da sua senha atual.');
                header("Location: " . $this->basePath . "/View/admin/Perfil.php");
                exit;
            }

            if (strtolower(trim($novaSenha)) === 'admin123') {
                Session::setFlash('error', 'Por segurança, não utilize a senha padrão temporária (admin123).');
                header("Location: " . $this->basePath . "/View/admin/Perfil.php");
                exit;
            }

            $novoHash = password_hash($novaSenha, PASSWORD_BCRYPT);
        }

        $sucesso = $this->userModel->updateProfile($userId, $nomeLimpo, $novoHash);

        if ($sucesso) {
            $_SESSION['User_name'] = $nomeLimpo;
            $msg = $novoHash !== null
                ? 'Perfil e senha de administrador atualizados com sucesso!'
                : 'Nome do administrador atualizado com sucesso!';
            Session::setFlash('success', $msg);
        } else {
            Session::setFlash('error', 'Não foi possível atualizar os dados do perfil. Tente novamente.');
        }

        header("Location: " . $this->basePath . "/View/admin/Perfil.php");
        exit;
    }
}

$controller = new PerfilController($pdo);
$action = $_GET['action'] ?? ($_SERVER['REQUEST_METHOD'] === 'POST' ? 'atualizar' : 'index');

if ($action === 'atualizar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->atualizar();
} else {
    $controller->index();
}

