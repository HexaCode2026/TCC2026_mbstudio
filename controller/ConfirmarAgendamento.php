<?php
session_start();
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/Appointment.php";

// APENAS Administradores ou Funcionários podem confirmar um agendamento.
// Clientes NÃO têm permissão para confirmar seus próprios agendamentos (Regra de Negócio).
if (!isset($_SESSION['User_id']) || !in_array($_SESSION['User_perm'], ['A', 'F'])) {
    header("Location: ../Index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['id'])) {
    $appoId = $_POST['id'] ?? $_GET['id'] ?? null;
    $userId = $_SESSION['User_id'];
    $userPerm = $_SESSION['User_perm'];

    if (!$appoId) {
        $redirect = ($userPerm === 'A') ? "../View/admin/Agenda.php" : "../View/funcionario/Agenda.php";
        header("Location: $redirect?erro=id_invalido");
        exit;
    }

    $appointmentModel = new Appointment($pdo);
    
    // Verificação da máquina de estados: Só pode confirmar se estiver Pendente
    $currentAppo = $appointmentModel->buscarPorId($appoId);
    $redirect = ($userPerm === 'A') ? "../View/admin/Agenda.php" : "../View/funcionario/Agenda.php";
    
    if (!$currentAppo || $currentAppo['Appo_status'] !== 'Pendente') {
        header("Location: $redirect?erro=status_invalido_para_confirmacao");
        exit;
    }

    $empId = null;

    // Se for funcionário, garantir que ele só confirme agendamentos DELE mesmo
    if ($userPerm === 'F') {
        $stmtEmp = $pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
        $stmtEmp->execute([$userId]);
        $empId = $stmtEmp->fetchColumn();

        if (!$empId) {
            header("Location: ../View/funcionario/Agenda.php?erro=funcionario_nao_encontrado");
            exit;
        }
    }
    // Se for Admin ('A'), $empId continua null, permitindo o bypass implementado no model.

    // Tentar atualizar status
    $success = $appointmentModel->atualizarStatus($appoId, $empId, 'Confirmado');

    $redirect = ($userPerm === 'A') ? "../View/admin/Agenda.php" : "../View/funcionario/Agenda.php";

    if ($success) {
        header("Location: $redirect?sucesso=agendamento_confirmado");
    } else {
        header("Location: $redirect?erro=falha_confirmacao_permissao");
    }
} else {
    header("Location: ../Index.php");
}
?>