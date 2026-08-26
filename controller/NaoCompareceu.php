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

    if (!$appoId) {
        header("Location: ../Index.php?erro=id_invalido");
        exit;
    }

    $appointmentModel = new Appointment($pdo);
    
    // Verificação da máquina de estados: Só pode marcar como Não Compareceu se estiver Confirmado
    $currentAppo = $appointmentModel->buscarPorId($appoId);
    $redirect = ($userPerm === 'A') ? "../View/admin/Agenda.php" : "../View/funcionario/Agenda.php";
    
    if (!$currentAppo || $currentAppo['Appo_status'] !== 'Confirmado') {
        header("Location: $redirect?erro=status_invalido_para_falta");
        exit;
    }

    $empId = null;

    if ($userPerm === 'F') {
        $stmtEmp = $pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
        $stmtEmp->execute([$userId]);
        $empId = $stmtEmp->fetchColumn();
    }

    // atualizarStatus(Appo_id, Emp_id, NovoStatus, CancelBy, CancelReason)
    $success = $appointmentModel->atualizarStatus($appoId, $empId, 'Nao Compareceu');

    if ($success) {
        header("Location: $redirect?sucesso=falta_registrada");
    } else {
        header("Location: $redirect?erro=falha_permissao");
    }
} else {
    header("Location: ../Index.php");
}
?>
