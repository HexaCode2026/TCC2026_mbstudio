<?php
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../config/conexao.php";

Session::iniciar();

// Apenas Clientes
if (!isset($_SESSION['User_id']) || ($_SESSION['User_perm'] ?? '') !== 'C') {
    header("Location: ../Index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Session::validarCsrf();

    $ser_id = filter_input(INPUT_POST, 'ser_id', FILTER_VALIDATE_INT);
    $emp_id = filter_input(INPUT_POST, 'emp_id', FILTER_VALIDATE_INT);
    $data   = trim($_POST['data'] ?? '');
    $hora   = trim($_POST['hora'] ?? '');
    $observacao = trim($_POST['observacao'] ?? '');

    if (!$ser_id || !$emp_id || !$data || !$hora) {
        header("Location: ../View/cliente/Servicos.php?erro=dados_invalidos");
        exit;
    }

    // Validação estrita do formato de data (Y-m-d) e hora (H:i ou H:i:s)
    $dtObj = DateTime::createFromFormat('Y-m-d', $data);
    if (!$dtObj || $dtObj->format('Y-m-d') !== $data || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) {
        header("Location: ../View/cliente/Servicos.php?erro=dados_invalidos");
        exit;
    }

    // Validação de Segurança 1: Data não pode ser no passado
    if ($data < date('Y-m-d')) {
        header("Location: ../View/cliente/Servicos.php?erro=data_passada");
        exit;
    }

    $userId = (int)$_SESSION['User_id'];

    try {
        // Inicia Transação ACID com bloqueio pessimista (FOR UPDATE) para prevenir Race Condition / Overbooking
        $pdo->beginTransaction();

        // 1. Obter Cli_id correspondente
        $stmtCli = $pdo->prepare("SELECT Cli_id FROM clients WHERE User_id = ? FOR UPDATE");
        $stmtCli->execute([$userId]);
        $cli_id = $stmtCli->fetchColumn();

        if (!$cli_id) {
            $stmtInsert = $pdo->prepare("INSERT INTO clients (User_id) VALUES (?)");
            $stmtInsert->execute([$userId]);
            $cli_id = $pdo->lastInsertId();
        }

        // 2. Calcular Appo_end com base na duração do serviço
        $stmtSer = $pdo->prepare("SELECT Ser_duration FROM services WHERE Ser_id = ? AND Ser_active = 1");
        $stmtSer->execute([$ser_id]);
        $duracao = (int)$stmtSer->fetchColumn();

        if ($duracao > 0) {
            $fim = date('H:i:s', strtotime("+$duracao minutes", strtotime($hora)));
        } else {
            $fim = date('H:i:s', strtotime("+1 hour", strtotime($hora)));
        }

        // Validação de Segurança 2: Evitar Overbooking com Agendamentos Existentes (Lock FOR UPDATE)
        $sqlCheckAppo = "SELECT Appo_id FROM appointments 
                         WHERE Emp_id = ? AND Appo_date = ? 
                         AND Appo_status NOT LIKE 'Cancelado%' 
                         AND (Appo_start < ? AND Appo_end > ?)
                         FOR UPDATE";
        $stmtCheckAppo = $pdo->prepare($sqlCheckAppo);
        $stmtCheckAppo->execute([$emp_id, $data, $fim, $hora]);
        if ($stmtCheckAppo->rowCount() > 0) {
            $pdo->rollBack();
            header("Location: ../View/cliente/Servicos.php?erro=horario_indisponivel");
            exit;
        }

        // Validação de Segurança 3: Evitar Overbooking com Bloqueios/Pausas do Funcionário (Lock FOR UPDATE)
        $sqlCheckBlock = "SELECT Block_id FROM employee_blocks 
                          WHERE Emp_id = ? AND Block_date = ? 
                          AND (Block_start < ? AND Block_end > ?)
                          FOR UPDATE";
        $stmtCheckBlock = $pdo->prepare($sqlCheckBlock);
        $stmtCheckBlock->execute([$emp_id, $data, $fim, $hora]);
        if ($stmtCheckBlock->rowCount() > 0) {
            $pdo->rollBack();
            header("Location: ../View/cliente/Servicos.php?erro=horario_bloqueado");
            exit;
        }

        // 3. Inserir agendamento com status inicial "Pendente"
        $sql = "INSERT INTO appointments (Cli_id, Emp_id, Ser_id, Appo_date, Appo_start, Appo_end, Appo_status, Appo_observation)
                VALUES (?, ?, ?, ?, ?, ?, 'Pendente', ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cli_id, $emp_id, $ser_id, $data, $hora, $fim, $observacao]);

        $pdo->commit();

        // 4. Redirecionar para o painel de visualização do cliente
        header("Location: ../View/cliente/MeusAgendamentos.php?sucesso=agendado");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: ../View/cliente/Servicos.php?erro=falha_agendamento");
        exit;
    }
} else {
    header("Location: ../Index.php");
}
?>
