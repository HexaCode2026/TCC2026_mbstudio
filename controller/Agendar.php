<?php
session_start();
require_once __DIR__ . "/../config/conexao.php";

// Apenas Clientes
if (!isset($_SESSION['User_id']) || $_SESSION['User_perm'] !== 'C') {
    header("Location: ../Index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ser_id = $_POST['ser_id'] ?? null;
    $emp_id = $_POST['emp_id'] ?? null;
    $data = $_POST['data'] ?? null;
    $hora = $_POST['hora'] ?? null;
    $observacao = $_POST['observacao'] ?? '';

    if (!$ser_id || !$emp_id || !$data || !$hora) {
        header("Location: ../View/cliente/Servicos.php?erro=dados_invalidos");
        exit;
    }

    $userId = $_SESSION['User_id'];

    // 1. Obter Cli_id correspondente
    $stmtCli = $pdo->prepare("SELECT Cli_id FROM clients WHERE User_id = ?");
    $stmtCli->execute([$userId]);
    $cli_id = $stmtCli->fetchColumn();

    // Se por acaso o cliente não estiver na tabela, inserimos (fallback de segurança)
    if (!$cli_id) {
        $stmtInsert = $pdo->prepare("INSERT INTO clients (User_id) VALUES (?)");
        $stmtInsert->execute([$userId]);
        $cli_id = $pdo->lastInsertId();
    }

    // 2. Calcular Appo_end com base na duração do serviço
    $stmtSer = $pdo->prepare("SELECT Ser_duration FROM services WHERE Ser_id = ?");
    $stmtSer->execute([$ser_id]);
    $duracao = $stmtSer->fetchColumn();

    if ($duracao) {
        $fim = date('H:i:s', strtotime("+$duracao minutes", strtotime($hora)));
    } else {
        $fim = date('H:i:s', strtotime("+1 hour", strtotime($hora)));
    }

    // Validação de Segurança 1: Data não pode ser no passado
    if ($data < date('Y-m-d')) {
        header("Location: ../View/cliente/Servicos.php?erro=data_passada");
        exit;
    }

    // Validação de Segurança 2: Evitar Overbooking com Agendamentos Existentes
    $sqlCheckAppo = "SELECT Appo_id FROM appointments 
                     WHERE Emp_id = ? AND Appo_date = ? 
                     AND Appo_status NOT LIKE 'Cancelado%' 
                     AND (Appo_start < ? AND Appo_end > ?)";
    $stmtCheckAppo = $pdo->prepare($sqlCheckAppo);
    $stmtCheckAppo->execute([$emp_id, $data, $fim, $hora]);
    if ($stmtCheckAppo->rowCount() > 0) {
        header("Location: ../View/cliente/Servicos.php?erro=horario_indisponivel");
        exit;
    }

    // Validação de Segurança 3: Evitar Overbooking com Bloqueios/Pausas do Funcionário
    $sqlCheckBlock = "SELECT Block_id FROM employee_blocks 
                      WHERE Emp_id = ? AND Block_date = ? 
                      AND (Block_start < ? AND Block_end > ?)";
    $stmtCheckBlock = $pdo->prepare($sqlCheckBlock);
    $stmtCheckBlock->execute([$emp_id, $data, $fim, $hora]);
    if ($stmtCheckBlock->rowCount() > 0) {
        header("Location: ../View/cliente/Servicos.php?erro=horario_bloqueado");
        exit;
    }

    // 3. Inserir agendamento com status inicial "Pendente"
    // O Cliente só Pede, o Funcionário Confirma. (A inconsistência resolvida).
    $sql = "INSERT INTO appointments (Cli_id, Emp_id, Ser_id, Appo_date, Appo_start, Appo_end, Appo_status, Appo_observation)
            VALUES (?, ?, ?, ?, ?, ?, 'Pendente', ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cli_id, $emp_id, $ser_id, $data, $hora, $fim, $observacao]);

    // 4. Redirecionar para o painel de visualização do cliente
    header("Location: ../View/cliente/MeusAgendamentos.php?sucesso=agendado");
    exit;
} else {
    header("Location: ../Index.php");
}
?>
