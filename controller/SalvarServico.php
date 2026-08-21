<?php

session_start();
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/Service.php";

// Apenas Administrador (Correção do nome da variável de sessão: User_id e User_perm)
if (!isset($_SESSION['User_id']) || $_SESSION['User_perm'] !== 'A') {
    header("Location: ../Index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST['nome'] ?? '';
    $description = $_POST['descricao'] ?? '';
    $price = $_POST['preco'] ?? 0;
    $duration = $_POST['duracao'] ?? 0;
    $employee_ids = $_POST['funcionarios'] ?? []; // array de IDs

    // Upload de Imagem
    $image = null;
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid("ser_") . "." . $ext;
        
        $uploadDir = __DIR__ . "/../assets/img/services/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $uploadPath)) {
            $image = "assets/img/services/" . $fileName; // Salva o caminho relativo
        }
    }

    $serviceModel = new Service($pdo);

    $success = $serviceModel->store($name, $description, $price, $duration, $image, $employee_ids);

    if ($success) {
        echo "<script>
            alert('Serviço (Anúncio) criado com sucesso!');
            window.location='../View/admin/Servicos.php';
        </script>";
    } else {
        echo "<script>
            alert('Erro ao criar o serviço!');
            window.location='../View/admin/Servicos.php';
        </script>";
    }

} else {
    header("Location: ../View/admin/Servicos.php");
}
?>
