<?php
$basePath = '/TCC2026_mbstudio';
?>
<style>
    /* Estilos do Cabeçalho Global MB Studio (Estrutura Original Totalmente Responsiva) */
    .main-header {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        min-height: 70px;
        background: rgba(18, 18, 20, 0.96);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(212, 175, 55, 0.3);
        z-index: 9999;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 clamp(12px, 3vw, 40px);
        box-shadow: 0 4px 25px rgba(0, 0, 0, 0.6);
        font-family: 'Inter', 'Candara', -apple-system, sans-serif;
        box-sizing: border-box;
        transition: all 0.3s ease;
    }

    .header-logo-link {
        display: flex;
        align-items: center;
        text-decoration: none;
        flex-shrink: 0;
    }

    .header-logo-img {
        height: clamp(34px, 4vw, 45px);
        width: auto;
        object-fit: contain;
        transition: transform 0.3s ease;
    }

    .header-logo-img:hover {
        transform: scale(1.05);
    }

    .header-nav {
        display: flex;
        gap: clamp(10px, 2.2vw, 30px);
        align-items: center;
        flex-wrap: wrap;
        justify-content: center;
    }

    .header-nav a {
        text-decoration: none;
        color: #e0e0e0;
        font-size: clamp(12px, 1.2vw, 14.5px);
        font-weight: 500;
        letter-spacing: 0.8px;
        position: relative;
        padding: 4px 0;
        white-space: nowrap;
        transition: color 0.3s ease;
    }

    .header-nav a:hover,
    .header-nav a.ativo {
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

    .header-nav a:hover::after,
    .header-nav a.ativo::after {
        width: 100%;
    }

    .header-auth {
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }

    /* Espaço do corpo para o topo fixo */
    body {
        padding-top: 70px;
    }

    /* Responsividade Adaptativa Mantendo a Estrutura Original */
    @media (max-width: 850px) {
        .main-header {
            padding: 8px 15px;
            min-height: 65px;
        }

        .header-nav {
            gap: clamp(8px, 1.8vw, 18px);
        }
    }

    @media (max-width: 650px) {
        .main-header {
            padding: 6px 12px;
            min-height: auto;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: space-between;
        }

        .header-nav {
            order: 3;
            width: 100%;
            padding-top: 4px;
            border-top: 1px solid rgba(212, 175, 55, 0.15);
            gap: 14px;
        }

        .header-nav a {
            font-size: 12px;
            padding: 2px 0;
        }

        body {
            padding-top: 88px;
        }
    }

    @media (max-width: 400px) {
        .header-nav {
            gap: 10px;
        }

        .header-nav a {
            font-size: 11px;
        }

        body {
            padding-top: 92px;
        }
    }
</style>

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