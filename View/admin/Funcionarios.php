<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";
require_once "../../model/Employee.php";

Session::iniciar();

// Proteção Admin
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'A') {
    header("Location: ../../Index.php");
    exit;
}

$employeeModel = new Employee($pdo);
$funcionarios = $employeeModel->listarEquipeAdmin();

// Cálculos rápidos para métricas
$totalFuncionarios = count($funcionarios);
$totalAtivos = 0;
$totalInativos = 0;
$totalHorariosGeral = 0;
$totalComServicos = 0;

foreach ($funcionarios as $f) {
    if ($f['User_active'] == 1) $totalAtivos++;
    else $totalInativos++;

    $totalHorariosGeral += intval($f['total_horarios'] ?? 0);
    if (!empty($f['servicos_atribuidos'])) {
        $totalComServicos++;
    }
}

// Função auxiliar para gerar iniciais do nome para o avatar quando não houver foto
function getIniciais($nome) {
    $partes = preg_split('/\s+/', trim($nome));
    $iniciais = '';
    if (count($partes) >= 2) {
        $iniciais = mb_substr($partes[0], 0, 1) . mb_substr(end($partes), 0, 1);
    } elseif (count($partes) === 1 && !empty($partes[0])) {
        $iniciais = mb_substr($partes[0], 0, 2);
    }
    return strtoupper($iniciais ?: 'PR');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Equipe | MB Studio Admin</title>

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <style>
        :root {
            --gold-primary: #b8860b;
            --gold-accent: #d4af37;
            --gold-light: #f5eccf;
            --gold-dark: #8a6508;
            --gold-darker: #6e5005;
            --gold-border: rgba(184, 134, 11, 0.22);
            --gold-border-hover: rgba(184, 134, 11, 0.5);
            
            --bg-body: #fbf9f5;
            --bg-card: #ffffff;
            --bg-card-alt: #f8f5ee;
            
            --text-title: #1a1917;
            --text-body: #3c3933;
            --text-muted: #736d62;
            
            --font-heading: 'Playfair Display', Georgia, serif;
            --font-cursive: 'Alex Brush', cursive;
            --font-body: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            
            --card-shadow: 0 10px 30px rgba(184, 134, 11, 0.07), 0 2px 6px rgba(0, 0, 0, 0.03);
            --card-shadow-hover: 0 16px 40px rgba(184, 134, 11, 0.14), 0 4px 10px rgba(0, 0, 0, 0.04);
            --transition-smooth: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-body);
            font-family: var(--font-body);
            min-height: 100vh;
            padding-top: 75px;
            margin: 0;
            overflow-x: hidden;
        }

        .admin-team-wrapper {
            max-width: 1300px;
            margin: 0 auto;
            padding: 35px 25px 80px;
            box-sizing: border-box;
        }

        /* Barra Superior */
        .top-nav-bar {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn-back-dashboard {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--gold-dark);
            background: #ffffff;
            border: 1px solid var(--gold-border);
            padding: 10px 22px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            transition: var(--transition-smooth);
            box-shadow: 0 2px 8px rgba(184, 134, 11, 0.08);
        }

        .btn-back-dashboard:hover {
            background: rgba(184, 134, 11, 0.1);
            border-color: var(--gold-primary);
            color: var(--gold-darker);
            transform: translateX(-3px);
            box-shadow: 0 4px 14px rgba(184, 134, 11, 0.18);
        }

        .btn-manage-services-top {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #b8860b, #8a6508);
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            transition: var(--transition-smooth);
            box-shadow: 0 3px 10px rgba(138, 101, 8, 0.25);
        }

        .btn-manage-services-top:hover {
            background: linear-gradient(135deg, #d4af37, #b8860b);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(138, 101, 8, 0.35);
        }

        /* Banner de Boas-Vindas e Título */
        .team-header-card {
            background: linear-gradient(135deg, #ffffff 0%, #faf6ec 100%);
            border: 1px solid var(--gold-border);
            border-radius: 20px;
            padding: 30px 35px;
            box-shadow: var(--card-shadow);
            margin-bottom: 35px;
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .team-header-card::after {
            content: '✂️';
            position: absolute;
            right: 25px;
            bottom: -15px;
            font-size: 100px;
            opacity: 0.08;
            pointer-events: none;
        }

        .header-tag-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            background: rgba(184, 134, 11, 0.1);
            border: 1px solid var(--gold-border);
            border-radius: 30px;
            color: var(--gold-dark);
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .team-main-title {
            font-family: var(--font-heading);
            font-size: clamp(24px, 3.2vw, 34px);
            font-weight: 700;
            color: var(--text-title);
            margin: 0 0 6px 0;
            line-height: 1.2;
        }

        .team-subtitle {
            font-size: 14.5px;
            color: var(--text-muted);
            max-width: 680px;
            line-height: 1.55;
            margin: 0;
        }

        /* Grid de Resumo / KPIs */
        .kpi-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 18px;
            margin-bottom: 35px;
        }

        .kpi-card {
            background: #ffffff;
            border: 1px solid var(--gold-border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: var(--transition-smooth);
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-shadow-hover);
            border-color: var(--gold-primary);
        }

        .kpi-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(184, 134, 11, 0.08);
            border: 1px solid rgba(184, 134, 11, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold-dark);
            flex-shrink: 0;
        }

        .kpi-info-val {
            font-family: var(--font-heading);
            font-size: 26px;
            font-weight: 700;
            color: var(--text-title);
            line-height: 1;
            margin-bottom: 4px;
        }

        .kpi-info-lbl {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Barra de Busca e Filtros */
        .controls-filter-bar {
            background: #ffffff;
            border: 1px solid var(--gold-border);
            border-radius: 16px;
            padding: 18px 24px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .search-box-wrap {
            position: relative;
            flex: 1;
            min-width: 260px;
        }

        .search-icon-svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #8c867a;
            pointer-events: none;
        }

        .search-input-field {
            width: 100%;
            padding: 11px 16px 11px 42px;
            border: 1px solid rgba(184, 134, 11, 0.3);
            border-radius: 10px;
            font-size: 14px;
            color: var(--text-body);
            background: #faf8f3;
            transition: var(--transition-smooth);
            outline: none;
            box-sizing: border-box;
        }

        .search-input-field:focus {
            background: #ffffff;
            border-color: var(--gold-primary);
            box-shadow: 0 0 0 3px rgba(184, 134, 11, 0.15);
        }

        .filters-group {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 10px 14px;
            border: 1px solid rgba(184, 134, 11, 0.3);
            border-radius: 10px;
            background: #faf8f3;
            color: var(--text-body);
            font-size: 13px;
            font-weight: 500;
            outline: none;
            cursor: pointer;
            transition: var(--transition-smooth);
        }

        .filter-select:focus, .filter-select:hover {
            border-color: var(--gold-primary);
            background: #ffffff;
        }

        .team-count-tag {
            font-size: 13px;
            color: var(--gold-dark);
            background: rgba(184, 134, 11, 0.1);
            border: 1px solid var(--gold-border);
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            white-space: nowrap;
        }

        /* Grid de Cards dos Funcionários */
        .team-luxury-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .team-card-luxury {
            background: #ffffff;
            border: 1px solid var(--gold-border);
            border-radius: 20px;
            padding: 28px;
            box-shadow: var(--card-shadow);
            transition: var(--transition-smooth);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .team-card-luxury:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
            border-color: var(--gold-primary);
        }

        /* Topo do Card */
        .team-card-top {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 20px;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(184, 134, 11, 0.15);
        }

        .team-avatar-frame {
            width: 74px;
            height: 74px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--gold-accent);
            box-shadow: 0 4px 12px rgba(184, 134, 11, 0.2);
            flex-shrink: 0;
            background: linear-gradient(135deg, #faf7ef, #f4ede0);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .team-avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .team-avatar-initials {
            font-family: var(--font-heading);
            font-size: 24px;
            font-weight: 700;
            color: var(--gold-dark);
            letter-spacing: 1px;
        }

        .team-info-header {
            flex: 1;
            min-width: 0;
        }

        .team-name-title {
            font-family: var(--font-heading);
            font-size: 19px;
            font-weight: 700;
            color: var(--text-title);
            margin: 0 0 4px 0;
            line-height: 1.2;
            word-break: break-word;
        }

        .team-specialty-badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            color: var(--gold-dark);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            background: rgba(184, 134, 11, 0.08);
            border: 1px solid var(--gold-border);
            padding: 3px 10px;
            border-radius: 20px;
            margin-top: 2px;
        }

        /* Corpo do Card */
        .team-card-body {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .card-info-item {
            font-size: 13.5px;
            color: var(--text-body);
            line-height: 1.5;
        }

        .card-info-label {
            font-weight: 700;
            color: var(--text-muted);
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 4px;
        }

        /* Badges */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .status-pill.active {
            background: rgba(46, 204, 113, 0.12);
            color: #1b8a47;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .status-pill.active .status-dot {
            background-color: #2ecc71;
            box-shadow: 0 0 6px #2ecc71;
        }

        .status-pill.inactive {
            background: rgba(231, 76, 60, 0.1);
            color: #b03a2e;
            border: 1px solid rgba(231, 76, 60, 0.25);
        }

        .status-pill.inactive .status-dot {
            background-color: #e74c3c;
        }

        .badge-agenda {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(184, 134, 11, 0.08);
            border: 1px solid var(--gold-border);
            color: var(--gold-dark);
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
        }

        .services-tag-list {
            font-size: 13px;
            color: var(--text-title);
            background: #faf8f3;
            border: 1px solid rgba(184, 134, 11, 0.15);
            padding: 8px 12px;
            border-radius: 8px;
            line-height: 1.4;
            max-height: 80px;
            overflow-y: auto;
        }

        .bio-quote-box {
            background: #faf7ee;
            border-left: 3px solid var(--gold-primary);
            padding: 8px 12px;
            border-radius: 0 8px 8px 0;
            font-size: 12.5px;
            color: var(--text-muted);
            font-style: italic;
            line-height: 1.45;
        }

        /* Rodapé de Ações */
        .team-card-actions {
            margin-top: 22px;
            padding-top: 16px;
            border-top: 1px solid rgba(184, 134, 11, 0.15);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-card-manage {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: linear-gradient(135deg, #b8860b, #8a6508);
            color: #ffffff;
            border: none;
            padding: 9px 12px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            transition: var(--transition-smooth);
            box-shadow: 0 2px 6px rgba(138, 101, 8, 0.2);
            cursor: pointer;
        }

        .btn-card-manage:hover {
            background: linear-gradient(135deg, #d4af37, #b8860b);
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(138, 101, 8, 0.3);
        }

        .btn-card-remove {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: #ffffff;
            color: #c0392b;
            border: 1px solid rgba(192, 57, 43, 0.3);
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-smooth);
            text-align: center;
        }

        .btn-card-remove:hover {
            background: #c0392b;
            color: #ffffff;
            border-color: #c0392b;
            box-shadow: 0 3px 8px rgba(192, 57, 43, 0.25);
            transform: translateY(-1px);
        }

        /* Estado Vazio */
        .empty-state-wrap {
            text-align: center;
            padding: 60px 20px;
            background: #ffffff;
            border: 1px solid var(--gold-border);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            color: var(--text-muted);
            grid-column: 1 / -1;
        }

        .empty-icon-circle {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(184, 134, 11, 0.08);
            border: 1px solid var(--gold-border);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: var(--gold-dark);
            font-size: 28px;
        }

        .empty-title {
            font-family: var(--font-heading);
            font-size: 20px;
            color: var(--text-title);
            margin-bottom: 6px;
        }

        /* Rodapé Informativo */
        .team-page-footer {
            margin-top: 45px;
            padding-top: 20px;
            border-top: 1px solid rgba(184, 134, 11, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .admin-team-wrapper {
                padding: 20px 15px 60px;
            }

            .team-header-card {
                padding: 22px 20px;
            }

            .team-luxury-grid {
                grid-template-columns: 1fr;
            }

            .controls-filter-bar {
                padding: 15px;
                flex-direction: column;
                align-items: stretch;
            }

            .filters-group {
                justify-content: space-between;
            }

            .filter-select {
                flex: 1;
            }
        }
    </style>
</head>
<body>

    <!-- Cabeçalho Oficial MB Studio -->
    <?php include '../components/Header.php'; ?>

    <main class="admin-team-wrapper">

        <!-- Barra Superior de Navegação -->
        <div class="top-nav-bar">
            <a href="Dashboard.php" class="btn-back-dashboard" title="Retornar ao painel principal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Voltar ao Dashboard</span>
            </a>

            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <a href="Servicos.php" class="btn-manage-services-top" title="Gerenciar e vincular serviços">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    <span>Atribuir Serviços</span>
                </a>
                <div class="team-count-tag" id="summary-counter">
                    Total: <strong><?= $totalFuncionarios ?></strong> profissional(is)
                </div>
            </div>
        </div>

        <!-- Banner de Título e Boas-Vindas -->
        <div class="team-header-card">
            <div>
                <div class="header-tag-pill">
                    <span>✦ Gestão de Especialistas & Staff ✦</span>
                </div>
                <h1 class="team-main-title">Gestão de Equipe (Funcionários)</h1>
                <p class="team-subtitle">
                    Acompanhe o quadro de profissionais do <strong>MB Studio</strong>, verifique especialidades, disponibilidade de horários na agenda e gerencie atribuições de serviços em tempo real.
                </p>
            </div>
        </div>

        <!-- Cards de Resumo (KPIs) -->
        <div class="kpi-summary-grid">
            <!-- 1. Total de Especialistas -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val"><?= $totalFuncionarios ?></div>
                    <div class="kpi-info-lbl">Profissionais</div>
                </div>
            </div>

            <!-- 2. Status Ativo / Inativo -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap" style="background: rgba(46, 204, 113, 0.1); border-color: rgba(46, 204, 113, 0.3); color: #27ae60;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val" style="font-size: 20px;">
                        <span style="color: #27ae60;"><?= $totalAtivos ?></span> <span style="font-size: 15px; color: #999;">/</span> <span style="color: #c0392b;"><?= $totalInativos ?></span>
                    </div>
                    <div class="kpi-info-lbl">Ativos / Inativos</div>
                </div>
            </div>

            <!-- 3. Com Serviços Vinculados -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val"><?= $totalComServicos ?></div>
                    <div class="kpi-info-lbl">Com Serviços</div>
                </div>
            </div>

            <!-- 4. Total de Horários na Agenda -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val"><?= $totalHorariosGeral ?></div>
                    <div class="kpi-info-lbl">Horários Totais</div>
                </div>
            </div>
        </div>

        <!-- Barra Interativa de Busca e Filtros -->
        <div class="controls-filter-bar">
            <div class="search-box-wrap">
                <svg class="search-icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input 
                    type="text" 
                    id="searchTeamInput" 
                    class="search-input-field" 
                    placeholder="Pesquisar por nome, especialidade ou serviço..."
                    onkeyup="filterTeamGrid()"
                >
            </div>

            <div class="filters-group">
                <!-- Filtro por Status -->
                <select id="statusTeamFilter" class="filter-select" onchange="filterTeamGrid()">
                    <option value="ALL">Todos os Status</option>
                    <option value="1">🟢 Apenas Ativos</option>
                    <option value="0">🔴 Apenas Inativos</option>
                </select>

                <!-- Filtro por Serviços Atribuídos -->
                <select id="servicesTeamFilter" class="filter-select" onchange="filterTeamGrid()">
                    <option value="ALL">Todos os Serviços</option>
                    <option value="COM">Com Serviços Atribuídos</option>
                    <option value="SEM">Sem Serviços Atribuídos</option>
                </select>
            </div>
        </div>

        <!-- Grid de Cards da Equipe -->
        <?php if(count($funcionarios) > 0): ?>
            <div class="team-luxury-grid" id="teamCardsGrid">
                <?php foreach($funcionarios as $f): 
                    $hasPhoto = !empty($f['Emp_photo']);
                    $iniciais = getIniciais($f['User_name']);
                    $temServicos = !empty($f['servicos_atribuidos']);
                ?>
                    <div 
                        class="team-card-luxury team-card-item"
                        data-name="<?= htmlspecialchars(mb_strtolower($f['User_name'])) ?>"
                        data-specialty="<?= htmlspecialchars(mb_strtolower($f['Emp_specialty'] ?: '')) ?>"
                        data-services="<?= htmlspecialchars(mb_strtolower($f['servicos_atribuidos'] ?: '')) ?>"
                        data-active="<?= $f['User_active'] ?>"
                        data-has-services="<?= $temServicos ? 'COM' : 'SEM' ?>"
                    >
                        
                        <!-- Topo do Card: Foto, Nome e Especialidade -->
                        <div class="team-card-top">
                            <div class="team-avatar-frame">
                                <?php if($hasPhoto): ?>
                                    <img src="../../<?= htmlspecialchars($f['Emp_photo']) ?>" alt="Foto de <?= htmlspecialchars($f['User_name']) ?>" class="team-avatar-img">
                                <?php else: ?>
                                    <div class="team-avatar-initials">
                                        <?= $iniciais ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="team-info-header">
                                <h3 class="team-name-title"><?= htmlspecialchars($f['User_name']) ?></h3>
                                <span class="team-specialty-badge">
                                    <?= htmlspecialchars($f['Emp_specialty'] ?: 'Profissional da Beleza') ?>
                                </span>
                            </div>
                        </div>

                        <!-- Corpo do Card: Informações Detalhadas -->
                        <div class="team-card-body">
                            
                            <!-- Status -->
                            <div class="card-info-item">
                                <span class="card-info-label">Status da Conta</span>
                                <?php if($f['User_active'] == 1): ?>
                                    <span class="status-pill active">
                                        <span class="status-dot"></span> Ativo no Sistema
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill inactive">
                                        <span class="status-dot"></span> Inativo
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Agenda -->
                            <div class="card-info-item">
                                <span class="card-info-label">Agenda do Profissional</span>
                                <span class="badge-agenda">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <?= $f['total_horarios'] ?> horários cadastrados
                                </span>
                            </div>

                            <!-- Serviços Atribuídos -->
                            <div class="card-info-item">
                                <span class="card-info-label">Serviços Atribuídos</span>
                                <div class="services-tag-list">
                                    <?= htmlspecialchars($f['servicos_atribuidos'] ?: 'Nenhum serviço atribuído no momento.') ?>
                                </div>
                            </div>

                            <!-- Bio / Descrição Curta -->
                            <?php if(!empty($f['Emp_bio'])): ?>
                                <div class="card-info-item" style="margin-top: 4px;">
                                    <div class="bio-quote-box">
                                        "<?= htmlspecialchars(substr($f['Emp_bio'], 0, 110)) ?><?= strlen($f['Emp_bio']) > 110 ? '...' : '' ?>"
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- Rodapé do Card: Ações -->
                        <div class="team-card-actions">
                            <a href="Servicos.php" class="btn-card-manage" title="Gerenciar catálogo de serviços">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                                Atribuir Serviços
                            </a>

                            <!-- Formulário para remover da equipe (alterar permissão para 'C') -->
                            <form action="../../controller/AlterarPermissao.php" method="POST" onsubmit="return confirm('Tem certeza que deseja rebaixar este funcionário para Cliente? Ele perderá acesso ao painel de equipe.');" style="flex: 1; margin: 0;">
                                <input type="hidden" name="User_id" value="<?= $f['User_id'] ?>">
                                <input type="hidden" name="User_perm" value="C">
                                <button type="submit" class="btn-card-remove" style="width: 100%;" title="Rebaixar permissão para Cliente">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="15" y1="9" x2="9" y2="15"></line>
                                        <line x1="9" y1="9" x2="15" y2="15"></line>
                                    </svg>
                                    Remover Equipe
                                </button>
                            </form>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Estado quando a pesquisa em tempo real não encontra resultados -->
            <div id="noTeamResultsMessage" class="empty-state-wrap" style="display: none; margin-top: 20px;">
                <div class="empty-icon-circle">🔍</div>
                <h3 class="empty-title">Nenhum profissional encontrado</h3>
                <p style="margin: 0; font-size: 14px;">Nenhum membro da equipe corresponde aos filtros ou termo pesquisado.</p>
            </div>

        <?php else: ?>
            <!-- Estado quando não há funcionários cadastrados no banco -->
            <div class="empty-state-wrap">
                <div class="empty-icon-circle">✂️</div>
                <h3 class="empty-title">Nenhum membro na equipe</h3>
                <p style="margin: 0; font-size: 14px;">No momento, não há usuários cadastrados com a permissão 'F' (Funcionário).</p>
            </div>
        <?php endif; ?>

        <!-- Rodapé do Painel -->
        <div class="team-page-footer">
            <div>
                <strong>MB Studio Admin</strong> — Gestão de Especialistas & Staff
            </div>
            <div>
                © <?= date('Y') ?> Todos os direitos reservados.
            </div>
        </div>

    </main>

    <!-- Script de Filtro Instantâneo em Tempo Real (Vanilla JS) -->
    <script>
        function filterTeamGrid() {
            const searchInput = document.getElementById('searchTeamInput').value.toLowerCase().trim();
            const statusFilter = document.getElementById('statusTeamFilter').value;
            const servicesFilter = document.getElementById('servicesTeamFilter').value;
            const cards = document.querySelectorAll('.team-card-item');
            const noResultsMsg = document.getElementById('noTeamResultsMessage');
            
            let visibleCount = 0;

            cards.forEach(card => {
                const name = card.getAttribute('data-name') || '';
                const specialty = card.getAttribute('data-specialty') || '';
                const services = card.getAttribute('data-services') || '';
                const active = card.getAttribute('data-active') || '';
                const hasServices = card.getAttribute('data-has-services') || '';

                const matchesSearch = (!searchInput) || 
                                      name.includes(searchInput) || 
                                      specialty.includes(searchInput) || 
                                      services.includes(searchInput);

                const matchesStatus = (statusFilter === 'ALL') || (active === statusFilter);
                const matchesServices = (servicesFilter === 'ALL') || (hasServices === servicesFilter);

                if (matchesSearch && matchesStatus && matchesServices) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Atualizar o contador no topo
            const counterElement = document.getElementById('summary-counter');
            if (counterElement) {
                counterElement.innerHTML = `Exibindo: <strong>${visibleCount}</strong> de <strong>${cards.length}</strong> profissional(is)`;
            }

            // Exibir mensagem de nenhum resultado se visível for zero
            if (noResultsMsg) {
                noResultsMsg.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
            }
        }
    </script>
</body>
</html>
