<?php
session_start();
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/Appointment.php";

if (!isset($_SESSION['User_id']) || !in_array($_SESSION['User_perm'], ['A', 'F'])) {
    header("Location: ../Index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $appoId = $_POST['appo_id'] ?? null;
    $userId = $_SESSION['User_id'];
    $userPerm = $_SESSION['User_perm'];

    if (!$appoId) {
        $redirect = ($userPerm === 'A') ? "../View/admin/Agenda.php" : "../View/funcionario/Agenda.php";
        header("Location: $redirect?erro=id_invalido");
        exit;
    }

    $appointmentModel = new Appointment($pdo);
    
    // Verificação da máquina de estados: Só pode finalizar se estiver Em Atendimento
    $currentAppo = $appointmentModel->buscarPorId($appoId);
    $redirect = ($userPerm === 'A') ? "../View/admin/Agenda.php" : "../View/funcionario/Agenda.php";
    
    if (!$currentAppo || $currentAppo['Appo_status'] !== 'Em Atendimento') {
        header("Location: $redirect?erro=status_invalido_para_concluir");
        exit;
    }

    $empId = null;

    if ($userPerm === 'F') {
        $stmtEmp = $pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
        $stmtEmp->execute([$userId]);
        $empId = $stmtEmp->fetchColumn();
    }

    $success = $appointmentModel->atualizarStatus($appoId, $empId, 'Concluido');

    $redirect = ($userPerm === 'A') ? "../View/admin/Agenda.php" : "../View/funcionario/Agenda.php";

    if ($success) {
        header("Location: $redirect?sucesso=atendimento_concluido");
    } else {
        header("Location: $redirect?erro=falha_permissao");
    }
} else {
    header("Location: ../Index.php");
}
?>
