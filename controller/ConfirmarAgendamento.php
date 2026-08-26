<?php
require_once "../config/conexao.php";
require_once "../core/Session.php";
require_once "../model/Client.php";
require_once "../model/Appointment.php";

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

        // 1. Obter ou criar o ID do Cliente
        $clientModel = new Client($pdo);
        $cli_id = $clientModel->getOrCreateClientId($userId);

        // 2. Verificar conflito de horário
        $appointmentModel = new Appointment($pdo);
        $conflito = $appointmentModel->verificarConflitoHorario($emp_id, $data, $hora_inicio, $hora_fim);
        
        if ($conflito) {
            $pdo->rollBack();
            echo "<script>alert('Desculpe, este horário acabou de ser reservado. Por favor, escolha outro.'); window.location.href='../View/cliente/Horarios.php?Ser_id=$ser_id&data=$data';</script>";
            exit;
        }

        // 3. Inserir o agendamento
        $appointmentModel->criarAgendamento($cli_id, $emp_id, $ser_id, $data, $hora_inicio, $hora_fim);

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
