<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

// Proteção para Cliente
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'C') {
    header("Location: ../../Index.php");
    exit;
}

$ser_id = $_GET['Ser_id'] ?? null;

if (!$ser_id) {
    header("Location: Servicos.php");
    exit;
}

require_once "../../model/Service.php";

// Buscar detalhes do Serviço (para mostrar ao usuário)
$serviceModel = new Service($pdo);
$servico = $serviceModel->buscarPorId($ser_id);

if (!$servico) {
    header("Location: Servicos.php");
    exit;
}

// Para UX: Restrição mínima para o dia atual
$hoje = date("Y-m-d");
$amanha = date("Y-m-d", strtotime("+1 day"));
$depoisAmanha = date("Y-m-d", strtotime("+2 days"));
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolha a Data | Agendamento MB Studio</title>

    <link rel="stylesheet" href="../../assets/css/global.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --preto: #111111;
            --dourado: #b98527;
            --dourado-accent: #d4af37;
            --dourado-claro: #d6b56e;
            --fundo: #fdfcf9;
            --card: #ffffff;
            --card-alt: #f8f2e9;
            --borda: #eee8df;
            --borda-gold: rgba(185, 133, 39, 0.25);

            --font-heading: 'Playfair Display', Georgia, "Times New Roman", serif;
            --font-cursive: 'Alex Brush', cursive;
            --font-body: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;

            --shadow-subtle: 0 4px 20px rgba(185, 133, 39, 0.07), 0 1px 4px rgba(0, 0, 0, 0.03);
            --shadow-hover: 0 10px 30px rgba(185, 133, 39, 0.15);
            --transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--fundo);
            color: var(--preto);
            font-family: var(--font-body);
            min-height: 100vh;
            padding-top: 75px;
            overflow-x: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .booking-page-wrapper {
            min-height: calc(100vh - 75px);
            position: relative;
            overflow: hidden;
            padding: 40px 20px 80px;
            background:
                radial-gradient(circle at 15% 80%,
                    rgba(185, 133, 39, 0.035),
                    transparent 30%),
                radial-gradient(circle at 85% 20%,
                    rgba(214, 181, 110, 0.03),
                    transparent 35%),
                var(--fundo);
        }

        /* Decorações do Fundo */
        .decoracao {
            position: absolute;
            pointer-events: none;
            opacity: 0.45;
            z-index: 1;
        }

        .decoracao-esquerda {
            width: 210px;
            height: 500px;
            left: -100px;
            top: 150px;
            border-right: 1.5px solid var(--dourado-claro);
            border-radius: 50%;
        }

        .decoracao-direita {
            width: 300px;
            height: 650px;
            right: -180px;
            top: -40px;
            border-left: 1.5px solid var(--dourado-claro);
            border-radius: 50%;
        }

        .booking-container {
            max-width: 860px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        /* Top Navigation */
        .top-nav {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-voltar {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--dourado);
            background: #ffffff;
            border: 1px solid var(--borda-gold);
            padding: 9px 18px;
            border-radius: 30px;
            box-shadow: 0 2px 8px rgba(185, 133, 39, 0.06);
            transition: var(--transition);
        }

        .btn-voltar:hover {
            transform: translateX(-3px);
            border-color: var(--dourado);
            box-shadow: 0 4px 12px rgba(185, 133, 39, 0.15);
        }

        /* Etapas do Agendamento (Step Tracker) */
        .steps-progress-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            border: 1px solid var(--borda-gold);
            border-radius: 16px;
            padding: 16px 24px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-subtle);
            position: relative;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13.5px;
            color: #888;
            font-weight: 500;
        }

        .step-item.active {
            color: var(--preto);
            font-weight: 700;
        }

        .step-item.completed {
            color: var(--dourado);
            font-weight: 600;
        }

        .step-badge {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            background: #f4f0e6;
            color: #777;
            border: 1px solid var(--borda);
        }

        .step-item.active .step-badge {
            background: linear-gradient(135deg, var(--dourado), var(--dourado-accent));
            color: #ffffff;
            border-color: var(--dourado);
            box-shadow: 0 2px 8px rgba(185, 133, 39, 0.3);
        }

        .step-item.completed .step-badge {
            background: #ffffff;
            color: var(--dourado);
            border-color: var(--dourado);
        }

        .step-divider {
            flex: 1;
            height: 1px;
            background: #e6dfd1;
            margin: 0 12px;
            max-width: 60px;
        }

        /* Header da Página */
        .page-header-box {
            text-align: center;
            margin-bottom: 30px;
        }

        .tag-subtitulo {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            color: var(--dourado);
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .page-title {
            font-family: var(--font-heading);
            font-size: clamp(26px, 3.5vw, 36px);
            font-weight: 600;
            color: var(--preto);
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .page-desc {
            font-size: 14.5px;
            color: #666;
            max-width: 500px;
            margin: 0 auto;
        }

        /* Card Resumo do Serviço Escolhido */
        .service-summary-card {
            background: linear-gradient(135deg, #ffffff 0%, #faf6ee 100%);
            border: 1px solid var(--borda-gold);
            border-radius: 18px;
            padding: 22px 28px;
            box-shadow: var(--shadow-subtle);
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .service-summary-left {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .service-summary-img {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            border: 1.5px solid var(--dourado-claro);
            box-shadow: 0 2px 8px rgba(185, 133, 39, 0.15);
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: var(--dourado);
            flex-shrink: 0;
        }

        .service-summary-info h3 {
            font-family: var(--font-heading);
            font-size: 20px;
            font-weight: 700;
            color: var(--preto);
            margin-bottom: 4px;
        }

        .service-summary-meta {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 13.5px;
            color: #555;
        }

        .service-summary-meta strong {
            color: var(--dourado);
            font-size: 15px;
        }

        .btn-change-service {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--dourado);
            border: 1px solid var(--borda-gold);
            background: #ffffff;
            padding: 7px 14px;
            border-radius: 20px;
            transition: var(--transition);
        }

        .btn-change-service:hover {
            background: var(--card-alt);
            border-color: var(--dourado);
        }

        /* Card Principal de Seleção de Data */
        .date-picker-card {
            background: #ffffff;
            border: 1px solid var(--borda-gold);
            border-radius: 20px;
            padding: 38px 34px;
            box-shadow: var(--shadow-subtle);
            text-align: center;
        }

        .date-picker-card h2 {
            font-family: var(--font-heading);
            font-size: 22px;
            font-weight: 600;
            color: var(--preto);
            margin-bottom: 6px;
        }

        .date-picker-card p.picker-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 28px;
        }

        /* Atalhos Rápidos de Data */
        .quick-dates-grid {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 28px;
        }

        .quick-date-btn {
            background: var(--card-alt);
            border: 1px solid var(--borda-gold);
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 600;
            color: #444;
            cursor: pointer;
            transition: var(--transition);
        }

        .quick-date-btn:hover {
            border-color: var(--dourado);
            background: #faf4e8;
            color: var(--dourado);
            transform: translateY(-2px);
        }

        .quick-date-btn.active {
            background: linear-gradient(135deg, var(--dourado), var(--dourado-accent));
            color: #ffffff;
            border-color: var(--dourado);
            box-shadow: 0 4px 12px rgba(185, 133, 39, 0.25);
        }

        /* Input Date Customizado */
        .date-input-wrap {
            max-width: 380px;
            margin: 0 auto 30px;
            position: relative;
        }

        .date-input-wrap label {
            display: block;
            text-align: left;
            font-size: 12.5px;
            font-weight: 700;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .input-date-luxury {
            width: 100%;
            padding: 14px 18px;
            border: 1.5px solid rgba(185, 133, 39, 0.35);
            border-radius: 12px;
            font-size: 16px;
            font-family: var(--font-body);
            color: var(--preto);
            background: #faf8f3;
            outline: none;
            transition: var(--transition);
            box-sizing: border-box;
            cursor: pointer;
        }

        .input-date-luxury:focus {
            background: #ffffff;
            border-color: var(--dourado);
            box-shadow: 0 0 0 4px rgba(185, 133, 39, 0.15);
        }

        /* Botão de Envio */
        .btn-submit-date {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            width: 100%;
            max-width: 380px;
            height: 52px;
            background: linear-gradient(100deg, #b98224, #d4af37);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 1px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(185, 133, 39, 0.25);
            transition: var(--transition);
        }

        .btn-submit-date:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(185, 133, 39, 0.35);
            background: linear-gradient(100deg, #c28c2c, #deb943);
        }

        .btn-submit-date:active {
            transform: translateY(0);
        }

        .btn-submit-date svg {
            transition: transform 0.2s ease;
        }

        .btn-submit-date:hover svg {
            transform: translateX(4px);
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .booking-page-wrapper {
                padding: 25px 15px 60px;
            }

            .steps-progress-bar {
                padding: 12px 14px;
            }

            .step-text {
                display: none;
            }

            .step-item.active .step-text {
                display: inline;
                font-size: 12px;
            }

            .service-summary-card {
                padding: 18px 20px;
                flex-direction: column;
                align-items: flex-start;
            }

            .date-picker-card {
                padding: 28px 20px;
            }

            .quick-dates-grid {
                flex-direction: column;
                width: 100%;
            }

            .quick-date-btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <!-- Cabeçalho Oficial do Projeto -->
    <?php include '../components/Header.php'; ?>

    <main class="booking-page-wrapper">

        <!-- Elementos Decorativos Flutuantes -->
        <div class="decoracao decoracao-esquerda"></div>
        <div class="decoracao decoracao-direita"></div>

        <div class="booking-container">

            <!-- Navegação Superior -->
            <div class="top-nav">
                <a href="Servicos.php" class="btn-voltar" title="Voltar para a lista de serviços">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    <span>Escolher outro serviço</span>
                </a>
            </div>

            <!-- Progresso das Etapas -->
            <div class="steps-progress-bar">
                <div class="step-item completed">
                    <div class="step-badge">✓</div>
                    <span class="step-text">Serviço</span>
                </div>
                <div class="step-divider"></div>
                <div class="step-item active">
                    <div class="step-badge">2</div>
                    <span class="step-text">Escolha da Data</span>
                </div>
                <div class="step-divider"></div>
                <div class="step-item">
                    <div class="step-badge">3</div>
                    <span class="step-text">Horário</span>
                </div>
                <div class="step-divider"></div>
                <div class="step-item">
                    <div class="step-badge">4</div>
                    <span class="step-text">Confirmação</span>
                </div>
            </div>

            <!-- Título e Introdução -->
            <div class="page-header-box">
                <span class="tag-subtitulo">✦ Agendamento Online ✦</span>
                <h1 class="page-title">Qual o melhor dia para seu atendimento?</h1>
                <p class="page-desc">Selecione uma data no calendário abaixo para consultar os horários e especialistas
                    disponíveis no MB Studio.</p>
            </div>

            <!-- Resumo do Serviço Selecionado -->
            <div class="service-summary-card">
                <div class="service-summary-left">
                    <?php if (!empty($servico['Ser_image'])): ?>
                        <img src="../../<?= htmlspecialchars($servico['Ser_image']) ?>"
                            alt="<?= htmlspecialchars($servico['Ser_name']) ?>" class="service-summary-img">
                    <?php else: ?>
                        <div class="service-summary-img">✂</div>
                    <?php endif; ?>

                    <div class="service-summary-info">
                        <h3><?= htmlspecialchars($servico['Ser_name']) ?></h3>
                        <div class="service-summary-meta">
                            <span>Preço: <strong>R$
                                    <?= number_format($servico['Ser_price'] ?? 0, 2, ',', '.') ?></strong></span>
                            <span>•</span>
                            <span>Duração: <strong><?= htmlspecialchars($servico['Ser_duration'] ?? 0) ?>
                                    min</strong></span>
                        </div>
                    </div>
                </div>

                <a href="Servicos.php" class="btn-change-service" title="Alterar serviço">
                    Trocar Serviço
                </a>
            </div>

            <!-- Card de Escolha de Data -->
            <div class="date-picker-card">
                <h2>Selecione a Data Desejada</h2>
                <p class="picker-subtitle">Escolha uma data rápida ou use o seletor completo do calendário.</p>

                <!-- Botões de Atalho Rápido de Data -->
                <div class="quick-dates-grid">
                    <button type="button" class="quick-date-btn active" onclick="setBookingDate('<?= $hoje ?>', this)">
                        Hoje (<?= date("d/m", strtotime($hoje)) ?>)
                    </button>
                    <button type="button" class="quick-date-btn" onclick="setBookingDate('<?= $amanha ?>', this)">
                        Amanhã (<?= date("d/m", strtotime($amanha)) ?>)
                    </button>
                    <button type="button" class="quick-date-btn" onclick="setBookingDate('<?= $depoisAmanha ?>', this)">
                        Depois de amanhã (<?= date("d/m", strtotime($depoisAmanha)) ?>)
                    </button>
                </div>

                <!-- Formulário Original com Destino para Horarios.php -->
                <form action="Horarios.php" method="GET">
                    <input type="hidden" name="Ser_id" value="<?= htmlspecialchars($ser_id) ?>">

                    <div class="date-input-wrap">
                        <label for="data">Data do Agendamento:</label>
                        <input type="date" id="data" name="data" min="<?= $hoje ?>" value="<?= $hoje ?>"
                            class="input-date-luxury" required>
                    </div>

                    <button type="submit" class="btn-submit-date">
                        <span>Avançar para Ver Horários</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </form>
            </div>

        </div>

    </main>

    <!-- Modal Global de Autenticação -->
    <?php include '../components/LoginModal.php'; ?>

    <script>
        function setBookingDate(dateStr, btnElement) {
            const dateInput = document.getElementById('data');
            if (dateInput) {
                dateInput.value = dateStr;
            }
            document.querySelectorAll('.quick-date-btn').forEach(btn => btn.classList.remove('active'));
            if (btnElement) {
                btnElement.classList.add('active');
            }
        }

        // Sincronizar botões rápidos caso o usuário use o calendário manual
        document.getElementById('data').addEventListener('change', function () {
            const val = this.value;
            const quickButtons = document.querySelectorAll('.quick-date-btn');
            quickButtons.forEach(btn => btn.classList.remove('active'));

            if (val === '<?= $hoje ?>') {
                quickButtons[0]?.classList.add('active');
            } else if (val === '<?= $amanha ?>') {
                quickButtons[1]?.classList.add('active');
            } else if (val === '<?= $depoisAmanha ?>') {
                quickButtons[2]?.classList.add('active');
            }
        });
    </script>

</body>

</html>