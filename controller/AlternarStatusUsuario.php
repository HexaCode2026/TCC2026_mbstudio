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
    $novoStatus = $_POST['User_active'] ?? null;

    if ($userId && $novoStatus !== null) {
        if ($userId != $_SESSION['User_id']) { // Proteger contra auto-inativação do admin atual
            $userModel = new User($pdo);
            $userModel->alternarStatus($userId, $novoStatus);
        }
    }
}

header("Location: ../View/admin/Usuarios.php");
exit;
