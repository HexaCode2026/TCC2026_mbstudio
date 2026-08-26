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
$data = $_GET['data'] ?? null;

if (!$ser_id || !$data) {
    header("Location: Servicos.php");
    exit;
}

// Buscar detalhes completos do Serviço
$sqlServico = "SELECT Ser_name, Ser_duration, Ser_price, Ser_image FROM services WHERE Ser_id = ?";
$stmtServico = $pdo->prepare($sqlServico);
$stmtServico->execute([$ser_id]);
$servico = $stmtServico->fetch(PDO::FETCH_ASSOC);

if (!$servico) {
    header("Location: Servicos.php");
    exit;
}

// Buscar as disponibilidades de todos os funcionários que fazem esse serviço naquela data
$sql = "SELECT a.Ava_start, a.Ava_end, e.Emp_id, u.User_name, e.Emp_photo, e.Emp_specialty
        FROM availabilities a
        JOIN employees e ON a.Emp_id = e.Emp_id
        JOIN users u ON e.User_id = u.User_id
        JOIN employee_services es ON e.Emp_id = es.Emp_id
        WHERE a.Ava_date = ? 
          AND a.Ava_status = 'Disponivel' 
          AND es.Ser_id = ? 
          AND u.User_perm = 'F'
        ORDER BY a.Ava_start ASC, u.User_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$data, $ser_id]);
$disponibilidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar agendamentos do dia (não cancelados) para controle de conflito
$sqlAppo = "SELECT Emp_id, Appo_start, Appo_end FROM appointments WHERE Appo_date = ? AND Appo_status NOT LIKE 'Cancelado%'";
$stmtAppo = $pdo->prepare($sqlAppo);
$stmtAppo->execute([$data]);
$agendamentosDoDia = $stmtAppo->fetchAll(PDO::FETCH_ASSOC);

// Buscar bloqueios/pausas do dia para controle de conflito
$sqlBlock = "SELECT Emp_id, Block_start, Block_end FROM employee_blocks WHERE Block_date = ?";
$stmtBlock = $pdo->prepare($sqlBlock);
$stmtBlock->execute([$data]);
$bloqueiosDoDia = $stmtBlock->fetchAll(PDO::FETCH_ASSOC);

// Função para gerar blocos de horários com base na duração do serviço, evitando conflitos
function gerarHorarios($inicio, $fim, $duracao_minutos, $emp_id, $agendamentos, $bloqueios)
{
    $horarios = [];
    $atual = strtotime($inicio);
    $final = strtotime($fim);

    while ($atual + ($duracao_minutos * 60) <= $final) {
        $slot_start_time = $atual;
        $slot_end_time = $atual + ($duracao_minutos * 60);
        $conflito = false;
        $max_end = 0;

        // Verifica conflito com agendamentos existentes
        foreach ($agendamentos as $ag) {
            if ($ag['Emp_id'] == $emp_id) {
                $ag_start = strtotime($ag['Appo_start']);
                $ag_end = strtotime($ag['Appo_end']);
                if ($slot_start_time < $ag_end && $slot_end_time > $ag_start) {
                    $conflito = true;
                    if ($ag_end > $max_end) {
                        $max_end = $ag_end;
                    }
                }
            }
        }

        // Verifica conflito com bloqueios/pausas do funcionário
        foreach ($bloqueios as $bl) {
            if ($bl['Emp_id'] == $emp_id) {
                $bl_start = strtotime($bl['Block_start']);
                $bl_end = strtotime($bl['Block_end']);
                if ($slot_start_time < $bl_end && $slot_end_time > $bl_start) {
                    $conflito = true;
                    if ($bl_end > $max_end) {
                        $max_end = $bl_end;
                    }
                }
            }
        }

        // Se não houver conflito, o horário está livre
        if (!$conflito) {
            $horarios[] = date('H:i', $atual);
            $atual += ($duracao_minutos * 60); // Avança normalmente
        } else {
            // Se houver conflito, pula exatamente para o final do bloqueio mais longo que conflitou
            if ($max_end > $atual) {
                $atual = $max_end;
            } else {
                // Fallback de segurança para evitar loops infinitos
                $atual += (10 * 60);
            }
        }
    }
    return $horarios;
}

// Função auxiliar para gerar iniciais quando não houver foto
function getIniciais($nome) {
    $partes = preg_split('/\s+/', trim($nome));
    $iniciais = '';
    if (count($partes) >= 2) {
        $iniciais = mb_substr($partes[0], 0, 1) . mb_substr(end($partes), 0, 1);
    } elseif (count($partes) === 1 && !empty($partes[0])) {
        $iniciais = mb_substr($partes[0], 0, 2);
    }
    return strtoupper($iniciais ?: 'MB');
}

// Formatar data em português
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
$dataTimestamp = strtotime($data);
$dataFormatada = date("d/m/Y", $dataTimestamp);
$diasSemana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
$diaSemanaNome = $diasSemana[date('w', $dataTimestamp)];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolha o Horário | MB Studio</title>

    <link rel="stylesheet" href="../../assets/css/global.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">

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
            --shadow-hover: 0 12px 32px rgba(185, 133, 39, 0.16);
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
                radial-gradient(
                    circle at 15% 80%,
                    rgba(185, 133, 39, 0.035),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 85% 20%,
                    rgba(214, 181, 110, 0.03),
                    transparent 35%
                ),
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
            max-width: 880px;
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

        /* Resumo do Procedimento e Data */
        .selection-summary-card {
            background: linear-gradient(135deg, #ffffff 0%, #faf6ee 100%);
            border: 1px solid var(--borda-gold);
            border-radius: 18px;
            padding: 20px 24px;
            box-shadow: var(--shadow-subtle);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 18px;
        }

        .summary-block {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .summary-icon-wrap {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: rgba(185, 133, 39, 0.1);
            border: 1px solid var(--borda-gold);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dourado);
            font-size: 20px;
            flex-shrink: 0;
        }

        .summary-label {
            font-size: 11.5px;
            font-weight: 700;
            color: #777;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .summary-val {
            font-family: var(--font-heading);
            font-size: 18px;
            font-weight: 700;
            color: var(--preto);
        }

        .summary-subval {
            font-size: 13px;
            color: #555;
        }

        .summary-actions {
            display: flex;
            gap: 10px;
        }

        .btn-action-small {
            font-size: 12px;
            font-weight: 600;
            color: var(--dourado);
            background: #ffffff;
            border: 1px solid var(--borda-gold);
            padding: 6px 14px;
            border-radius: 20px;
            transition: var(--transition);
        }

        .btn-action-small:hover {
            background: var(--card-alt);
            border-color: var(--dourado);
        }

        /* Header da Seção */
        .section-header-box {
            text-align: center;
            margin-bottom: 32px;
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

        .section-title {
            font-family: var(--font-heading);
            font-size: clamp(24px, 3.2vw, 34px);
            font-weight: 600;
            color: var(--preto);
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .section-desc {
            font-size: 14.5px;
            color: #666;
            max-width: 540px;
            margin: 0 auto;
        }

        /* Cards dos Profissionais */
        .professionals-grid {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .professional-card {
            background: #ffffff;
            border: 1px solid var(--borda-gold);
            border-radius: 20px;
            padding: 28px;
            box-shadow: var(--shadow-subtle);
            transition: var(--transition);
        }

        .professional-card:hover {
            box-shadow: var(--shadow-hover);
            border-color: var(--dourado);
        }

        .professional-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(214, 181, 110, 0.25);
            margin-bottom: 20px;
        }

        .prof-info-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .prof-photo-frame {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 2px solid var(--dourado-claro);
            box-shadow: 0 4px 12px rgba(185, 133, 39, 0.15);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #faf6ee, #f5ecd9);
            color: var(--dourado);
            font-family: var(--font-heading);
            font-size: 20px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .prof-photo-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .prof-name-text {
            font-family: var(--font-heading);
            font-size: 21px;
            font-weight: 700;
            color: var(--preto);
            margin-bottom: 3px;
        }

        .prof-specialty-badge {
            display: inline-block;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--dourado);
            background: rgba(185, 133, 39, 0.08);
            padding: 2px 10px;
            border-radius: 20px;
        }

        .shift-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            background: #faf8f3;
            border: 1px solid var(--borda-gold);
            padding: 6px 14px;
            border-radius: 20px;
        }

        /* Grade de Botões de Horários */
        .time-slots-container {
            margin-top: 10px;
        }

        .time-slots-title {
            font-size: 13px;
            font-weight: 700;
            color: #777;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .time-slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 12px;
        }

        .time-slot-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: #faf8f3;
            border: 1.5px solid rgba(185, 133, 39, 0.35);
            color: var(--preto);
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            transition: var(--transition);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
            cursor: pointer;
        }

        .time-slot-btn:hover {
            background: linear-gradient(135deg, var(--dourado), var(--dourado-accent));
            border-color: var(--dourado);
            color: #ffffff;
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(185, 133, 39, 0.3);
        }

        .time-slot-btn:active {
            transform: translateY(-1px);
        }

        .time-slot-btn svg {
            color: var(--dourado);
            transition: color 0.2s ease;
        }

        .time-slot-btn:hover svg {
            color: #ffffff;
        }

        .warning-duration-notice {
            font-size: 13.5px;
            color: #8a6508;
            background: #faf4e8;
            border: 1px solid var(--borda-gold);
            padding: 12px 16px;
            border-radius: 10px;
            margin: 0;
        }

        /* Estado Vazio (Sem Disponibilidade) */
        .empty-schedule-card {
            background: #ffffff;
            border: 1px solid var(--borda-gold);
            border-radius: 20px;
            padding: 50px 30px;
            text-align: center;
            box-shadow: var(--shadow-subtle);
        }

        .empty-icon-circle {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(185, 133, 39, 0.08);
            border: 1px solid var(--borda-gold);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: var(--dourado);
            font-size: 28px;
        }

        .empty-title {
            font-family: var(--font-heading);
            font-size: 22px;
            font-weight: 700;
            color: var(--preto);
            margin-bottom: 8px;
        }

        .empty-desc {
            font-size: 14.5px;
            color: #666;
            max-width: 480px;
            margin: 0 auto 24px;
            line-height: 1.55;
        }

        .btn-choose-other-date {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(100deg, #b98224, #d4af37);
            color: #ffffff;
            padding: 12px 28px;
            border-radius: 12px;
            font-size: 14.5px;
            font-weight: 700;
            transition: var(--transition);
            box-shadow: 0 4px 14px rgba(185, 133, 39, 0.25);
        }

        .btn-choose-other-date:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(185, 133, 39, 0.35);
            background: linear-gradient(100deg, #c28c2c, #deb943);
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

            .selection-summary-card {
                flex-direction: column;
                align-items: flex-start;
                padding: 18px 20px;
            }

            .summary-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .professional-card {
                padding: 20px 18px;
            }

            .professional-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .time-slots-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 480px) {
            .time-slots-grid {
                grid-template-columns: repeat(2, 1fr);
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
                <a href="Data.php?Ser_id=<?= htmlspecialchars($ser_id) ?>" class="btn-voltar" title="Retornar à seleção de data">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    <span>Alterar data</span>
                </a>
            </div>

            <!-- Progresso das Etapas -->
            <div class="steps-progress-bar">
                <div class="step-item completed">
                    <div class="step-badge">✓</div>
                    <span class="step-text">Serviço</span>
                </div>
                <div class="step-divider"></div>
                <div class="step-item completed">
                    <div class="step-badge">✓</div>
                    <span class="step-text">Data</span>
                </div>
                <div class="step-divider"></div>
                <div class="step-item active">
                    <div class="step-badge">3</div>
                    <span class="step-text">Horário</span>
                </div>
                <div class="step-divider"></div>
                <div class="step-item">
                    <div class="step-badge">4</div>
                    <span class="step-text">Confirmação</span>
                </div>
            </div>

            <!-- Resumo das Escolhas Anteriores -->
            <div class="selection-summary-card">
                <div class="summary-block">
                    <div class="summary-icon-wrap">✂</div>
                    <div>
                        <div class="summary-label">Serviço Selecionado</div>
                        <div class="summary-val"><?= htmlspecialchars($servico['Ser_name']) ?></div>
                        <div class="summary-subval">
                            Duração: <strong><?= $servico['Ser_duration'] ?> min</strong>
                            <?php if(!empty($servico['Ser_price'])): ?>
                                • R$ <strong><?= number_format($servico['Ser_price'], 2, ',', '.') ?></strong>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="summary-block">
                    <div class="summary-icon-wrap">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div>
                        <div class="summary-label">Data Escolhida</div>
                        <div class="summary-val"><?= $dataFormatada ?></div>
                        <div class="summary-subval"><?= $diaSemanaNome ?></div>
                    </div>
                </div>

                <div class="summary-actions">
                    <a href="Data.php?Ser_id=<?= htmlspecialchars($ser_id) ?>" class="btn-action-small">Alterar Data</a>
                    <a href="Servicos.php" class="btn-action-small">Trocar Serviço</a>
                </div>
            </div>

            <!-- Título e Introdução da Seção -->
            <div class="section-header-box">
                <span class="tag-subtitulo">✦ Profissionais & Disponibilidade ✦</span>
                <h1 class="section-title">Escolha o Profissional e Horário</h1>
                <p class="section-desc">Clique no horário mais conveniente abaixo para avançar para a confirmação do seu agendamento.</p>
            </div>

            <!-- Lista de Profissionais Disponíveis -->
            <?php if (count($disponibilidades) > 0): ?>
                <div class="professionals-grid">
                    <?php foreach ($disponibilidades as $disp): ?>
                        <div class="professional-card">
                            
                            <!-- Cabeçalho do Especialista -->
                            <div class="professional-header">
                                <div class="prof-info-left">
                                    <div class="prof-photo-frame">
                                        <?php if (!empty($disp['Emp_photo'])): ?>
                                            <img src="../../<?= htmlspecialchars($disp['Emp_photo']) ?>" class="prof-photo-img" alt="<?= htmlspecialchars($disp['User_name']) ?>">
                                        <?php else: ?>
                                            <span><?= getIniciais($disp['User_name']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h2 class="prof-name-text"><?= htmlspecialchars($disp['User_name']) ?></h2>
                                        <?php if (!empty($disp['Emp_specialty'])): ?>
                                            <span class="prof-specialty-badge"><?= htmlspecialchars($disp['Emp_specialty']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="shift-badge">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    <span>Expediente: <?= date('H:i', strtotime($disp['Ava_start'])) ?> às <?= date('H:i', strtotime($disp['Ava_end'])) ?></span>
                                </div>
                            </div>

                            <!-- Grade de Horários Disponíveis -->
                            <div class="time-slots-container">
                                <div class="time-slots-title">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    Horários Livres para Atendimento:
                                </div>

                                <?php
                                $horarios_gerados = gerarHorarios(
                                    $disp['Ava_start'], 
                                    $disp['Ava_end'], 
                                    $servico['Ser_duration'], 
                                    $disp['Emp_id'], 
                                    $agendamentosDoDia, 
                                    $bloqueiosDoDia
                                );
                                
                                if (count($horarios_gerados) > 0):
                                ?>
                                    <div class="time-slots-grid">
                                        <?php foreach ($horarios_gerados as $h): ?>
                                            <!-- Link que envia os dados para Confirmar.php -->
                                            <a 
                                                href="Confirmar.php?Ser_id=<?= $ser_id ?>&data=<?= $data ?>&Emp_id=<?= $disp['Emp_id'] ?>&hora=<?= $h ?>" 
                                                class="time-slot-btn"
                                                title="Agendar com <?= htmlspecialchars($disp['User_name']) ?> às <?= $h ?>"
                                            >
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <polyline points="12 6 12 12 16 14"></polyline>
                                                </svg>
                                                <span><?= $h ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="warning-duration-notice">
                                        <em>O tempo deste procedimento (<?= $servico['Ser_duration'] ?> min) ultrapassa a janela disponível deste profissional.</em>
                                    </p>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <!-- Estado Vazio -->
                <div class="empty-schedule-card">
                    <div class="empty-icon-circle">📅</div>
                    <h2 class="empty-title">Nenhum horário disponível para esta data</h2>
                    <p class="empty-desc">
                        Não encontramos nenhum especialista com horários livres para o procedimento <strong>"<?= htmlspecialchars($servico['Ser_name']) ?>"</strong> no dia <strong><?= $dataFormatada ?></strong>.
                    </p>
                    <a href="Data.php?Ser_id=<?= htmlspecialchars($ser_id) ?>" class="btn-choose-other-date">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        <span>Escolher Outra Data</span>
                    </a>
                </div>
            <?php endif; ?>

        </div>

    </main>

    <!-- Modal Global de Autenticação -->
    <?php include '../components/LoginModal.php'; ?>

</body>

</html>