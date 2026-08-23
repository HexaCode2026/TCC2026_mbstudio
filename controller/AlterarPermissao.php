<?php
require_once "../config/conexao.php";
require_once "../core/Session.php";
require_once "../model/User.php";

Session::iniciar();

// Proteção: Apenas Admin
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'A') {
    header("Location: ../Index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['User_id'] ?? null;
    $novaPermissao = $_POST['User_perm'] ?? null;

    if ($userId && $novaPermissao) {
        $userModel = new User($pdo);
        $userModel->alterarPermissao($userId, $novaPermissao);
    }
}

header("Location: ../View/admin/Usuarios.php");
exit;
