<?php
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../config/conexao.php";

Session::iniciar();

if (!isset($_SESSION['User_id']) || !in_array($_SESSION['User_perm'] ?? '', ['F', 'A'], true)) {
    header("Location: " . Session::getBasePath() . "/Index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../View/funcionario/Agenda.php");
    exit;
}

Session::validarCsrf();

$cliId = filter_input(INPUT_POST, 'cli_id', FILTER_VALIDATE_INT);
$obs   = trim((string)($_POST['cli_observation'] ?? ''));
$userId = (int)$_SESSION['User_id'];

if (!$cliId || $cliId <= 0) {
    Session::setFlash('erro', 'Cliente inválido.');
    header("Location: ../View/funcionario/Agenda.php");
    exit;
}

// Garante que o funcionário realmente possui vínculo de agendamento com este cliente (mitigação de IDOR)
$stmtCheck = $pdo->prepare("
    SELECT 1
    FROM appointments a
    INNER JOIN employees e ON a.Emp_id = e.Emp_id
    WHERE a.Cli_id = ? AND e.User_id = ?
    LIMIT 1
");
$stmtCheck->execute([$cliId, $userId]);

if (!$stmtCheck->fetchColumn()) {
    Session::setFlash('erro', 'Você não tem permissão para editar observações deste cliente.');
    header("Location: ../View/funcionario/Agenda.php");
    exit;
}

$stmtUpd = $pdo->prepare("UPDATE clients SET Cli_observation = ? WHERE Cli_id = ?");
$stmtUpd->execute([$obs, $cliId]);

Session::setFlash('sucesso', 'Observações do cliente atualizadas com sucesso.');
header("Location: ../View/funcionario/Agenda.php");
exit;
