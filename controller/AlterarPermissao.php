<?php
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../model/User.php";

Session::iniciar();

// Proteção: Apenas Admin
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] !== 'A') {
    header("Location: ../Index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Session::validarCsrf();

    $userId = filter_input(INPUT_POST, 'User_id', FILTER_VALIDATE_INT);
    $novaPermissao = $_POST['User_perm'] ?? null;

    // Lista branca estrita de permissões e proteção contra auto-rebaixamento do Admin logado
    $permissoesValidas = ['A', 'F', 'C'];
    if ($userId && in_array($novaPermissao, $permissoesValidas, true) && $userId != $_SESSION['User_id']) {
        $userModel = new User($pdo);
        $userModel->alterarPermissao($userId, $novaPermissao);
    }
}

header("Location: ../View/admin/Usuarios.php");
exit;
