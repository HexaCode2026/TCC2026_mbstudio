<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";
require_once "../../model/User.php";

Session::iniciar();

// Proteção Admin
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'A') {
    header("Location: ../../Index.php");
    exit;
}

$userModel = new User($pdo);
$usuarios = $userModel->listarTodos();

// Cálculos para os indicadores de resumo
$totalUsuarios = count($usuarios);
$totalClientes = 0;
$totalFuncionarios = 0;
$totalAdmins = 0;
$totalAtivos = 0;
$totalInativos = 0;

foreach ($usuarios as $u) {
    if ($u['User_perm'] == 'C') $totalClientes++;
    elseif ($u['User_perm'] == 'F') $totalFuncionarios++;
    elseif ($u['User_perm'] == 'A') $totalAdmins++;
    
    if ($u['User_active'] == 1) $totalAtivos++;
    else $totalInativos++;
}

// Função auxiliar para gerar iniciais do nome para o avatar
function getIniciais($nome) {
    $partes = preg_split('/\s+/', trim($nome));
    $iniciais = '';
    if (count($partes) >= 2) {
        $iniciais = mb_substr($partes[0], 0, 1) . mb_substr(end($partes), 0, 1);
    } elseif (count($partes) === 1 && !empty($partes[0])) {
        $iniciais = mb_substr($partes[0], 0, 2);
    }
    return strtoupper($iniciais ?: 'US');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Usuários | MB Studio</title>

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

        .admin-users-wrapper {
            max-width: 1300px;
            margin: 0 auto;
            padding: 35px 25px 80px;
            box-sizing: border-box;
        }

        /* Botão Voltar */
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

        /* Banner de Título */
        .users-header-card {
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

        .users-header-card::after {
            content: '👥';
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

        .users-main-title {
            font-family: var(--font-heading);
            font-size: clamp(24px, 3.2vw, 34px);
            font-weight: 700;
            color: var(--text-title);
            margin: 0 0 6px 0;
            line-height: 1.2;
        }

        .users-subtitle {
            font-size: 14.5px;
            color: var(--text-muted);
            max-width: 650px;
            line-height: 1.55;
            margin: 0;
        }

        /* Grid de Resumo / Contadores Rápidos */
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

        .users-count-tag {
            font-size: 13px;
            color: var(--gold-dark);
            background: rgba(184, 134, 11, 0.1);
            border: 1px solid var(--gold-border);
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            white-space: nowrap;
        }

        /* Card da Tabela Principal */
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

        .luxury-users-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            text-align: left;
        }

        .luxury-users-table th {
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

        .luxury-users-table td {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(184, 134, 11, 0.12);
            vertical-align: middle;
            font-size: 14px;
            transition: background 0.2s ease;
        }

        .luxury-users-table tbody tr:hover td {
            background-color: #faf7ee;
        }

        .luxury-users-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Linha destacando a conta atual */
        .row-self-account {
            background-color: rgba(212, 175, 55, 0.04);
        }

        /* Componentes de Linha */
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

        .user-identity-cell {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .user-avatar-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #d4af37, #8a6508);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.5px;
            box-shadow: 0 3px 8px rgba(184, 134, 11, 0.25);
            flex-shrink: 0;
            border: 2px solid #ffffff;
        }

        .user-name-text {
            font-weight: 600;
            color: var(--text-title);
            font-size: 14.5px;
            line-height: 1.2;
            margin-bottom: 3px;
        }

        .user-email-text {
            font-size: 12.5px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Badges de Status com Luz Indicadora */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
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

        /* Formulário de Permissão */
        .perm-form-container {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .select-luxury-perm {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid var(--gold-border);
            background: #ffffff;
            color: var(--text-title);
            font-size: 13px;
            font-weight: 600;
            outline: none;
            cursor: pointer;
            transition: var(--transition-smooth);
        }

        .select-luxury-perm:focus, .select-luxury-perm:hover {
            border-color: var(--gold-primary);
            box-shadow: 0 0 0 2px rgba(184, 134, 11, 0.15);
        }

        .btn-luxury-save {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: linear-gradient(135deg, #b8860b, #8a6508);
            color: #ffffff;
            border: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition-smooth);
            box-shadow: 0 2px 6px rgba(138, 101, 8, 0.2);
        }

        .btn-luxury-save:hover {
            background: linear-gradient(135deg, #d4af37, #b8860b);
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(138, 101, 8, 0.3);
        }

        /* Ações Rápidas (Ativar / Desativar / Excluir) */
        .actions-cell-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-action-luxury {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-smooth);
            text-decoration: none;
            border: 1px solid transparent;
        }

        /* Botão Desativar */
        .btn-toggle-off {
            background: #ffffff;
            color: #d35400;
            border-color: rgba(211, 84, 0, 0.3);
        }

        .btn-toggle-off:hover {
            background: #d35400;
            color: #ffffff;
            box-shadow: 0 3px 8px rgba(211, 84, 0, 0.25);
            transform: translateY(-1px);
        }

        /* Botão Ativar */
        .btn-toggle-on {
            background: #ffffff;
            color: #27ae60;
            border-color: rgba(39, 174, 96, 0.35);
        }

        .btn-toggle-on:hover {
            background: #27ae60;
            color: #ffffff;
            box-shadow: 0 3px 8px rgba(39, 174, 96, 0.25);
            transform: translateY(-1px);
        }

        /* Botão Excluir */
        .btn-delete-luxury {
            background: #ffffff;
            color: #c0392b;
            border-color: rgba(192, 57, 43, 0.3);
        }

        .btn-delete-luxury:hover {
            background: #c0392b;
            color: #ffffff;
            box-shadow: 0 3px 8px rgba(192, 57, 43, 0.25);
            transform: translateY(-1px);
        }

        /* Badge de Proteção da Própria Conta */
        .self-admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: rgba(184, 134, 11, 0.08);
            border: 1px dashed rgba(184, 134, 11, 0.35);
            color: var(--gold-dark);
            border-radius: 8px;
            font-size: 11.5px;
            font-weight: 600;
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
        .users-page-footer {
            margin-top: 35px;
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
            .admin-users-wrapper {
                padding: 20px 15px 60px;
            }

            .users-header-card {
                padding: 22px 20px;
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

    <main class="admin-users-wrapper">

        <!-- Barra Superior de Navegação -->
        <div class="top-nav-bar">
            <a href="Dashboard.php" class="btn-back-dashboard" title="Retornar ao painel principal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Voltar ao Dashboard</span>
            </a>

            <div class="users-count-tag" id="summary-counter">
                Total: <strong><?= $totalUsuarios ?></strong> usuário(s)
            </div>
        </div>

        <!-- Banner de Boas-Vindas e Título -->
        <div class="users-header-card">
            <div>
                <div class="header-tag-pill">
                    <span>✦ Controle de Acessos & Segurança ✦</span>
                </div>
                <h1 class="users-main-title">Gerenciamento de Usuários</h1>
                <p class="users-subtitle">
                    Visualize e gerencie todos os clientes, colaboradores da equipe e administradores do <strong>MB Studio</strong>. Altere permissões ou ative/desative contas em tempo real.
                </p>
            </div>
        </div>

        <!-- Cards de Resumo Rápido (KPIs) -->
        <div class="kpi-summary-grid">
            <!-- 1. Total Geral -->
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
                    <div class="kpi-info-val"><?= $totalUsuarios ?></div>
                    <div class="kpi-info-lbl">Total Geral</div>
                </div>
            </div>

            <!-- 2. Clientes -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val"><?= $totalClientes ?></div>
                    <div class="kpi-info-lbl">Clientes</div>
                </div>
            </div>

            <!-- 3. Funcionários -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="6" cy="6" r="3"></circle>
                        <circle cx="6" cy="18" r="3"></circle>
                        <line x1="20" y1="4" x2="8.12" y2="15.88"></line>
                        <line x1="14.47" y1="14.48" x2="20" y2="20"></line>
                        <line x1="8.12" y1="8.12" x2="12" y2="12"></line>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val"><?= $totalFuncionarios ?></div>
                    <div class="kpi-info-lbl">Equipe / Staff</div>
                </div>
            </div>

            <!-- 4. Administradores -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val"><?= $totalAdmins ?></div>
                    <div class="kpi-info-lbl">Administradores</div>
                </div>
            </div>

            <!-- 5. Status Ativo / Inativo -->
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
                    id="searchUserInput" 
                    class="search-input-field" 
                    placeholder="Pesquisar por nome, e-mail ou código ID..."
                    onkeyup="filterUsersTable()"
                >
            </div>

            <div class="filters-group">
                <!-- Filtro por Permissão -->
                <select id="permFilter" class="filter-select" onchange="filterUsersTable()">
                    <option value="ALL">Todas as Permissões</option>
                    <option value="C">👤 Apenas Clientes (C)</option>
                    <option value="F">✂️ Apenas Funcionários (F)</option>
                    <option value="A">👑 Apenas Administradores (A)</option>
                </select>

                <!-- Filtro por Status -->
                <select id="statusFilter" class="filter-select" onchange="filterUsersTable()">
                    <option value="ALL">Todos os Status</option>
                    <option value="1">🟢 Apenas Ativos</option>
                    <option value="0">🔴 Apenas Inativos</option>
                </select>
            </div>
        </div>

        <!-- Card da Tabela de Usuários -->
        <div class="table-card-container">
            <?php if(count($usuarios) > 0): ?>
                <div class="table-responsive-scroll">
                    <table class="luxury-users-table" id="usersTable">
                        <thead>
                            <tr>
                                <th style="width: 70px;">ID</th>
                                <th>Usuário & E-mail</th>
                                <th style="width: 130px;">Status</th>
                                <th style="width: 250px;">Permissão de Acesso</th>
                                <th style="width: 230px;">Ações de Controle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($usuarios as $u): 
                                $isSelf = ($u['User_id'] == $_SESSION['User_id']);
                                $iniciais = getIniciais($u['User_name']);
                            ?>
                                <tr 
                                    class="user-table-row <?= $isSelf ? 'row-self-account' : '' ?>"
                                    data-id="<?= $u['User_id'] ?>"
                                    data-name="<?= htmlspecialchars(mb_strtolower($u['User_name'])) ?>"
                                    data-email="<?= htmlspecialchars(mb_strtolower($u['User_email'])) ?>"
                                    data-perm="<?= $u['User_perm'] ?>"
                                    data-active="<?= $u['User_active'] ?>"
                                >
                                    <!-- ID -->
                                    <td>
                                        <span class="id-badge">#<?= $u['User_id'] ?></span>
                                    </td>

                                    <!-- Nome & E-mail com Avatar -->
                                    <td>
                                        <div class="user-identity-cell">
                                            <div class="user-avatar-circle" title="<?= htmlspecialchars($u['User_name']) ?>">
                                                <?= $iniciais ?>
                                            </div>
                                            <div>
                                                <div class="user-name-text">
                                                    <?= htmlspecialchars($u['User_name']) ?>
                                                    <?php if($isSelf): ?>
                                                        <span style="font-size: 11px; color: var(--gold-dark); background: rgba(184, 134, 11, 0.12); padding: 2px 6px; border-radius: 4px; margin-left: 5px; font-weight: 700;">(Você)</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="user-email-text">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                                        <polyline points="22,6 12,13 2,6"></polyline>
                                                    </svg>
                                                    <?= htmlspecialchars($u['User_email']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td>
                                        <?php if($u['User_active'] == 1): ?>
                                            <span class="status-pill active">
                                                <span class="status-dot"></span> Ativo
                                            </span>
                                        <?php else: ?>
                                            <span class="status-pill inactive">
                                                <span class="status-dot"></span> Inativo
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Permissão (Formulário Original Preservado) -->
                                    <td>
                                        <form action="../../controller/AlterarPermissao.php" method="POST" class="perm-form-container">
                                            <input type="hidden" name="User_id" value="<?= $u['User_id'] ?>">
                                            <select name="User_perm" class="select-luxury-perm" aria-label="Permissão do Usuário">
                                                <option value="C" <?= $u['User_perm'] == 'C' ? 'selected' : '' ?>>👤 Cliente (C)</option>
                                                <option value="F" <?= $u['User_perm'] == 'F' ? 'selected' : '' ?>>✂️ Funcionário (F)</option>
                                                <option value="A" <?= $u['User_perm'] == 'A' ? 'selected' : '' ?>>👑 Admin (A)</option>
                                            </select>
                                            <button type="submit" class="btn-luxury-save" title="Salvar alteração de permissão">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                                Salvar
                                            </button>
                                        </form>
                                    </td>

                                    <!-- Ações (Alternar Status / Excluir) -->
                                    <td>
                                        <div class="actions-cell-wrap">
                                            <?php if(!$isSelf): ?>
                                                <!-- Alternar Status (Ativar / Desativar) -->
                                                <form action="../../controller/AlternarStatusUsuario.php" method="POST" style="margin: 0;">
                                                    <input type="hidden" name="User_id" value="<?= $u['User_id'] ?>">
                                                    <input type="hidden" name="User_active" value="<?= $u['User_active'] == 1 ? 0 : 1 ?>">
                                                    <?php if($u['User_active'] == 1): ?>
                                                        <button type="submit" class="btn-action-luxury btn-toggle-off" title="Desativar este usuário">
                                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <circle cx="12" cy="12" r="10"></circle>
                                                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                                                <line x1="9" y1="9" x2="15" y2="15"></line>
                                                            </svg>
                                                            Desativar
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" class="btn-action-luxury btn-toggle-on" title="Reativar este usuário">
                                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                                            </svg>
                                                            Ativar
                                                        </button>
                                                    <?php endif; ?>
                                                </form>

                                                <!-- Excluir Usuário Fisicamente -->
                                                <form action="../../controller/ExcluirUsuario.php" method="POST" onsubmit="return confirm('Atenção: Tem certeza que deseja excluir fisicamente o usuário \'<?= htmlspecialchars(addslashes($u['User_name'])) ?>\'? Esta ação é irreversível.');" style="margin: 0;">
                                                    <input type="hidden" name="User_id" value="<?= $u['User_id'] ?>">
                                                    <button type="submit" class="btn-action-luxury btn-delete-luxury" title="Excluir usuário permanentemente">
                                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <polyline points="3 6 5 6 21 6"></polyline>
                                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                                        </svg>
                                                        Excluir
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="self-admin-badge" title="Você não pode desativar ou excluir a própria conta logada por segurança.">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                                    </svg>
                                                    Sua Conta (Protegida)
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Estado quando a pesquisa em tempo real não encontra resultados -->
                <div id="noSearchResultsMessage" class="empty-state-wrap" style="display: none;">
                    <div class="empty-icon-circle">🔍</div>
                    <h3 class="empty-title">Nenhum usuário encontrado</h3>
                    <p style="margin: 0; font-size: 14px;">Nenhum registro corresponde aos filtros ou ao termo pesquisado.</p>
                </div>

            <?php else: ?>
                <!-- Estado quando a tabela no banco está completamente vazia -->
                <div class="empty-state-wrap">
                    <div class="empty-icon-circle">👤</div>
                    <h3 class="empty-title">Nenhum usuário encontrado</h3>
                    <p style="margin: 0; font-size: 14px;">Ainda não existem usuários cadastrados no banco de dados.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Rodapé do Painel de Usuários -->
        <div class="users-page-footer">
            <div>
                <strong>MB Studio Admin</strong> — Gestão de Usuários & Acessos
            </div>
            <div>
                © <?= date('Y') ?> Todos os direitos reservados.
            </div>
        </div>

    </main>

    <!-- Script de Filtro Instantâneo em Tempo Real (Vanilla JS) -->
    <script>
        function filterUsersTable() {
            const searchInput = document.getElementById('searchUserInput').value.toLowerCase().trim();
            const permFilter = document.getElementById('permFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('.user-table-row');
            const noResultsMsg = document.getElementById('noSearchResultsMessage');
            
            let visibleCount = 0;

            rows.forEach(row => {
                const id = row.getAttribute('data-id') || '';
                const name = row.getAttribute('data-name') || '';
                const email = row.getAttribute('data-email') || '';
                const perm = row.getAttribute('data-perm') || '';
                const active = row.getAttribute('data-active') || '';

                const matchesSearch = (!searchInput) || 
                                      name.includes(searchInput) || 
                                      email.includes(searchInput) || 
                                      id.includes(searchInput);

                const matchesPerm = (permFilter === 'ALL') || (perm === permFilter);
                const matchesStatus = (statusFilter === 'ALL') || (active === statusFilter);

                if (matchesSearch && matchesPerm && matchesStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Atualizar o contador no topo
            const counterElement = document.getElementById('summary-counter');
            if (counterElement) {
                counterElement.innerHTML = `Exibindo: <strong>${visibleCount}</strong> de <strong>${rows.length}</strong> usuário(s)`;
            }

            // Exibir mensagem de nenhum resultado se visível for zero
            if (noResultsMsg) {
                noResultsMsg.style.display = (visibleCount === 0 && rows.length > 0) ? 'block' : 'none';
            }
        }
    </script>
</body>
</html>
