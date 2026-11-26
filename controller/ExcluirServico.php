<?php
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/Service.php";

Session::iniciar();

// Apenas Administrador
if (!isset($_SESSION['User_id']) || ($_SESSION['User_perm'] ?? '') !== 'A') {
    header("Location: ../Index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Session::validarCsrf();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
} elseif (isset($_GET['id']) && !empty($_SERVER['HTTP_REFERER'])) {
    // Fallback protegido por Same-Origin Referer para links internos legados
    Session::validarCsrf();
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
} else {
    $id = null;
}

if ($id && $id > 0) {
    $serviceModel = new Service($pdo);
    $success = $serviceModel->destroy($id);

    if ($success) {
        Session::setFlash('sucesso', 'Serviço excluído com sucesso!');
    } else {
        Session::setFlash('erro', 'Não foi possível excluir o serviço! Verifique se existem agendamentos ativos ou concluídos vinculados a ele.');
    }
    header("Location: ../View/admin/Servicos.php");
    exit;
} else {
    header("Location: ../View/admin/Servicos.php");
}
?>
