<?php
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/Appointment.php";

Session::iniciar();

if (!isset($_SESSION['User_id']) || !in_array($_SESSION['User_perm'], ['A', 'F'])) {
    header("Location: ../Index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $redirect = ($_SESSION['User_perm'] === 'A') ? "../View/admin/Agenda.php" : "../View/funcionario/Agenda.php";
    header("Location: $redirect");
    exit;
}

$appoId = filter_input(INPUT_POST, 'appo_id', FILTER_VALIDATE_INT);
$paymentMethod = $_POST['payment_method'] ?? null;
$userId = $_SESSION['User_id'];
$userPerm = $_SESSION['User_perm'];

$redirect = ($userPerm === 'A') ? "../View/admin/Agenda.php" : "../View/funcionario/Agenda.php";

if (!$appoId || $appoId <= 0 || !$paymentMethod) {
    header("Location: $redirect?erro=dados_incompletos");
    exit;
}

// Validação estrita do método de pagamento (os mesmos do Banco.sql)
$metodosPermitidos = ['Pix', 'Dinheiro', 'Cartão'];
if (!in_array($paymentMethod, $metodosPermitidos, true)) {
    header("Location: $redirect?erro=pagamento_invalido");
    exit;
}

$appointmentModel = new Appointment($pdo);
$empId = null;

if ($userPerm === 'F') {
    $stmtEmp = $pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
    $stmtEmp->execute([$userId]);
    $empId = $stmtEmp->fetchColumn();
    
    // Funcionário não pode continuar se não tiver Emp_id correspondente
    if (!$empId) {
        header("Location: $redirect?erro=funcionario_nao_encontrado");
        exit;
    }
}

// UPDATE atômico que também funciona como validação
$success = $appointmentModel->finalizarComPagamento($appoId, $paymentMethod, $empId);

if ($success) {
    if (isset($_POST['cli_observation'])) {
        $cliObservation = $_POST['cli_observation'];
        $stmtClient = $pdo->prepare("SELECT Cli_id FROM appointments WHERE Appo_id = ?");
        $stmtClient->execute([$appoId]);
        $cliId = $stmtClient->fetchColumn();
        
        if ($cliId) {
            $stmtUpdateObs = $pdo->prepare("UPDATE clients SET Cli_observation = ? WHERE Cli_id = ?");
            $stmtUpdateObs->execute([$cliObservation, $cliId]);
        }
    }
    header("Location: $redirect?sucesso=atendimento_concluido");
} else {
    header("Location: $redirect?erro=falha_permissao_ou_status");
}
exit;
?>
