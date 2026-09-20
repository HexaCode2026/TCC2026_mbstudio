<?php
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/../../'));
$basePath = str_ireplace($docRoot, '', $projectRoot);
?>
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/components/header.css">

<header class="main-header">
    <a href="<?= $basePath ?>/Index.php" class="header-logo-link">
        <img src="<?= $basePath ?>/assets/img/logob.png" alt="Logo MB Studio" class="header-logo-img">
    </a>

    <nav class="header-nav">
        <?php
        $perm = $_SESSION['User_perm'] ?? null;
        if ($perm === 'F'): ?>
            <!-- Menu do Funcionário -->
            <a href="<?= $basePath ?>/View/funcionario/Agenda.php">MINHA AGENDA</a>
            <a href="<?= $basePath ?>/View/funcionario/Disponibilidade.php">MEUS HORÁRIOS</a>
            <a href="<?= $basePath ?>/View/funcionario/Bloqueios.php">PAUSAS/BLOQUEIOS</a>
            <a href="<?= $basePath ?>/View/funcionario/Perfil.php">MEU PERFIL</a>
        <?php elseif ($perm === 'A'): ?>
            <!-- Menu do Admin -->
            <a href="<?= $basePath ?>/View/admin/Dashboard.php">PAINEL</a>
            <a href="<?= $basePath ?>/View/admin/Servicos.php">GERENCIAR SERVIÇOS</a>
            <a href="<?= $basePath ?>/View/admin/Funcionarios.php">EQUIPE</a>
        <?php else: ?>
            <!-- Menu do Cliente / Visitante -->
            <a href="<?= $basePath ?>/Index.php">INÍCIO</a>
            <a href="<?= $basePath ?>/View/cliente/Servicos.php">SERVIÇOS</a>
            <a href="<?= $basePath ?>/View/cliente/Funcionarios.php">EQUIPE</a>
            <a href="<?= $basePath ?>/Index.php#contato">CONTATO</a>
        <?php endif; ?>
    </nav>

    <div class="header-auth">
        <?php include __DIR__ . '/AuthButton.php'; ?>
    </div>
</header>