<?php
require_once "../config/conexao.php";
require_once "../core/Session.php";

Session::iniciar();

// Proteção para Cliente
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'C') {
    header("Location: ../Index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ser_id = $_POST['Ser_id'] ?? null;
    $data = $_POST['data'] ?? null;
    $emp_id = $_POST['Emp_id'] ?? null;
    $hora_inicio = $_POST['hora_inicio'] ?? null;
    $hora_fim = $_POST['hora_fim'] ?? null;

    if (!$ser_id || !$data || !$emp_id || !$hora_inicio || !$hora_fim) {
        // Redirecionar com erro (simples)
        echo "<script>alert('Dados incompletos.'); window.location.href='../View/cliente/Servicos.php';</script>";
        exit;
    }

    $userId = $_SESSION['User_id'];

    try {
        $pdo->beginTransaction();

        // 1. Buscar o Cli_id correspondente ao usuário logado
        $stmtCli = $pdo->prepare("SELECT Cli_id FROM clients WHERE User_id = ?");
        $stmtCli->execute([$userId]);
        $cliente = $stmtCli->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            // Se o usuário não estiver na tabela clients por algum motivo (ex: erro no cadastro), cria um registro
            $stmtInsertCli = $pdo->prepare("INSERT INTO clients (User_id) VALUES (?)");
            $stmtInsertCli->execute([$userId]);
            $cli_id = $pdo->lastInsertId();
        } else {
            $cli_id = $cliente['Cli_id'];
        }

        // 2. Opcional: Verificar se o horário ainda está disponível para evitar race condition
        $sqlCheck = "SELECT Appo_id FROM appointments 
                     WHERE Emp_id = ? AND Appo_date = ? 
                       AND Appo_status NOT IN ('Cancelado pelo Cliente', 'Cancelado pelo Funcionario', 'Cancelado pelo Administrador', 'Nao Compareceu')
                       AND (
                           (Appo_start <= ? AND Appo_end > ?) OR
                           (Appo_start < ? AND Appo_end >= ?) OR
                           (? <= Appo_start AND ? >= Appo_end)
                       )";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([
            $emp_id, $data, 
            $hora_inicio, $hora_inicio, 
            $hora_fim, $hora_fim, 
            $hora_inicio, $hora_fim
        ]);
        
        if ($stmtCheck->rowCount() > 0) {
            $pdo->rollBack();
            echo "<script>alert('Desculpe, este horário acabou de ser reservado. Por favor, escolha outro.'); window.location.href='../View/cliente/Horarios.php?Ser_id=$ser_id&data=$data';</script>";
            exit;
        }

        // 3. Inserir o agendamento
        $sqlInsert = "INSERT INTO appointments (Cli_id, Emp_id, Ser_id, Appo_date, Appo_start, Appo_end, Appo_status) 
                      VALUES (?, ?, ?, ?, ?, ?, 'Pendente')";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([$cli_id, $emp_id, $ser_id, $data, $hora_inicio, $hora_fim]);

        $pdo->commit();

        echo "<script>alert('Agendamento realizado com sucesso!'); window.location.href='../View/cliente/MeusAgendamentos.php';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Erro ao realizar o agendamento: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    }
} else {
    header("Location: ../View/cliente/Servicos.php");
    exit;
}
