<?php
date_default_timezone_set('America/Sao_Paulo');
session_start();
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/Availability.php";

// Proteção para Funcionário
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'F') {
    header("Location: ../Index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['User_id'];
    $avaId = $_POST['Ava_id'] ?? null;
    $status = $_POST['Ava_status'] ?? null;
    $date = $_POST['Ava_date'] ?? null;
    $start = $_POST['Ava_start'] ?? null;
    $end = $_POST['Ava_end'] ?? null;

    if (!$avaId) {
        echo "<script>
            alert('Por favor, informe o ID (Ava_id) da disponibilidade a ser editada!');
            window.location='../View/funcionario/Disponibilidade.php';
        </script>";
        exit;
    }

    if ($date) {
        $hoje = date('Y-m-d');
        if ($date < $hoje) {
            echo "<script>
                alert('A data não pode ser anterior a hoje!');
                window.location='../View/funcionario/Disponibilidade.php';
            </script>";
            exit;
        }
    }

    if ($start && $end) {
        if ($start < '08:00' || $end > '19:00' || $start >= $end) {
            echo "<script>
                alert('Os horários devem estar entre 08:00 e 19:00 e o início deve ser menor que o término.');
                window.location='../View/funcionario/Disponibilidade.php';
            </script>";
            exit;
        }
    }

    // Buscar o Emp_id do funcionário logado
    $stmtEmp = $pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
    $stmtEmp->execute([$userId]);
    $empId = $stmtEmp->fetchColumn();

    if (!$empId) {
        echo "<script>
            alert('Funcionário não encontrado!');
            window.location='../View/funcionario/Disponibilidade.php';
        </script>";
        exit;
    }

    $availabilityModel = new Availability($pdo);
    
    if (is_array($avaId)) {
        $sucesso = true;
        foreach ($avaId as $id) {
            $atualizou = $availabilityModel->editarPorId($id, $empId, $status, $date, $start, $end);
            if (!$atualizou) $sucesso = false;
        }
    } else {
        $sucesso = $availabilityModel->editarPorId($avaId, $empId, $status, $date, $start, $end);
    }

    if ($sucesso) {
        echo "<script>
            alert('Disponibilidade atualizada com sucesso!');
            window.location='../View/funcionario/Disponibilidade.php';
        </script>";
    } else {
        echo "<script>
            alert('Aviso: Algumas atualizações falharam.');
            window.location='../View/funcionario/Disponibilidade.php';
        </script>";
    }
} else {
    header("Location: ../View/funcionario/Disponibilidade.php");
}
?>
