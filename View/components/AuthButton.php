<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$userName = isset($_SESSION['User_name']) ? $_SESSION['User_name'] : null;
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/../../'));
$basePath = str_ireplace($docRoot, '', $projectRoot);
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/components/authbutton.css">

<div class="auth-floating-container">
    <?php if ($userName): ?>
        <div class="auth-dropdown">
            <div class="auth-btn">
                <div class="auth-icon"><?= strtoupper(substr($userName, 0, 1)) ?></div>
                Olá, <?= htmlspecialchars(explode(' ', $userName)[0]) ?>
            </div>
            <div class="auth-dropdown-content">
                <div class="auth-dropdown-menu">
                    <?php if (isset($_SESSION['User_perm']) && $_SESSION['User_perm'] == 'F'): ?>
                        <a href="<?= $basePath ?>/View/funcionario/Agenda.php" class="auth-dropdown-item">Minha Agenda</a>
                    <?php elseif (isset($_SESSION['User_perm']) && $_SESSION['User_perm'] == 'A'): ?>
                        <a href="<?= $basePath ?>/View/admin/Dashboard.php" class="auth-dropdown-item">Painel Administrativo</a>
                    <?php else: ?>
                        <a href="<?= $basePath ?>/View/cliente/MeusAgendamentos.php" class="auth-dropdown-item">Meus Agendamentos</a>
                        <a href="<?= $basePath ?>/View/cliente/Perfil.php" class="auth-dropdown-item">Meu Perfil</a>
                    <?php endif; ?>
                    <a href="<?= $basePath ?>/controller/Logout.php" class="auth-dropdown-item logout">Sair</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <a href="#" onclick="document.getElementById('global-login-modal').classList.add('show'); switchModalView('view-login'); return false;" class="auth-btn">
            <div class="auth-icon" style="background: transparent; border: 1px solid #d4af37; color: #d4af37;">
                <!-- Icone SVG de Login -->
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10 17 15 12 10 7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
            </div>
            Login / Cadastrar
        </a>
    <?php endif; ?>
</div>
