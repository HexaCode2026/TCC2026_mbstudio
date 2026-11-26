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
    $status = $_POST['Ava_status'] ?? null;
    $date = $_POST['Ava_date'] ?? null;
    $start = $_POST['Ava_start'] ?? null;
    $end = $_POST['Ava_end'] ?? null;

    if (!$avaId) {
        Session::setFlash('erro', 'Por favor, informe o ID (Ava_id) da disponibilidade a ser editada!');
        header("Location: ../View/funcionario/Disponibilidade.php");
        exit;
    }

    if ($date) {
        $hoje = date('Y-m-d');
        if ($date < $hoje) {
            Session::setFlash('erro', 'A data não pode ser anterior a hoje!');
            header("Location: ../View/funcionario/Disponibilidade.php");
            exit;
        }
    }

    if ($start && $end) {
        if ($start < '08:00' || $end > '19:00' || $start >= $end) {
            Session::setFlash('erro', 'Os horários devem estar entre 08:00 e 19:00 e o início deve ser menor que o término.');
            header("Location: ../View/funcionario/Disponibilidade.php");
            exit;
        }
    }

    // Buscar o Emp_id do funcionário logado
    $stmtEmp = $pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
    $stmtEmp->execute([$userId]);
    $empId = $stmtEmp->fetchColumn();

    if (!$empId) {
        Session::setFlash('erro', 'Funcionário não encontrado!');
        header("Location: ../View/funcionario/Disponibilidade.php");
        exit;
    }

    $availabilityModel = new Availability($pdo);
    
    if (is_array($avaId)) {
        $sucesso = true;
        foreach ($avaId as $id) {
            $atualizou = $availabilityModel->editarPorId($id, $empId, $status, $date, $start, $end);
            if (!$atualizou) $sucesso = false;
        }
    } else {
        $sucesso = $availabilityModel->editarPorId($avaId, $empId, $status, $date, $start, $end);
    }

    if ($sucesso) {
        Session::setFlash('sucesso', 'Disponibilidade atualizada com sucesso!');
    } else {
        Session::setFlash('erro', 'Aviso: Algumas atualizações falharam.');
    }
    header("Location: ../View/funcionario/Disponibilidade.php");
    exit;
} else {
    header("Location: ../View/funcionario/Disponibilidade.php");
}
?>
