<?php
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
    $date = $_POST['Dis_date'] ?? null;
    $horarios = $_POST['horarios'] ?? [];

    if (!$date || empty($horarios)) {
        echo "<script>
            alert('Por favor, preencha a data e defina os horários com status.');
            window.location='../View/funcionario/Disponibilidade.php';
        </script>";
        exit;
    }

    // Buscar ou garantir o Emp_id do funcionário logado
    $stmtEmp = $pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
    $stmtEmp->execute([$userId]);
    $empId = $stmtEmp->fetchColumn();

    if (!$empId) {
        $insertEmp = $pdo->prepare("INSERT INTO employees (User_id) VALUES (?)");
        $insertEmp->execute([$userId]);
        $empId = $pdo->lastInsertId();
    }

    $availabilityModel = new Availability($pdo);
    $sucesso = $availabilityModel->salvarDisponibilidades($empId, $date, $horarios);

    if ($sucesso) {
        echo "<script>
            alert('Disponibilidade salva com sucesso!');
            window.location='../View/funcionario/Disponibilidade.php';
        </script>";
    } else {
        echo "<script>
            alert('Erro ao salvar a disponibilidade!');
            window.location='../View/funcionario/Disponibilidade.php';
        </script>";
    }
} else {
    header("Location: ../View/funcionario/Disponibilidade.php");
}
?>
