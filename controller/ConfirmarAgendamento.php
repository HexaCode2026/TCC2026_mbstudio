<?php
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/Appointment.php";

Session::iniciar();

// APENAS Administradores ou Funcionários podem confirmar um agendamento.
if (!isset($_SESSION['User_id']) || !in_array($_SESSION['User_perm'] ?? '', ['A', 'F'], true)) {
    header("Location: ../Index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Session::validarCsrf();

    $appoId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $userId = (int)$_SESSION['User_id'];
    $userPerm = $_SESSION['User_perm'];

    $redirect = ($userPerm === 'A') ? "../View/admin/Agenda.php" : "../View/funcionario/Agenda.php";

    if (!$appoId || $appoId <= 0) {
        header("Location: $redirect?erro=id_invalido");
        exit;
    }

    $appointmentModel = new Appointment($pdo);

    // Verificação da máquina de estados: Só pode confirmar se estiver Pendente
    $currentAppo = $appointmentModel->buscarPorId($appoId);
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

    $success = $appointmentModel->atualizarStatus($appoId, $empId, 'Confirmado');

    if ($success) {
        header("Location: $redirect?sucesso=agendamento_confirmado");
    } else {
        header("Location: $redirect?erro=falha_confirmacao_permissao");
    }
} else {
    header("Location: ../Index.php");
}
?>