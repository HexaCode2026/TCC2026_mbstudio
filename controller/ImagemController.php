<?php
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../model/Imagem.php";

Session::iniciar();

// Proteção exclusiva para Administrador
if (!isset($_SESSION['User_id']) || ($_SESSION['User_perm'] ?? '') !== 'A') {
    header("Location: ../Index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    Session::validarCsrf();

    $acao = $_POST['acao'] ?? '';
    $imagemModel = new Imagem($pdo);

    // Função auxiliar para upload estritamente validado de imagem (Allowlist + MIME Magic Bytes + Limite 5MB)
    $processarUpload = function ($inputName, $subFolder = 'carousel', $prefix = 'img_') {
        if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // 1. Limite de tamanho: máximo 5MB
        if ($_FILES[$inputName]['size'] > 5 * 1024 * 1024) {
            return false;
        }

        // 2. Validação da extensão em lista branca
        $ext = strtolower(pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];
        if (!in_array($ext, $permitidas, true)) {
            return false;
        }

        // 3. Validação de MIME real via finfo (Magic Bytes)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($_FILES[$inputName]['tmp_name']);
        $mapaMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            'image/avif' => 'avif'
        ];

        if (!array_key_exists($mimeReal, $mapaMimes)) {
            return false;
        }

        // 4. Verificação estrutural de imagem
        if ($mimeReal !== 'image/avif' && !@getimagesize($_FILES[$inputName]['tmp_name'])) {
            return false;
        }

        $subPastasPermitidas = ['carousel', 'services'];
        if (!in_array($subFolder, $subPastasPermitidas, true)) {
            $subFolder = 'carousel';
        }

        $extSegura = $mapaMimes[$mimeReal];
        $fileName = $prefix . bin2hex(random_bytes(8)) . "." . $extSegura;
        $uploadDir = __DIR__ . "/../assets/img/" . $subFolder . "/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uploadPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $uploadPath)) {
            return "assets/img/" . $subFolder . "/" . $fileName;
        }
        return false;
    };

    if ($acao === 'adicionar') {
        $titulo = trim($_POST['titulo'] ?? '');
        $subtitulo = trim($_POST['subtitulo'] ?? '');
        $ordem = (int)($_POST['ordem'] ?? 1);
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        $caminho = $processarUpload('imagem', 'carousel', 'hero_');
        if ($caminho === false) {
            header("Location: ../View/admin/Imagens.php?msg=formato_invalido");
            exit;
        }
        if (!$caminho) {
            header("Location: ../View/admin/Imagens.php?msg=sem_arquivo");
            exit;
        }

        if ($titulo === '') {
            $titulo = 'Destaque MB Studio';
        }

        $imagemModel->adicionar($titulo, $subtitulo, $caminho, 'hero', $ordem, $ativo);
        header("Location: ../View/admin/Imagens.php?msg=adicionada");
        exit;
    }

    if ($acao === 'editar') {
        $id = (int)($_POST['Img_id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $subtitulo = trim($_POST['subtitulo'] ?? '');
        $ordem = (int)($_POST['ordem'] ?? 1);
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($id > 0) {
            $novoCaminho = $processarUpload('imagem', 'carousel', 'hero_');
            if ($novoCaminho === false) {
                header("Location: ../View/admin/Imagens.php?msg=formato_invalido");
                exit;
            }
            $imagemModel->atualizar($id, $titulo ?: 'Destaque MB Studio', $subtitulo, $novoCaminho, $ordem, $ativo);
            header("Location: ../View/admin/Imagens.php?msg=atualizada");
            exit;
        }
    }

    if ($acao === 'status') {
        $id = (int)($_POST['Img_id'] ?? 0);
        $novoStatus = (int)($_POST['novo_status'] ?? 0) === 1 ? 1 : 0;
        if ($id > 0) {
            $imagemModel->alternarStatus($id, $novoStatus);
        }
        header("Location: ../View/admin/Imagens.php?msg=status");
        exit;
    }

    if ($acao === 'excluir') {
        $id = (int)($_POST['Img_id'] ?? 0);
        if ($id > 0) {
            $imagemModel->excluir($id);
        }
        header("Location: ../View/admin/Imagens.php?msg=excluida");
        exit;
    }

    if ($acao === 'atualizar_servico') {
        $serId = (int)($_POST['Ser_id'] ?? 0);
        if ($serId > 0) {
            $novoCaminho = $processarUpload('imagem_servico', 'services', 'ser_');
            if ($novoCaminho === false) {
                header("Location: ../View/admin/Imagens.php?msg=formato_invalido#galeria-servicos");
                exit;
            }
            if ($novoCaminho) {
                $imagemModel->atualizarImagemServico($serId, $novoCaminho);
                header("Location: ../View/admin/Imagens.php?msg=servico_atualizado#galeria-servicos");
                exit;
            }
        }
        header("Location: ../View/admin/Imagens.php?msg=sem_arquivo#galeria-servicos");
        exit;
    }
}

header("Location: ../View/admin/Imagens.php");
exit;
