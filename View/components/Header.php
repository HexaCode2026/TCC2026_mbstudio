<?php
$basePath = '/TCC2026_mbstudio';
?>
<style>
/* Estilos do Novo Cabeçalho Global */
.main-header {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 70px;
    background: rgba(20, 20, 20, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(212, 175, 55, 0.3);
    z-index: 999;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 40px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
    font-family: 'Inter', 'Candara', sans-serif;
}

.header-logo-link {
    display: flex;
    align-items: center;
    text-decoration: none;
}

.header-logo-img {
    height: 45px;
    width: auto;
    object-fit: contain;
    transition: transform 0.3s ease;
}

.header-logo-img:hover {
    transform: scale(1.05);
}

.header-nav {
    display: flex;
    gap: 30px;
    align-items: center;
}

.header-nav a {
    text-decoration: none;
    color: #ddd;
    font-size: 15px;
    font-weight: 500;
    position: relative;
    padding: 5px 0;
    transition: color 0.3s ease;
}

.header-nav a:hover, .header-nav a.ativo {
    color: #d4af37;
}

.header-nav a::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: 0;
    left: 0;
    background-color: #d4af37;
    transition: width 0.3s ease;
}

.header-nav a:hover::after, .header-nav a.ativo::after {
    width: 100%;
}

.header-auth {
    display: flex;
    align-items: center;
}

/* Spacer for the fixed header */
body {
    padding-top: 70px; 
}

/* Adaptação Responsiva do Header */
@media (max-width: 800px) {
    .main-header {
        padding: 0 20px;
        height: 60px;
    }
    .header-logo-img {
        height: 35px;
    }
    .header-nav {
        display: none; /* Em mobile, pode esconder ou criar menu sanduíche */
    }
    body {
        padding-top: 60px;
    }
}
</style>

<header class="main-header">
    <a href="<?= $basePath ?>/Index.php" class="header-logo-link">
        <img src="<?= $basePath ?>/assets/img/Logo.png" alt="Logo MB Studio" class="header-logo-img">
    </a>

    <nav class="header-nav">
        <a href="<?= $basePath ?>/Index.php">INÍCIO</a>
        <a href="<?= $basePath ?>/View/cliente/Servicos.php">SERVIÇOS</a>
        <a href="<?= $basePath ?>/View/cliente/Funcionarios.php">EQUIPE</a>
        <a href="#">CONTATO</a>
    </nav>

    <div class="header-auth">
        <?php include __DIR__ . '/AuthButton.php'; ?>
    </div>
</header>
