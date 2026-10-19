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

    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['nome'] ?? '');
    $description = trim($_POST['descricao'] ?? '');
    $price = $_POST['preco'] ?? 0;
    $duration = (int)($_POST['duracao'] ?? 0);
    $active = (int)($_POST['ativo'] ?? 1);
    $employee_ids = is_array($_POST['funcionarios'] ?? null) ? $_POST['funcionarios'] : [];

    // Sanitiza caminho da imagem atual para evitar Path Traversal
    $imagemAtual = trim($_POST['imagem_atual'] ?? '');
    if ($imagemAtual !== '' && (strpos($imagemAtual, '..') !== false || strpos($imagemAtual, 'assets/img/') !== 0)) {
        $imagemAtual = null;
    }
    $image = $imagemAtual ?: null;

    // Upload Seguro de Imagem Nova (se houver)
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
            echo "<script>
                alert('Erro durante o envio da imagem. Tente novamente.');
                window.location='../View/admin/Servicos.php';
            </script>";
            exit;
        }

        // 1. Limite de tamanho: máximo 5MB
        if ($_FILES['imagem']['size'] > 5 * 1024 * 1024) {
            echo "<script>
                alert('A imagem excede o tamanho máximo permitido de 5MB.');
                window.location='../View/admin/Servicos.php';
            </script>";
            exit;
        }

        // 2. Validação da extensão original em lista branca
        $extOriginal = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $extPermitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];
        if (!in_array($extOriginal, $extPermitidas, true)) {
            echo "<script>
                alert('Formato de arquivo inválido! Envie apenas JPG, PNG, WebP, GIF ou AVIF.');
                window.location='../View/admin/Servicos.php';
            </script>";
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
            echo "<script>
                alert('O conteúdo do arquivo não corresponde a uma imagem válida.');
                window.location='../View/admin/Servicos.php';
            </script>";
            exit;
        }

        // 4. Verificação estrutural de imagem
        if ($mimeReal !== 'image/avif' && !@getimagesize($_FILES['imagem']['tmp_name'])) {
            echo "<script>
                alert('Arquivo de imagem corrompido ou inválido.');
                window.location='../View/admin/Servicos.php';
            </script>";
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
