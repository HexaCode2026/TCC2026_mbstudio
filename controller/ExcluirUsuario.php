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

    if ($userId) {
        // Não deixar o admin se excluir acidentalmente (opcional, mas recomendado)
        if ($userId != $_SESSION['User_id']) {
            $userModel = new User($pdo);
            $userModel->excluir($userId);
        }
    }
}

header("Location: ../View/admin/Usuarios.php");
exit;
