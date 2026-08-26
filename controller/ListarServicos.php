<?php

session_start();
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/Service.php";

// Apenas Administrador
if (!isset($_SESSION['User_id']) || $_SESSION['User_perm'] !== 'A') {
    header("Location: ../Index.php");
    exit;
}

// Instancia o modelo e busca a lista
$serviceModel = new Service($pdo);
$servicos = $serviceModel->index();

// Como este arquivo seria incluído em uma View ou consumido via AJAX,
// podemos apenas disponibilizar a variável $servicos para o Frontend,
// ou retornar em formato JSON caso seja uma chamada fetch.

// Se a chamada esperar JSON (ex: AJAX/Fetch API):
if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    header('Content-Type: application/json');
    echo json_encode($servicos);
    exit;
}

// Caso seja incluído no meio do PHP, a variável $servicos fica disponível para uso no loop (foreach)
?>