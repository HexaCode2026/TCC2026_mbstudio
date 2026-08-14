<?php

session_start();
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/Service.php";

// Apenas Administrador (Correção do nome da variável de sessão)
if (!isset($_SESSION['User_id']) || $_SESSION['User_perm'] !== 'A') {
    header("Location: ../View/Login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_POST['id'] ?? 0;
    $name = $_POST['nome'] ?? '';
    $description = $_POST['descricao'] ?? '';
    $price = $_POST['preco'] ?? 0;
    $duration = $_POST['duracao'] ?? 0;
    $active = $_POST['ativo'] ?? 1;
    $employee_ids = $_POST['funcionarios'] ?? [];

    $image = $_POST['imagem_atual'] ?? null; // Mantém a imagem atual se não enviar uma nova

    // Upload de Imagem Nova (se houver)
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid("ser_") . "." . $ext;
        
        $uploadDir = __DIR__ . "/../assets/img/services/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $uploadPath)) {
            $image = "assets/img/services/" . $fileName; // Atualiza com a nova imagem
        }
    }

    $serviceModel = new Service($pdo);

    $success = $serviceModel->update($id, $name, $description, $price, $duration, $image, $active, $employee_ids);

    if ($success) {
        echo "<script>
            alert('Serviço atualizado com sucesso!');
            window.location='../View/admin/Servicos.php';
        </script>";
    } else {
        echo "<script>
            alert('Erro ao atualizar serviço!');
            window.location='../View/admin/Servicos.php';
        </script>";
    }

} else {
    header("Location: ../View/admin/Servicos.php");
}
?>
