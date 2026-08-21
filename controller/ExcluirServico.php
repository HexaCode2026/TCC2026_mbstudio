<?php

session_start();
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/Service.php";

// Apenas Administrador
if (!isset($_SESSION['User_id']) || $_SESSION['User_perm'] !== 'A') {
    header("Location: ../Index.php");
    exit;
}

if (isset($_GET['id'])) {
    
    $id = $_GET['id'];
    $serviceModel = new Service($pdo);

    $success = $serviceModel->destroy($id);

    if ($success) {
        echo "<script>
            alert('Serviço excluído com sucesso!');
            window.location='../View/admin/Servicos.php';
        </script>";
    } else {
        echo "<script>
            alert('Erro ao excluir serviço! Verifique se existem agendamentos pendentes.');
            window.location='../View/admin/Servicos.php';
        </script>";
    }

} else {
    header("Location: ../View/admin/Servicos.php");
}
?>
