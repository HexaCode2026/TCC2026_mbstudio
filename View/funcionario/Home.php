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

    <style>
        :root {
            --gold-primary: #d4af37;
            --gold-light: #f3e5ab;
            --gold-dark: #8a6508;
            --gold-darker: #6e5005;
            --gold-border: rgba(212, 175, 55, 0.35);
            --bg-dark: #0f0f11;
            --bg-card: #161619;
            --text-light: #ffffff;
            --text-muted: #a0a0a8;
            --font-heading: 'Playfair Display', Georgia, serif;
            --font-cursive: 'Alex Brush', 'Brush Script MT', cursive;
            --font-body: 'Inter', -apple-system, sans-serif;
            --transition-smooth: all 0.35s cubic-bezier(0.25, 1, 0.5, 1);
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-light);
            font-family: var(--font-body);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            overflow-x: hidden;
        }

        .cursiva-gold {
            font-family: var(--font-cursive);
            color: var(--gold-primary);
            font-style: italic;
            font-size: 1.25em;
            line-height: 1;
            display: inline-block;
            padding: 0 4px;
        }

        /* Container Principal Centralizado */
        .employee-home-section {
            position: relative;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 20px 80px;
            background: radial-gradient(circle at 50% 30%, rgba(212, 175, 55, 0.1) 0%, rgba(15, 15, 17, 0.95) 70%), var(--bg-dark);
            box-sizing: border-box;
        }

        .employee-home-section::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.08) 0%, transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }

        .employee-card-wrapper {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 680px;
            text-align: center;
        }

        /* Badge Superior */
        .panel-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 18px;
            background: rgba(212, 175, 55, 0.1);
            border: 1px solid var(--gold-border);
            border-radius: 30px;
            color: var(--gold-light);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        /* Card Central de Alto Padrão */
        .employee-main-card {
            background: linear-gradient(145deg, #1a1a1e, #121215);
            border: 2px solid var(--gold-border);
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6), 0 0 35px rgba(212, 175, 55, 0.08);
            position: relative;
            box-sizing: border-box;
            backdrop-filter: blur(10px);
        }

        .employee-main-card::after {
            content: '';
            position: absolute;
            inset: 10px;
            border: 1px dashed rgba(212, 175, 55, 0.2);
            border-radius: 14px;
            pointer-events: none;
        }

        .card-icon-wrap {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.2), rgba(138, 101, 8, 0.3));
            border: 2px solid var(--gold-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            color: var(--gold-light);
            box-shadow: 0 0 25px rgba(212, 175, 55, 0.25);
            transition: var(--transition-smooth);
        }

        .employee-main-card:hover .card-icon-wrap {
            transform: scale(1.08) rotate(5deg);
            box-shadow: 0 0 35px rgba(212, 175, 55, 0.4);
        }

        .card-title {
            font-family: var(--font-heading);
            font-size: clamp(26px, 4vw, 36px);
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 12px;
            line-height: 1.2;
        }

        .card-subtitle {
            font-size: 15px;
            color: var(--text-muted);
            line-height: 1.65;
            max-width: 480px;
            margin: 0 auto 35px;
        }

        /* Botão Grande e Centralizado de Disponibilidade */
        .btn-big-availability {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            width: 100%;
            max-width: 440px;
            background: linear-gradient(135deg, #d4af37 0%, #a67c00 50%, #8a6508 100%);
            color: #0b0b0c;
            font-family: var(--font-body);
            font-weight: 800;
            font-size: clamp(14px, 1.6vw, 16px);
            letter-spacing: 1.8px;
            text-transform: uppercase;
            padding: 20px 32px;
            border-radius: 8px;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.35), 0 4px 12px rgba(0, 0, 0, 0.5);
            transition: var(--transition-smooth);
            cursor: pointer;
            box-sizing: border-box;
            position: relative;
            z-index: 3;
        }

        .btn-big-availability:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(212, 175, 55, 0.55), 0 6px 20px rgba(0, 0, 0, 0.6);
            background: linear-gradient(135deg, #f3e5ab 0%, #d4af37 60%, #a67c00 100%);
            color: #000000;
        }

        .btn-big-availability:active {
            transform: translateY(-1px);
        }

        .btn-big-availability svg {
            transition: transform 0.3s ease;
        }

        .btn-big-availability:hover svg {
            transform: translateX(4px);
        }

        /* Links Secundários / Navegação Rápida */
        .secondary-actions {
            display: flex;
            justify-content: center;
            gap: 18px;
            margin-top: 30px;
            flex-wrap: wrap;
            position: relative;
            z-index: 3;
        }

        .btn-quick-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--gold-light);
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(212, 175, 55, 0.25);
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition-smooth);
        }

        .btn-quick-link:hover {
            background: rgba(212, 175, 55, 0.15);
            border-color: var(--gold-primary);
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* Rodapé Minimalista do Painel */
        .panel-footer {
            text-align: center;
            padding: 20px;
            font-size: 12.5px;
            color: #66666e;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Responsividade */
        @media (max-width: 600px) {
            .employee-home-section {
                padding: 40px 15px 60px;
            }

            .employee-main-card {
                padding: 35px 20px;
            }

            .card-icon-wrap {
                width: 65px;
                height: 65px;
                margin-bottom: 20px;
            }

            .card-icon-wrap svg {
                width: 28px;
                height: 28px;
            }

            .btn-big-availability {
                padding: 16px 20px;
                font-size: 13.5px;
            }

            .secondary-actions {
                flex-direction: column;
                gap: 12px;
            }

            .btn-quick-link {
                width: 100%;
                justify-content: center;
                box-sizing: border-box;
            }
        }
    </style>
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