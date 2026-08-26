<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$userName = isset($_SESSION['User_name']) ? $_SESSION['User_name'] : null;
$basePath = '/TCC2026_mbstudio';
?>

<style>
/* CSS para o botão flutuante de autenticação */
.auth-floating-container {
    position: relative;
    z-index: 1000;
    font-family: 'Inter', sans-serif;
    display: flex;
    align-items: center;
}

.auth-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(26, 26, 26, 0.8);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(212, 175, 55, 0.5);
    color: #fff;
    padding: 10px 20px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.auth-btn:hover {
    background: rgba(212, 175, 55, 0.1);
    border-color: #d4af37;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(212, 175, 55, 0.3);
}

.auth-icon {
    width: 24px;
    height: 24px;
    background: linear-gradient(135deg, #d4af37, #f3e5ab);
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #000;
    font-weight: bold;
    font-size: 12px;
}

/* Dropdown para usuário logado */
.auth-dropdown {
    position: relative;
    display: inline-block;
}

.auth-dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    padding-top: 10px; /* Cria uma área invisível para o hover não falhar */
    z-index: 1001;
}

.auth-dropdown-menu {
    background-color: #1a1a1a;
    min-width: 150px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.5);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    overflow: hidden;
}

.auth-dropdown:hover .auth-dropdown-content {
    display: block;
}

.auth-dropdown-item {
    color: white;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
    font-size: 14px;
    transition: background 0.2s;
}

.auth-dropdown-item:hover {
    background-color: rgba(212, 175, 55, 0.2);
    color: #f3e5ab;
}

.auth-dropdown-item.logout {
    color: #ff6b6b;
    border-top: 1px solid rgba(255,255,255,0.05);
}

.auth-dropdown-item.logout:hover {
    background-color: rgba(255, 107, 107, 0.1);
    color: #ff6b6b;
}
</style>

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
