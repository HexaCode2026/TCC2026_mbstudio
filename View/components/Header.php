<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/../../'));
$basePath = str_ireplace($docRoot, '', $projectRoot);

$headerCssVer = @filemtime(__DIR__ . '/../../assets/css/components/header.css') ?: '2.2';
$transCssVer  = @filemtime(__DIR__ . '/../../assets/css/cliente-transitions.css') ?: '2.2';
$transJsVer   = @filemtime(__DIR__ . '/../../assets/js/cliente-transitions.js') ?: '2.5';
$heroJsVerHdr = @filemtime(__DIR__ . '/../../assets/js/heroSlideshow.js') ?: '2.5';

$perm = $_SESSION['User_perm'] ?? null;
$userNameHeader = $_SESSION['User_name'] ?? null;
$firstNameHeader = $userNameHeader ? htmlspecialchars(explode(' ', trim($userNameHeader))[0]) : null;
$userInitialHeader = $userNameHeader ? strtoupper(substr(trim($userNameHeader), 0, 1)) : null;

// Detectar a página atual para destacar o link ativo
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
$currentPage = basename($currentScript, '.php');
?>
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/components/header.css?v=<?= $headerCssVer ?>">
<?php if ($perm !== 'A' && $perm !== 'F'): ?>
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/cliente-transitions.css?v=<?= $transCssVer ?>">
    <link rel="prefetch" href="<?= $basePath ?>/assets/css/cliente/servicos.css">
    <link rel="prefetch" href="<?= $basePath ?>/assets/css/cliente/funcionarios.css">
    <link rel="prefetch" href="<?= $basePath ?>/assets/css/home.css">
    <script src="<?= $basePath ?>/assets/js/cliente-transitions.js?v=<?= $transJsVer ?>" defer></script>
    <script src="<?= $basePath ?>/assets/js/heroSlideshow.js?v=<?= $heroJsVerHdr ?>" defer></script>
<?php endif; ?>

<header class="main-header" id="mainHeader">
    <!-- Backdrop do Menu Mobile (3 pontinhos) -->
    <div class="header-nav-backdrop" id="headerNavBackdrop" aria-hidden="true"></div>

    <div class="header-main-bar">
        <!-- ESQUERDA: Logo -->
        <a href="<?= $basePath ?>/Index.php" class="header-logo-link" title="MB Studio - Início">
            <img src="<?= $basePath ?>/assets/img/logob.png" alt="Logo MB Studio" class="header-logo-img">
        </a>

        <!-- CENTRO (Desktop) / MENU DE OPÇÕES & PERFIL (Mobile via 3 pontinhos no canto direito) -->
        <nav class="header-nav" id="headerNav" aria-label="Navegação Principal">
            <!-- Cabeçalho do Menu Mobile -->
            <div class="mobile-drawer-header">
                <span class="drawer-title">✦ MB Studio</span>
                <button type="button" class="drawer-close-btn" id="mobileDrawerClose" aria-label="Fechar menu">&times;</button>
            </div>

            <!-- Seção de Perfil / Conta no Modo Celular -->
            <div class="mobile-profile-section">
                <?php if ($userNameHeader): ?>
                    <div class="mobile-user-card">
                        <div class="mobile-user-avatar"><?= $userInitialHeader ?></div>
                        <div class="mobile-user-info">
                            <span class="mobile-user-greeting">Olá, <?= $firstNameHeader ?></span>
                            <span class="mobile-user-role">
                                <?php
                                if ($perm === 'A') echo 'Administrador';
                                elseif ($perm === 'F') echo 'Profissional';
                                else echo 'Cliente MB Studio';
                                ?>
                            </span>
                        </div>
                    </div>

                    <div class="mobile-profile-links">
                        <span class="mobile-section-label">OPÇÕES DE PERFIL</span>
                        <?php if ($perm === 'F'): ?>
                            <a href="<?= $basePath ?>/View/funcionario/Perfil.php" class="mobile-profile-item <?= $currentPage === 'Perfil' ? 'ativo' : '' ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                Meu Perfil
                            </a>
                            <a href="<?= $basePath ?>/View/funcionario/Agenda.php" class="mobile-profile-item <?= $currentPage === 'Agenda' ? 'ativo' : '' ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                Minha Agenda
                            </a>
                        <?php elseif ($perm === 'A'): ?>
                            <a href="<?= $basePath ?>/View/admin/Dashboard.php" class="mobile-profile-item <?= ($currentPage === 'Dashboard' || $currentPage === 'Home') ? 'ativo' : '' ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                                Painel Administrativo
                            </a>
                            <a href="<?= $basePath ?>/admin/perfil" class="mobile-profile-item <?= ($currentPage === 'Perfil' || $currentPage === 'PerfilController') ? 'ativo' : '' ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                Meu Perfil
                            </a>
                        <?php else: ?>
                            <a href="<?= $basePath ?>/View/cliente/Perfil.php" class="mobile-profile-item <?= $currentPage === 'Perfil' ? 'ativo' : '' ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                Meu Perfil
                            </a>
                            <a href="<?= $basePath ?>/View/cliente/MeusAgendamentos.php" class="mobile-profile-item <?= $currentPage === 'MeusAgendamentos' ? 'ativo' : '' ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                Meus Agendamentos
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <span class="mobile-section-label">CONTA & ACESSO</span>
                    <button type="button" class="mobile-auth-cta-btn" onclick="if(document.getElementById('headerNav')) document.getElementById('headerNav').classList.remove('mobile-open'); if(document.getElementById('headerNavBackdrop')) document.getElementById('headerNavBackdrop').classList.remove('active'); document.body.classList.remove('mobile-drawer-locked'); document.getElementById('global-login-modal').classList.add('show'); if(typeof switchModalView === 'function') switchModalView('view-login');">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                            <polyline points="10 17 15 12 10 7"></polyline>
                            <line x1="15" y1="12" x2="3" y2="12"></line>
                        </svg>
                        Login / Cadastrar
                    </button>
                <?php endif; ?>
            </div>

            <!-- Links Principais de Navegação (Centralizados no Desktop / Listados no Mobile) -->
            <div class="header-nav-links">
                <span class="mobile-section-label mobile-nav-label">NAVEGAÇÃO</span>
                <?php if ($perm === 'F'): ?>
                    <!-- Menu do Funcionário -->
                    <a href="<?= $basePath ?>/View/funcionario/Agenda.php" class="nav-item-link <?= $currentPage === 'Agenda' ? 'ativo' : '' ?>">MINHA AGENDA</a>
                    <a href="<?= $basePath ?>/View/funcionario/Disponibilidade.php" class="nav-item-link <?= $currentPage === 'Disponibilidade' ? 'ativo' : '' ?>">MEUS HORÁRIOS</a>
                    <a href="<?= $basePath ?>/View/funcionario/Bloqueios.php" class="nav-item-link <?= $currentPage === 'Bloqueios' ? 'ativo' : '' ?>">PAUSAS/BLOQUEIOS</a>
                    <a href="<?= $basePath ?>/View/funcionario/Perfil.php" class="nav-item-link <?= $currentPage === 'Perfil' ? 'ativo' : '' ?>">MEU PERFIL</a>
                <?php elseif ($perm === 'A'): ?>
                    <!-- Menu do Admin -->
                    <a href="<?= $basePath ?>/View/admin/Dashboard.php" class="nav-item-link <?= ($currentPage === 'Dashboard' || $currentPage === 'Home') ? 'ativo' : '' ?>">PAINEL</a>
                    <a href="<?= $basePath ?>/View/admin/Servicos.php" class="nav-item-link <?= $currentPage === 'Servicos' ? 'ativo' : '' ?>">SERVIÇOS</a>
                    <a href="<?= $basePath ?>/View/admin/Imagens.php" class="nav-item-link <?= $currentPage === 'Imagens' ? 'ativo' : '' ?>">IMAGENS</a>
                    <a href="<?= $basePath ?>/View/admin/Funcionarios.php" class="nav-item-link <?= $currentPage === 'Funcionarios' ? 'ativo' : '' ?>">EQUIPE</a>
                    <a href="<?= $basePath ?>/View/admin/Usuarios.php" class="nav-item-link <?= $currentPage === 'Usuarios' ? 'ativo' : '' ?>">USUÁRIOS</a>
                    <a href="<?= $basePath ?>/View/admin/Agenda.php" class="nav-item-link <?= $currentPage === 'Agenda' ? 'ativo' : '' ?>">AGENDAMENTOS</a>
                    <a href="<?= $basePath ?>/View/admin/Relatorios.php" class="nav-item-link <?= $currentPage === 'Relatorios' ? 'ativo' : '' ?>">RELATÓRIOS</a>
                    <a href="<?= $basePath ?>/admin/perfil" class="nav-item-link <?= ($currentPage === 'Perfil' || $currentPage === 'PerfilController') ? 'ativo' : '' ?>">PERFIL</a>
                <?php else: ?>
                    <!-- Menu do Cliente / Visitante -->
                    <a href="<?= $basePath ?>/Index.php" class="nav-item-link <?= $currentPage === 'Index' ? 'ativo' : '' ?>">INÍCIO</a>
                    <a href="<?= $basePath ?>/View/cliente/Servicos.php" class="nav-item-link <?= $currentPage === 'Servicos' ? 'ativo' : '' ?>">SERVIÇOS</a>
                    <a href="<?= $basePath ?>/View/cliente/Funcionarios.php" class="nav-item-link <?= $currentPage === 'Funcionarios' ? 'ativo' : '' ?>">EQUIPE</a>
                    <a href="<?= $basePath ?>/Index.php#contato" class="nav-item-link">CONTATO</a>
                <?php endif; ?>
            </div>

            <?php if ($userNameHeader): ?>
                <div class="mobile-drawer-footer">
                    <a href="<?= $basePath ?>/controller/Logout.php" class="mobile-logout-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        Sair da Conta
                    </a>
                </div>
            <?php endif; ?>
        </nav>

        <!-- DIREITA: Botão Login/Cadastrar (Desktop) & Botão 3 Pontinhos (Mobile) -->
        <div class="header-right-actions">
            <div class="header-auth">
                <?php include __DIR__ . '/AuthButton.php'; ?>
            </div>

            <!-- Botão 3 Pontinhos Mobile (Canto Direito) -->
            <button type="button" class="header-dots-btn" id="mobileMenuToggle" aria-label="Abrir opções de perfil e menu" aria-expanded="false" aria-controls="headerNav" title="Opções de perfil e menu">
                <?php if ($userInitialHeader): ?>
                    <span class="dots-user-avatar"><?= $userInitialHeader ?></span>
                <?php endif; ?>
                <svg class="dots-icon-svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <circle cx="12" cy="5" r="2.2"></circle>
                    <circle cx="12" cy="12" r="2.2"></circle>
                    <circle cx="12" cy="19" r="2.2"></circle>
                </svg>
            </button>
        </div>
    </div>
</header>

<script>
(function() {
    function initHeaderDrawer() {
        const toggleBtn = document.getElementById('mobileMenuToggle');
        const drawer = document.getElementById('headerNav');
        const backdrop = document.getElementById('headerNavBackdrop');
        const closeBtn = document.getElementById('mobileDrawerClose');

        if (!toggleBtn || !drawer) return;

        function openDrawer() {
            drawer.classList.add('mobile-open');
            if (backdrop) backdrop.classList.add('active');
            toggleBtn.setAttribute('aria-expanded', 'true');
            document.body.classList.add('mobile-drawer-locked');
        }

        function closeDrawer() {
            drawer.classList.remove('mobile-open');
            if (backdrop) backdrop.classList.remove('active');
            toggleBtn.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('mobile-drawer-locked');
        }

        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (drawer.classList.contains('mobile-open')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                closeDrawer();
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', closeDrawer);
        }

        // Fechar ao clicar fora do menu no modo mobile
        document.addEventListener('click', function(e) {
            if (drawer.classList.contains('mobile-open') && !drawer.contains(e.target) && !toggleBtn.contains(e.target)) {
                closeDrawer();
            }
        });

        // Fechar ao pressionar a tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && drawer.classList.contains('mobile-open')) {
                closeDrawer();
            }
        });

        // Fechar menu ao clicar em um link interno
        const links = drawer.querySelectorAll('a');
        links.forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 860) {
                    closeDrawer();
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeaderDrawer);
    } else {
        initHeaderDrawer();
    }
})();
</script>