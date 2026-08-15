<?php
date_default_timezone_set('America/Sao_Paulo');
session_start();
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/Availability.php";

// Proteção para Funcionário
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'F') {
    header("Location: ../View/Login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['User_id'];
    $avaId = $_POST['Ava_id'] ?? null;

    if (!$avaId) {
        echo "<script>
            alert('Por favor, informe o ID (Ava_id) da disponibilidade a ser excluída!');
            window.location='../View/funcionario/Disponibilidade.php';
        </script>";
        exit;
    }

    // Buscar o Emp_id do funcionário logado
    $stmtEmp = $pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
    $stmtEmp->execute([$userId]);
    $empId = $stmtEmp->fetchColumn();

    if (!$empId) {
        echo "<script>
            alert('Funcionário não encontrado.');
            window.location='../View/funcionario/Disponibilidade.php';
        </script>";
        exit;
    }

    $availabilityModel = new Availability($pdo);
    $sucesso = $availabilityModel->excluirPorId($avaId, $empId);

    if ($sucesso) {
        echo "<script>
            alert('Disponibilidade excluída com sucesso!');
            window.location='../View/funcionario/Disponibilidade.php';
        </script>";
    } else {
        echo "<script>
            alert('Erro ao excluir a disponibilidade!');
            window.location='../View/funcionario/Disponibilidade.php';
        </script>";
    }
} else {
    header("Location: ../View/funcionario/Disponibilidade.php");
}
?>
