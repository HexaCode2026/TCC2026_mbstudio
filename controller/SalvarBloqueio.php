<?php
session_start();
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/EmployeeBlock.php";

// Apenas Funcionário
if (!isset($_SESSION['User_id']) || $_SESSION['User_perm'] !== 'F') {
    header("Location: ../Index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['data'] ?? null;
    $start = $_POST['hora_inicio'] ?? null;
    $end = $_POST['hora_fim'] ?? null;
    $reason = $_POST['motivo'] ?? null;
    $userId = $_SESSION['User_id'];

    if (!$date || !$start || !$end) {
        header("Location: ../View/funcionario/Bloqueios.php?erro=dados_invalidos");
        exit;
    }

    // Validar se hora de fim é maior que hora de início
    if (strtotime($start) >= strtotime($end)) {
        header("Location: ../View/funcionario/Bloqueios.php?erro=horario_invalido");
        exit;
    }

    // Buscar Emp_id do funcionário logado
    $stmtEmp = $pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
    $stmtEmp->execute([$userId]);
    $empId = $stmtEmp->fetchColumn();

    if (!$empId) {
        header("Location: ../View/funcionario/Bloqueios.php?erro=funcionario_nao_encontrado");
        exit;
    }

    // Validar se já existe um agendamento conflitante neste horário
    $sqlCheckAppo = "SELECT Appo_id FROM appointments 
                 WHERE Emp_id = ? 
                 AND Appo_date = ? 
                 AND Appo_status NOT LIKE 'Cancelado%' 
                 AND (Appo_start < ? AND Appo_end > ?)";
    $stmtCheckAppo = $pdo->prepare($sqlCheckAppo);
    $stmtCheckAppo->execute([$empId, $date, $end, $start]);
    if ($stmtCheckAppo->rowCount() > 0) {
        header("Location: ../View/funcionario/Bloqueios.php?erro=conflito_agendamento");
        exit;
    }
    
    // Validar se já existe um bloqueio conflitante neste mesmo horário
    $sqlCheckBlock = "SELECT Block_id FROM employee_blocks 
                 WHERE Emp_id = ? 
                 AND Block_date = ? 
                 AND (Block_start < ? AND Block_end > ?)";
    $stmtCheckBlock = $pdo->prepare($sqlCheckBlock);
    $stmtCheckBlock->execute([$empId, $date, $end, $start]);
    if ($stmtCheckBlock->rowCount() > 0) {
        header("Location: ../View/funcionario/Bloqueios.php?erro=conflito_bloqueio");
        exit;
    }

    $blockModel = new EmployeeBlock($pdo);
    $success = $blockModel->store($empId, $date, $start, $end, $reason);

    if ($success) {
        header("Location: ../View/funcionario/Bloqueios.php?sucesso=salvo");
    } else {
        header("Location: ../View/funcionario/Bloqueios.php?erro=falha_banco");
    }
} else {
    header("Location: ../Index.php");
}
?>
