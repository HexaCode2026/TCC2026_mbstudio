<?php
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/Availability.php";

Session::iniciar();

// Proteção para Funcionário
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'F') {
    header("Location: ../Index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    Session::validarCsrf();
    $userId = $_SESSION['User_id'];
    $avaId = $_POST['Ava_id'] ?? null;

    if (!$avaId) {
        Session::setFlash('erro', 'Por favor, informe o ID (Ava_id) da disponibilidade a ser excluída!');
        header("Location: ../View/funcionario/Disponibilidade.php");
        exit;
    }

    // Buscar o Emp_id do funcionário logado
    $stmtEmp = $pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
    $stmtEmp->execute([$userId]);
    $empId = $stmtEmp->fetchColumn();

    if (!$empId) {
        Session::setFlash('erro', 'Funcionário não encontrado.');
        header("Location: ../View/funcionario/Disponibilidade.php");
        exit;
    }

    $availabilityModel = new Availability($pdo);
    $sucesso = $availabilityModel->excluirPorId($avaId, $empId);

    if ($sucesso) {
        Session::setFlash('sucesso', 'Disponibilidade excluída com sucesso!');
    } else {
        Session::setFlash('erro', 'Erro ao excluir a disponibilidade!');
    }
    header("Location: ../View/funcionario/Disponibilidade.php");
    exit;
} else {
    header("Location: ../View/funcionario/Disponibilidade.php");
}
?>
