<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

// Proteção para Funcionário ou Administrador
if (!isset($_SESSION['User_perm']) || ($_SESSION['User_perm'] !== 'F' && $_SESSION['User_perm'] !== 'A')) {
    header("Location: ../../Index.php");
    exit;
}

$userName = $_SESSION['User_name'] ?? 'Profissional';
$primeiroNome = explode(' ', trim($userName))[0];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Profissional | MB Studio</title>

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/funcionario/home.css">
</head>

<body>
    <!-- Cabeçalho Global MB Studio -->
    <?php include '../components/Header.php'; ?>

    <main class="employee-home-section">
        <div class="employee-card-wrapper">
            
            <div class="panel-badge">
                <span>✦ Painel do Profissional ✦</span>
            </div>

            <div class="employee-main-card">
                <div class="card-icon-wrap">
                    <!-- Ícone de Calendário / Relógio de Disponibilidade -->
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                        <polyline points="12 14 12 17 14 17"></polyline>
                    </svg>
                </div>

                <h1 class="card-title">
                    Olá, <span class="cursiva-gold"><?= htmlspecialchars($primeiroNome) ?></span>!
                </h1>

                <p class="card-subtitle">
                    Defina seus horários de atendimento, turnos e dias da semana disponíveis para que os clientes possam realizar agendamentos com você no estúdio.
                </p>

                <!-- BOTÃO GRANDE E CENTRALIZADO DE DISPONIBILIDADE -->
                <a href="Disponibilidade.php" class="btn-big-availability">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Checar Minha Disponibilidade</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>

                <!-- Ações Rápidas Complementares -->
                <div class="secondary-actions">
                    <a href="Agenda.php" class="btn-quick-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        Ver Minha Agenda
                    </a>

                    <a href="Perfil.php" class="btn-quick-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Meu Perfil Profissional
                    </a>
                </div>
            </div>

        </div>
    </main>

    <footer class="panel-footer">
        <p>&copy; <?= date('Y') ?> MB Studio. Painel do Profissional.</p>
    </footer>
</body>

</html>