<?php
session_start();
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/Appointment.php";

if (!isset($_SESSION['User_id']) || !isset($_SESSION['User_perm'])) {
    header("Location: ../Index.php");
    exit;
}

$userId = $_SESSION['User_id'];
$userPerm = $_SESSION['User_perm'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $appoId = $_POST['appo_id'] ?? null;
    $cancelReason = trim($_POST['cancel_reason'] ?? 'Desistência');

    if (!$appoId) {
        header("Location: ../Index.php?erro=id_invalido");
        exit;
    }

    $appointmentModel = new Appointment($pdo);
    
    // Verificação da máquina de estados: Só pode cancelar se estiver Pendente ou Confirmado
    $currentAppo = $appointmentModel->buscarPorId($appoId);
    if (!$currentAppo || ($currentAppo['Appo_status'] !== 'Pendente' && $currentAppo['Appo_status'] !== 'Confirmado')) {
        header("Location: ../Index.php?erro=status_invalido_para_cancelamento");
        exit;
    }

    $empId = null;
    $cliId = null;
    $cancelBy = '';
    $redirectUrl = '';

    if ($userPerm === 'C') {
        // Cliente cancelando
        $stmtCli = $pdo->prepare("SELECT Cli_id FROM clients WHERE User_id = ?");
        $stmtCli->execute([$userId]);
        $cliId = $stmtCli->fetchColumn();
        
        $cancelBy = 'Cliente';
        $status = 'Cancelado pelo Cliente';
        $redirectUrl = "../View/cliente/MeusAgendamentos.php";
        
    } elseif ($userPerm === 'F') {
        // Funcionário cancelando
        $stmtEmp = $pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
        $stmtEmp->execute([$userId]);
        $empId = $stmtEmp->fetchColumn();
        
        $cancelBy = 'Funcionario';
        $status = 'Cancelado pelo Funcionario';
        $redirectUrl = "../View/funcionario/Agenda.php";
        
    } elseif ($userPerm === 'A') {
        // Admin cancelando
        $cancelBy = 'Administrador';
        $status = 'Cancelado pelo Administrador';
        $redirectUrl = "../View/admin/Agenda.php";
    }

    $success = $appointmentModel->atualizarStatus($appoId, $empId, $status, $cancelBy, $cancelReason, $cliId);

    if ($success) {
        header("Location: $redirectUrl?sucesso=agendamento_cancelado");
    } else {
        header("Location: $redirectUrl?erro=falha_cancelamento_permissao");
    }
} else {
    header("Location: ../Index.php");
}
?>
