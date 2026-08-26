<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";
require_once "../../model/Service.php";

Session::iniciar();

// Proteção Admin
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'A') {
    header("Location: ../../Index.php");
    exit;
}

$serviceModel = new Service($pdo);
$servicos = $serviceModel->index();

require_once "../../model/User.php";

$userModel = new User($pdo);
// Obter todos os usuários marcados como 'F' (Funcionário), ignorando a tabela employees inicialmente
$funcionarios = $userModel->buscarPorPermissao('F');
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Serviços | MB Studio Admin</title>

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap"
        rel="stylesheet">

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

        .admin-services-wrapper {
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

        .btn-toggle-new-service {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #b8860b, #8a6508);
            color: #ffffff;
            border: none;
            padding: 10px 22px;
            border-radius: 30px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-smooth);
            box-shadow: 0 3px 10px rgba(138, 101, 8, 0.25);
            text-decoration: none;
        }

        .btn-toggle-new-service:hover {
            background: linear-gradient(135deg, #d4af37, #b8860b);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(138, 101, 8, 0.35);
        }

        /* Banner de Título */
        .services-header-card {
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

        .services-header-card::after {
            content: '✨';
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

        .services-main-title {
            font-family: var(--font-heading);
            font-size: clamp(24px, 3.2vw, 34px);
            font-weight: 700;
            color: var(--text-title);
            margin: 0 0 6px 0;
            line-height: 1.2;
        }

        .services-subtitle {
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

        /* Card de Cadastro / Formulário Novo Serviço */
        .form-section-card {
            background: #ffffff;
            border: 1px solid var(--gold-border);
            border-radius: 20px;
            padding: 32px;
            box-shadow: var(--card-shadow);
            margin-bottom: 40px;
            position: relative;
        }

        .form-card-title {
            font-family: var(--font-heading);
            font-size: 22px;
            font-weight: 700;
            color: var(--text-title);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-card-desc {
            font-size: 13.5px;
            color: var(--text-muted);
            margin-bottom: 25px;
        }

        .form-grid-two-cols {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group-luxury {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group-full {
            grid-column: 1 / -1;
        }

        .label-luxury {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text-title);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-luxury,
        .textarea-luxury,
        .select-luxury {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(184, 134, 11, 0.3);
            border-radius: 10px;
            font-size: 14px;
            color: var(--text-body);
            background: #faf8f3;
            transition: var(--transition-smooth);
            outline: none;
            box-sizing: border-box;
            font-family: var(--font-body);
        }

        .input-luxury:focus,
        .textarea-luxury:focus,
        .select-luxury:focus {
            background: #ffffff;
            border-color: var(--gold-primary);
            box-shadow: 0 0 0 3px rgba(184, 134, 11, 0.15);
        }

        .textarea-luxury {
            resize: vertical;
            min-height: 85px;
        }

        /* Checkboxes de Funcionários Estilizados */
        .employees-checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 10px;
            background: #faf8f3;
            border: 1px solid rgba(184, 134, 11, 0.2);
            padding: 15px;
            border-radius: 12px;
            max-height: 160px;
            overflow-y: auto;
        }

        .checkbox-pill-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-body);
            background: #ffffff;
            border: 1px solid rgba(184, 134, 11, 0.2);
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition-smooth);
            user-select: none;
        }

        .checkbox-pill-label:hover {
            border-color: var(--gold-primary);
            background: #faf6ec;
        }

        .checkbox-pill-label input[type="checkbox"] {
            accent-color: var(--gold-primary);
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .btn-submit-luxury {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, #b8860b, #8a6508);
            color: #ffffff;
            border: none;
            padding: 13px 30px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition-smooth);
            box-shadow: 0 4px 12px rgba(138, 101, 8, 0.25);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-submit-luxury:hover {
            background: linear-gradient(135deg, #d4af37, #b8860b);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(138, 101, 8, 0.35);
        }

        /* Barra de Busca e Filtros */
        .controls-filter-bar {
            background: #ffffff;
            border: 1px solid var(--gold-border);
            border-radius: 16px;
            padding: 18px 24px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
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

        .filter-select:focus,
        .filter-select:hover {
            border-color: var(--gold-primary);
            background: #ffffff;
        }

        .services-count-tag {
            font-size: 13px;
            color: var(--gold-dark);
            background: rgba(184, 134, 11, 0.1);
            border: 1px solid var(--gold-border);
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            white-space: nowrap;
        }

        /* Tabela Principal */
        .table-card-container {
            background: var(--bg-card);
            border: 1px solid var(--gold-border);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .table-responsive-scroll {
            width: 100%;
            overflow-x: auto;
        }

        .luxury-services-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            text-align: left;
        }

        .luxury-services-table th {
            background: linear-gradient(180deg, #faf7ef 0%, #f4ede0 100%);
            color: var(--gold-darker);
            font-size: 12.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--gold-border);
            white-space: nowrap;
        }

        .luxury-services-table td {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(184, 134, 11, 0.12);
            vertical-align: middle;
            font-size: 14px;
            transition: background 0.2s ease;
        }

        .luxury-services-table tbody tr:hover td {
            background-color: #faf7ee;
        }

        .luxury-services-table tbody tr:last-child td {
            border-bottom: none;
        }

        .id-badge {
            font-family: monospace;
            font-size: 12px;
            font-weight: 700;
            color: var(--gold-dark);
            background: rgba(184, 134, 11, 0.08);
            border: 1px solid rgba(184, 134, 11, 0.2);
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-block;
        }

        .service-thumb-wrap {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            overflow: hidden;
            border: 1.5px solid var(--gold-border);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            background: #faf8f3;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .service-thumb-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .service-name-text {
            font-family: var(--font-heading);
            font-size: 16px;
            font-weight: 700;
            color: var(--text-title);
            margin-bottom: 3px;
        }

        .service-desc-text {
            font-size: 12.5px;
            color: var(--text-muted);
            line-height: 1.4;
            max-width: 280px;
        }

        .price-badge {
            font-family: var(--font-heading);
            font-size: 15.5px;
            font-weight: 700;
            color: var(--gold-darker);
            background: rgba(184, 134, 11, 0.08);
            border: 1px solid rgba(184, 134, 11, 0.2);
            padding: 4px 10px;
            border-radius: 8px;
            white-space: nowrap;
        }

        .duration-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
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

        /* Edição Inline em Accordion / Details */
        details.edit-service-details {
            display: inline-block;
            margin-bottom: 6px;
        }

        details.edit-service-details summary {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #ffffff;
            color: var(--gold-dark);
            border: 1px solid var(--gold-border);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-smooth);
            user-select: none;
            outline: none;
        }

        details.edit-service-details summary:hover {
            background: rgba(184, 134, 11, 0.1);
            border-color: var(--gold-primary);
        }

        .edit-form-box {
            margin-top: 12px;
            background: #ffffff;
            border: 1px solid var(--gold-primary);
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 8px 24px rgba(184, 134, 11, 0.15);
            text-align: left;
            min-width: 290px;
            max-width: 400px;
        }

        .btn-delete-luxury {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #ffffff;
            color: #c0392b;
            border: 1px solid rgba(192, 57, 43, 0.3);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition-smooth);
        }

        .btn-delete-luxury:hover {
            background: #c0392b;
            color: #ffffff;
            border-color: #c0392b;
            box-shadow: 0 3px 8px rgba(192, 57, 43, 0.25);
            transform: translateY(-1px);
        }

        .btn-update-service {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            background: linear-gradient(135deg, #b8860b, #8a6508);
            color: #ffffff;
            border: none;
            padding: 9px 16px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition-smooth);
            margin-top: 10px;
        }

        .btn-update-service:hover {
            background: linear-gradient(135deg, #d4af37, #b8860b);
            box-shadow: 0 4px 10px rgba(138, 101, 8, 0.3);
        }

        /* Estado Vazio */
        .empty-state-wrap {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-muted);
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
        .services-page-footer {
            margin-top: 40px;
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
        @media (max-width: 900px) {
            .admin-services-wrapper {
                padding: 20px 15px 60px;
            }

            .services-header-card,
            .form-section-card {
                padding: 22px 20px;
            }

            .controls-filter-bar {
                padding: 15px;
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>

<body>

    <!-- Cabeçalho Oficial MB Studio -->
    <?php include '../components/Header.php'; ?>

    <main class="admin-services-wrapper">

        <!-- Barra Superior de Navegação -->
        <div class="top-nav-bar">
            <a href="Dashboard.php" class="btn-back-dashboard" title="Retornar ao painel principal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Voltar ao Dashboard</span>
            </a>

            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <a href="#novo-servico" class="btn-toggle-new-service">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Cadastrar Novo Serviço</span>
                </a>
                <div class="services-count-tag" id="summary-counter">
                    Total: <strong>
                        <?= $totalServicos ?>
                    </strong> serviço(s)
                </div>
            </div>
        </div>

        <!-- Banner de Boas-Vindas e Título -->
        <div class="services-header-card">
            <div>
                <div class="header-tag-pill">
                    <span>✦ Catálogo de Procedimentos & Preços ✦</span>
                </div>
                <h1 class="services-main-title">Gerenciamento de Serviços</h1>
                <p class="services-subtitle">
                    Cadastre novos procedimentos de beleza, configure valores, duração e vincule os especialistas
                    responsáveis pelo atendimento no <strong>MB Studio</strong>.
                </p>
            </div>
        </div>

        <!-- Cards de Resumo (KPIs) -->
        <div class="kpi-summary-grid">
            <!-- 1. Total Geral -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polygon
                            points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2">
                        </polygon>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val">
                        <?= $totalServicos ?>
                    </div>
                    <div class="kpi-info-lbl">Total de Serviços</div>
                </div>
            </div>

            <!-- 2. Status Ativos / Inativos -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap"
                    style="background: rgba(46, 204, 113, 0.1); border-color: rgba(46, 204, 113, 0.3); color: #27ae60;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val" style="font-size: 20px;">
                        <span style="color: #27ae60;">
                            <?= $totalAtivos ?>
                        </span> <span style="font-size: 15px; color: #999;">/</span> <span style="color: #c0392b;">
                            <?= $totalInativos ?>
                        </span>
                    </div>
                    <div class="kpi-info-lbl">Ativos / Inativos</div>
                </div>
            </div>

            <!-- 3. Preço Médio -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val" style="font-size: 22px;">R$
                        <?= number_format($precoMedio, 2, ',', '.') ?>
                    </div>
                    <div class="kpi-info-lbl">Preço Médio</div>
                </div>
            </div>

            <!-- 4. Profissionais Aptos -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val">
                        <?= count($funcionarios) ?>
                    </div>
                    <div class="kpi-info-lbl">Especialistas na Equipe</div>
                </div>
            </div>
        </div>

        <!-- FORMULÁRIO DE CADASTRO DE NOVO SERVIÇO -->
        <section class="form-section-card" id="novo-servico">
            <h2 class="form-card-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                    stroke-linecap="round" stroke-linejoin="round" style="color: var(--gold-dark);">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
                Cadastrar Novo Serviço (Anúncio)
            </h2>
            <p class="form-card-desc">Preencha os campos abaixo para disponibilizar um novo procedimento para
                agendamento.</p>

            <form action="../../controller/SalvarServico.php" method="POST" enctype="multipart/form-data">
                <div class="form-grid-two-cols">
                    <!-- Nome -->
                    <div class="form-group-luxury">
                        <label class="label-luxury">Nome do Serviço: *</label>
                        <input type="text" name="nome" class="input-luxury" placeholder="Ex: Corte Feminino & Escova"
                            required>
                    </div>

                    <!-- Preço -->
                    <div class="form-group-luxury">
                        <label class="label-luxury">Preço (R$): *</label>
                        <input type="number" step="0.01" name="preco" class="input-luxury" placeholder="Ex: 85.00"
                            required>
                    </div>

                    <!-- Duração -->
                    <div class="form-group-luxury">
                        <label class="label-luxury">Duração Estimada (Minutos): *</label>
                        <input type="number" name="duracao" class="input-luxury" placeholder="Ex: 45" required>
                    </div>

                    <!-- Imagem -->
                    <div class="form-group-luxury">
                        <label class="label-luxury">Foto do Anúncio (Imagem): *</label>
                        <input type="file" name="imagem" accept="image/*" class="input-luxury" style="padding: 9px;"
                            required>
                    </div>

                    <!-- Descrição -->
                    <div class="form-group-luxury form-group-full">
                        <label class="label-luxury">Descrição do Procedimento:</label>
                        <textarea name="descricao" class="textarea-luxury"
                            placeholder="Descreva os detalhes do serviço, benefícios e orientações para o cliente..."></textarea>
                    </div>

                    <!-- Atribuir Funcionários -->
                    <div class="form-group-luxury form-group-full">
                        <label class="label-luxury">Atribuir Funcionários Aptos para este Serviço:</label>
                        <?php if (count($funcionarios) > 0): ?>
                            <div class="employees-checkbox-grid">
                                <?php foreach ($funcionarios as $func): ?>
                                    <label class="checkbox-pill-label">
                                        <input type="checkbox" name="funcionarios[]" value="<?= $func['User_id'] ?>">
                                        <span>
                                            <?= htmlspecialchars($func['User_name']) ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="font-size: 13px; color: #8c867a; margin: 5px 0;">
                                Nenhum funcionário com permissão 'F' cadastrado. Cadastre primeiro em <a
                                    href="Funcionarios.php" style="color: var(--gold-dark); font-weight: 700;">Gestão de
                                    Equipe</a>.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-top: 25px; text-align: right;">
                    <button type="submit" class="btn-submit-luxury">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Salvar Serviço
                    </button>
                </div>
            </form>
        </section>

        <!-- Barra Interativa de Busca e Filtros -->
        <div class="controls-filter-bar">
            <div class="search-box-wrap">
                <svg class="search-icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="searchServiceInput" class="search-input-field"
                    placeholder="Pesquisar serviço por nome, descrição ou funcionário..."
                    onkeyup="filterServicesTable()">
            </div>

            <div>
                <select id="statusServiceFilter" class="filter-select" onchange="filterServicesTable()">
                    <option value="ALL">Todos os Status</option>
                    <option value="1">🟢 Apenas Ativos</option>
                    <option value="0">🔴 Apenas Inativos</option>
                </select>
            </div>
        </div>

        <!-- LISTAGEM DE SERVIÇOS -->
        <div class="table-card-container">
            <?php if (count($servicos) > 0): ?>
                <div class="table-responsive-scroll">
                    <table class="luxury-services-table" id="servicesTable">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th style="width: 80px;">Imagem</th>
                                <th>Serviço & Detalhes</th>
                                <th style="width: 120px;">Preço</th>
                                <th style="width: 110px;">Duração</th>
                                <th>Funcionários</th>
                                <th style="width: 120px;">Status</th>
                                <th style="width: 180px; text-align: right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($servicos as $s):
                                $isActive = !empty($s['Ser_active']);
                                $hasImage = !empty($s['Ser_image']);
                                ?>
                                <tr class="service-table-row" data-id="<?= $s['Ser_id'] ?>"
                                    data-name="<?= htmlspecialchars(mb_strtolower($s['Ser_name'])) ?>"
                                    data-desc="<?= htmlspecialchars(mb_strtolower($s['Ser_description'] ?? '')) ?>"
                                    data-emp="<?= htmlspecialchars(mb_strtolower($s['Employees'] ?? '')) ?>"
                                    data-active="<?= $isActive ? '1' : '0' ?>">
                                    <!-- ID -->
                                    <td>
                                        <span class="id-badge">#
                                            <?= $s['Ser_id'] ?>
                                        </span>
                                    </td>

                                    <!-- Imagem -->
                                    <td>
                                        <div class="service-thumb-wrap">
                                            <?php if ($hasImage): ?>
                                                <img src="../../<?= htmlspecialchars($s['Ser_image']) ?>" alt="Imagem do Serviço"
                                                    class="service-thumb-img">
                                            <?php else: ?>
                                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#d4af37"
                                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                    <polyline points="21 15 16 10 5 21"></polyline>
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Nome e Descrição -->
                                    <td>
                                        <div class="service-name-text">
                                            <?= htmlspecialchars($s['Ser_name']) ?>
                                        </div>
                                        <?php if (!empty($s['Ser_description'])): ?>
                                            <div class="service-desc-text">
                                                <?= htmlspecialchars($s['Ser_description']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Preço -->
                                    <td>
                                        <span class="price-badge">R$
                                            <?= number_format($s['Ser_price'], 2, ',', '.') ?>
                                        </span>
                                    </td>

                                    <!-- Duração -->
                                    <td>
                                        <span class="duration-badge">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                            <?= $s['Ser_duration'] ?> min
                                        </span>
                                    </td>

                                    <!-- Funcionários Atribuídos -->
                                    <td>
                                        <span style="font-size: 13px; color: var(--text-body); font-weight: 500;">
                                            <?= htmlspecialchars($s['Employees'] ?: 'Nenhum profissional vinculado') ?>
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="status-pill active">
                                                <span class="status-dot"></span> Ativo
                                            </span>
                                        <?php else: ?>
                                            <span class="status-pill inactive">
                                                <span class="status-dot"></span> Inativo
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Ações (Editar com Details / Excluir) -->
                                    <td style="text-align: right;">
                                        <!-- INTERAÇÃO COM DETAILS PARA EDIÇÃO -->
                                        <details class="edit-service-details">
                                            <summary title="Editar dados do serviço">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2.2" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                                Editar
                                            </summary>

                                            <div class="edit-form-box">
                                                <form action="../../controller/EditarServico.php" method="POST"
                                                    enctype="multipart/form-data">
                                                    <input type="hidden" name="id" value="<?= $s['Ser_id'] ?>">
                                                    <input type="hidden" name="imagem_atual"
                                                        value="<?= htmlspecialchars($s['Ser_image'] ?? '') ?>">

                                                    <div style="margin-bottom: 10px;">
                                                        <label class="label-luxury" style="font-size: 11px;">Nome:</label>
                                                        <input type="text" name="nome"
                                                            value="<?= htmlspecialchars($s['Ser_name']) ?>" class="input-luxury"
                                                            style="padding: 7px 10px; font-size: 13px;" required>
                                                    </div>

                                                    <div style="margin-bottom: 10px;">
                                                        <label class="label-luxury" style="font-size: 11px;">Descrição:</label>
                                                        <textarea name="descricao" class="textarea-luxury"
                                                            style="min-height: 60px; padding: 7px 10px; font-size: 13px;"><?= htmlspecialchars($s['Ser_description'] ?? '') ?></textarea>
                                                    </div>

                                                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                                        <div style="flex: 1;">
                                                            <label class="label-luxury" style="font-size: 11px;">Preço
                                                                (R$):</label>
                                                            <input type="number" step="0.01" name="preco"
                                                                value="<?= $s['Ser_price'] ?>" class="input-luxury"
                                                                style="padding: 7px 10px; font-size: 13px;" required>
                                                        </div>
                                                        <div style="flex: 1;">
                                                            <label class="label-luxury" style="font-size: 11px;">Duração
                                                                (min):</label>
                                                            <input type="number" name="duracao"
                                                                value="<?= $s['Ser_duration'] ?>" class="input-luxury"
                                                                style="padding: 7px 10px; font-size: 13px;" required>
                                                        </div>
                                                    </div>

                                                    <div style="margin-bottom: 10px;">
                                                        <label class="label-luxury" style="font-size: 11px;">Nova Imagem
                                                            (Opcional):</label>
                                                        <input type="file" name="imagem" accept="image/*" class="input-luxury"
                                                            style="padding: 6px; font-size: 12px;">
                                                    </div>

                                                    <div style="margin-bottom: 10px;">
                                                        <label class="label-luxury" style="font-size: 11px;">Status:</label>
                                                        <select name="ativo" class="select-luxury"
                                                            style="padding: 7px 10px; font-size: 13px;">
                                                            <option value="1" <?= $s['Ser_active'] == 1 ? 'selected' : '' ?>>🟢
                                                                Ativo</option>
                                                            <option value="0" <?= $s['Ser_active'] == 0 ? 'selected' : '' ?>>🔴
                                                                Inativo</option>
                                                        </select>
                                                    </div>

                                                    <div style="margin-bottom: 12px;">
                                                        <label class="label-luxury" style="font-size: 11px;">Reatribuir
                                                            Funcionários:</label>
                                                        <div
                                                            style="max-height: 90px; overflow-y: auto; background: #faf8f3; padding: 8px; border-radius: 6px; border: 1px solid rgba(184,134,11,0.2);">
                                                            <?php foreach ($funcionarios as $func): ?>
                                                                <label
                                                                    style="display: flex; align-items: center; gap: 6px; font-size: 12px; margin-bottom: 4px; cursor: pointer;">
                                                                    <input type="checkbox" name="funcionarios[]"
                                                                        value="<?= $func['User_id'] ?>">
                                                                    <span>
                                                                        <?= htmlspecialchars($func['User_name']) ?>
                                                                    </span>
                                                                </label>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>

                                                    <button type="submit" class="btn-update-service">Salvar Alterações</button>
                                                </form>
                                            </div>
                                        </details>

                                        <!-- AÇÃO DE EXCLUIR -->
                                        <a href="../../controller/ExcluirServico.php?id=<?= $s['Ser_id'] ?>"
                                            class="btn-delete-luxury"
                                            onclick="return confirm('Tem certeza que deseja excluir o serviço \'<?= htmlspecialchars(addslashes($s['Ser_name'])) ?>\'?');"
                                            title="Excluir serviço">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                                </path>
                                            </svg>
                                            Excluir
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Estado quando a pesquisa em tempo real não encontra resultados -->
                <div id="noServiceResultsMessage" class="empty-state-wrap" style="display: none;">
                    <div class="empty-icon-circle">🔍</div>
                    <h3 class="empty-title">Nenhum serviço encontrado</h3>
                    <p style="margin: 0; font-size: 14px;">Nenhum procedimento corresponde aos filtros ou ao termo
                        pesquisado.</p>
                </div>

            <?php else: ?>
                <!-- Estado quando a tabela no banco está vazia -->
                <div class="empty-state-wrap">
                    <div class="empty-icon-circle">✨</div>
                    <h3 class="empty-title">Nenhum serviço cadastrado</h3>
                    <p style="margin: 0; font-size: 14px;">Cadastre seu primeiro serviço utilizando o formulário acima.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Rodapé Informativo -->
        <div class="services-page-footer">
            <div>
                <strong>MB Studio Admin</strong> — Catálogo & Gerenciamento de Serviços
            </div>
            <div>
                ©
                <?= date('Y') ?> Todos os direitos reservados.
            </div>
        </div>

    </main>

    <!-- Script de Filtro Instantâneo em Tempo Real (Vanilla JS) -->
    <script>
        function filterServicesTable() {
            const searchInput = document.getElementById('searchServiceInput').value.toLowerCase().trim();
            const statusFilter = document.getElementById('statusServiceFilter').value;
            const rows = document.querySelectorAll('.service-table-row');
            const noResultsMsg = document.getElementById('noServiceResultsMessage');

            let visibleCount = 0;

            rows.forEach(row => {
                const id = row.getAttribute('data-id') || '';
                const name = row.getAttribute('data-name') || '';
                const desc = row.getAttribute('data-desc') || '';
                const emp = row.getAttribute('data-emp') || '';
                const active = row.getAttribute('data-active') || '';

                const matchesSearch = (!searchInput) ||
                    name.includes(searchInput) ||
                    desc.includes(searchInput) ||
                    emp.includes(searchInput) ||
                    id.includes(searchInput);

                const matchesStatus = (statusFilter === 'ALL') || (active === statusFilter);

                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Atualizar o contador no topo
            const counterElement = document.getElementById('summary-counter');
            if (counterElement) {
                counterElement.innerHTML = `Exibindo: <strong>${visibleCount}</strong> de <strong>${rows.length}</strong> serviço(s)`;
            }

            // Exibir mensagem de nenhum resultado se visível for zero
            if (noResultsMsg) {
                noResultsMsg.style.display = (visibleCount === 0 && rows.length > 0) ? 'block' : 'none';
            }
        }
    </script>
</body>

</html>