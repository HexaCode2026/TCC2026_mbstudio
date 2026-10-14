<?php
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/../../'));
$basePath = str_ireplace($docRoot, '', $projectRoot);
?>
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/components/header.css">
<?php
$userPermHeader = $_SESSION['User_perm'] ?? null;
if ($userPermHeader !== 'A' && $userPermHeader !== 'F'): ?>
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/cliente-transitions.css">
    <link rel="prefetch" href="<?= $basePath ?>/assets/css/cliente/servicos.css">
    <link rel="prefetch" href="<?= $basePath ?>/assets/css/cliente/funcionarios.css">
    <link rel="prefetch" href="<?= $basePath ?>/assets/css/home.css">
    <script src="<?= $basePath ?>/assets/js/cliente-transitions.js" defer></script>
<?php endif; ?>

<header class="main-header">
    <a href="<?= $basePath ?>/Index.php" class="header-logo-link">
        <img src="<?= $basePath ?>/assets/img/logob.png" alt="Logo MB Studio" class="header-logo-img">
    </a>

    <nav class="header-nav">
        <?php
        $perm = $_SESSION['User_perm'] ?? null;
        // Detectar a página atual para destacar o link ativo
        $currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
        $currentPage = basename($currentScript, '.php');
        $currentFull = $currentScript . ($_SERVER['QUERY_STRING'] ?? '');

        if ($perm === 'F'): ?>
            <!-- Menu do Funcionário -->
            <a href="<?= $basePath ?>/View/funcionario/Agenda.php" class="<?= $currentPage === 'Agenda' ? 'ativo' : '' ?>">MINHA AGENDA</a>
            <a href="<?= $basePath ?>/View/funcionario/Disponibilidade.php" class="<?= $currentPage === 'Disponibilidade' ? 'ativo' : '' ?>">MEUS HORÁRIOS</a>
            <a href="<?= $basePath ?>/View/funcionario/Bloqueios.php" class="<?= $currentPage === 'Bloqueios' ? 'ativo' : '' ?>">PAUSAS/BLOQUEIOS</a>
            <a href="<?= $basePath ?>/View/funcionario/Perfil.php" class="<?= $currentPage === 'Perfil' ? 'ativo' : '' ?>">MEU PERFIL</a>
        <?php elseif ($perm === 'A'): ?>
            <!-- Menu do Admin -->
            <a href="<?= $basePath ?>/View/admin/Dashboard.php" class="<?= ($currentPage === 'Dashboard' || $currentPage === 'Home') ? 'ativo' : '' ?>">PAINEL</a>
            <a href="<?= $basePath ?>/View/admin/Servicos.php" class="<?= $currentPage === 'Servicos' ? 'ativo' : '' ?>">GERENCIAR SERVIÇOS</a>
            <a href="<?= $basePath ?>/View/admin/Funcionarios.php" class="<?= $currentPage === 'Funcionarios' ? 'ativo' : '' ?>">EQUIPE</a>
        <?php else: ?>
            <!-- Menu do Cliente / Visitante -->
            <a href="<?= $basePath ?>/Index.php" class="<?= $currentPage === 'Index' ? 'ativo' : '' ?>">INÍCIO</a>
            <a href="<?= $basePath ?>/View/cliente/Servicos.php" class="<?= $currentPage === 'Servicos' ? 'ativo' : '' ?>">SERVIÇOS</a>
            <a href="<?= $basePath ?>/View/cliente/Funcionarios.php" class="<?= $currentPage === 'Funcionarios' ? 'ativo' : '' ?>">EQUIPE</a>
            <a href="<?= $basePath ?>/Index.php#contato" class="">CONTATO</a>
        <?php endif; ?>
    </nav>

    <div class="header-auth">
        <?php include __DIR__ . '/AuthButton.php'; ?>
    </div>
</header>