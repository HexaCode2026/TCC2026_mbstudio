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
    $novoStatus = isset($_POST['User_active']) ? ((int)$_POST['User_active'] === 1 ? 1 : 0) : null;

    if ($userId && $novoStatus !== null) {
        if ($userId != $_SESSION['User_id']) { // Proteger contra auto-inativação do admin atual
            $userModel = new User($pdo);
            $userModel->alternarStatus($userId, $novoStatus);
        }
    }
}

header("Location: ../View/admin/Usuarios.php");
exit;
