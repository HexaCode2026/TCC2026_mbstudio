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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    Session::validarCsrf();

    $name = trim($_POST['nome'] ?? '');
    $description = trim($_POST['descricao'] ?? '');
    $price = (float)str_replace(',', '.', (string)($_POST['preco'] ?? 0));
    $duration = (int)($_POST['duracao'] ?? 0);
    $employee_ids = is_array($_POST['funcionarios'] ?? null) ? $_POST['funcionarios'] : [];

    if ($name === '' || $price <= 0 || $duration <= 0) {
        Session::setFlash('erro', 'Informe um nome válido, preço maior que zero e duração maior que zero.');
        header("Location: ../View/admin/Servicos.php");
        exit;
    }

    // Upload Seguro de Imagem (Allowlist + MIME Magic Bytes + Verificação de Imagem)
    $image = null;
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
            Session::setFlash('erro', 'Erro durante o envio da imagem. Tente novamente.');
            header("Location: ../View/admin/Servicos.php");
            exit;
        }

        // 1. Limite de tamanho: máximo 5MB
        if ($_FILES['imagem']['size'] > 5 * 1024 * 1024) {
            Session::setFlash('erro', 'A imagem excede o tamanho máximo permitido de 5MB.');
            header("Location: ../View/admin/Servicos.php");
            exit;
        }

        // 2. Validação da extensão original em lista branca
        $extOriginal = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $extPermitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];
        if (!in_array($extOriginal, $extPermitidas, true)) {
            Session::setFlash('erro', 'Formato de arquivo inválido! Envie apenas JPG, PNG, WebP, GIF ou AVIF.');
            header("Location: ../View/admin/Servicos.php");
            exit;
        }

        // 3. Validação do MIME Type real via finfo (Magic Bytes)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($_FILES['imagem']['tmp_name']);
        $mapaMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            'image/avif' => 'avif'
        ];

        if (!array_key_exists($mimeReal, $mapaMimes)) {
            Session::setFlash('erro', 'O conteúdo do arquivo não corresponde a uma imagem válida.');
            header("Location: ../View/admin/Servicos.php");
            exit;
        }

        // 4. Verificação estrutural de imagem
        if ($mimeReal !== 'image/avif' && !@getimagesize($_FILES['imagem']['tmp_name'])) {
            Session::setFlash('erro', 'Arquivo de imagem corrompido ou inválido.');
            header("Location: ../View/admin/Servicos.php");
            exit;
        }

        $extSegura = $mapaMimes[$mimeReal];
        $fileName = "ser_" . bin2hex(random_bytes(8)) . "." . $extSegura;

        $uploadDir = __DIR__ . "/../assets/img/services/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uploadPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $uploadPath)) {
            $image = "assets/img/services/" . $fileName;
        }
    }

    $serviceModel = new Service($pdo);
    $success = $serviceModel->store($name, $description, $price, $duration, $image, $employee_ids);

    if ($success) {
        Session::setFlash('sucesso', 'Serviço (Anúncio) criado com sucesso!');
    } else {
        Session::setFlash('erro', 'Erro ao criar o serviço!');
    }
    header("Location: ../View/admin/Servicos.php");
    exit;
} else {
    header("Location: ../View/admin/Servicos.php");
}
?>
