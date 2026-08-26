<?php
session_start();
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/EmployeeBlock.php";

// Apenas Funcionário
if (!isset($_SESSION['User_id']) || $_SESSION['User_perm'] !== 'F') {
    header("Location: ../Index.php");
    exit;
}

$blockId = $_GET['id'] ?? null;
$userId = $_SESSION['User_id'];

if (!$blockId) {
    header("Location: ../View/funcionario/Bloqueios.php?erro=id_invalido");
    exit;
}

// Buscar Emp_id do funcionário logado
$stmtEmp = $pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
$stmtEmp->execute([$userId]);
$empId = $stmtEmp->fetchColumn();

if (!$empId) {
    header("Location: ../View/funcionario/Bloqueios.php?erro=funcionario_nao_encontrado");
    exit;
}

$blockModel = new EmployeeBlock($pdo);

// Passamos o $empId para garantir que o funcionário só consiga excluir os próprios bloqueios
$success = $blockModel->destroy($blockId, $empId);

if ($success) {
    header("Location: ../View/funcionario/Bloqueios.php?sucesso=excluido");
} else {
    header("Location: ../View/funcionario/Bloqueios.php?erro=falha_exclusao");
}
?>
