<?php
date_default_timezone_set('America/Sao_Paulo');
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

// Proteção para Funcionário ou Administrador
if (!isset($_SESSION['User_perm']) || ($_SESSION['User_perm'] != 'F' && $_SESSION['User_perm'] != 'A')) {
    header("Location: ../../Index.php");
    exit;
}

// Pega o ID do usuário logado
$userId = $_SESSION['User_id'] ?? 0;

// Busca ou garante o Emp_id do funcionário logado
$stmtEmp = $pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
$stmtEmp->execute([$userId]);
$empId = $stmtEmp->fetchColumn();

if (!$empId && $_SESSION['User_perm'] == 'F') {
    $insertEmp = $pdo->prepare("INSERT INTO employees (User_id) VALUES (?)");
    $insertEmp->execute([$userId]);
    $empId = $pdo->lastInsertId();
}

// Processa formulários de ações (aceitar, cancelar, iniciar, finalizar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['appo_id'])) {
        $appoId = (int)$_POST['appo_id'];
        
        if ($_POST['action'] === 'accept') {
            $stmt = $pdo->prepare("UPDATE appointments SET Appo_status = 'Confirmado' WHERE Appo_id = :id");
            $stmt->bindParam(':id', $appoId, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($_POST['action'] === 'start') {
            $stmt = $pdo->prepare("UPDATE appointments SET Appo_status = 'Em Atendimento' WHERE Appo_id = :id");
            $stmt->bindParam(':id', $appoId, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($_POST['action'] === 'finish') {
            $stmt = $pdo->prepare("UPDATE appointments SET Appo_status = 'Concluido' WHERE Appo_id = :id");
            $stmt->bindParam(':id', $appoId, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($_POST['action'] === 'cancel' && isset($_POST['cancel_reason'])) {
            $reason = trim($_POST['cancel_reason']);
            if (!empty($reason)) {
                $stmt = $pdo->prepare("UPDATE appointments SET Appo_status = 'Cancelado pelo Funcionario', Appo_cancel_by = 'Funcionario', Appo_cancel_reason = :reason WHERE Appo_id = :id");
                $stmt->bindParam(':reason', $reason, PDO::PARAM_STR);
                $stmt->bindParam(':id', $appoId, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
        
        header("Location: Agenda.php");
        exit;
    }
}

// Busca os agendamentos do funcionário vinculado ao usuário logado
// appointments (Cli_id) -> clients (User_id) -> users (User_name)
$query = "SELECT a.*, 
                 users.User_name as client_name, 
                 s.Ser_name, 
                 s.Ser_price, 
                 s.Ser_duration 
          FROM appointments a
          JOIN clients c ON a.Cli_id = c.Cli_id
          JOIN users ON c.User_id = users.User_id
          JOIN services s ON a.Ser_id = s.Ser_id
          JOIN employees e ON a.Emp_id = e.Emp_id
          WHERE e.User_id = :user_id
          ORDER BY a.Appo_date DESC, a.Appo_start ASC";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter datas únicas dos agendamentos para o filtro
$datasCadastradas = [];
if (!empty($appointments)) {
    foreach ($appointments as $app) {
        $data = $app['Appo_date'];
        if (!in_array($data, $datasCadastradas)) {
            $datasCadastradas[] = $data;
        }
    }
    usort($datasCadastradas, function($a, $b) {
        return strtotime($a) - strtotime($b);
    });
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Agenda | MB Studio</title>

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
            --bg-card-hover: #1e1e24;
            --text-light: #ffffff;
            --text-muted: #a0a0a8;
            --font-heading: 'Playfair Display', Georgia, serif;
            --font-cursive: 'Alex Brush', 'Brush Script MT', cursive;
            --font-body: 'Inter', -apple-system, sans-serif;
            --transition-smooth: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-light);
            font-family: var(--font-body);
            min-height: 100vh;
            margin: 0;
            padding-top: 70px;
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

        .agenda-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 40px 25px 80px;
            box-sizing: border-box;
        }

        /* Top Navigation & Header */
        .agenda-top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn-back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--gold-light);
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--gold-border);
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition-smooth);
        }

        .btn-back-link:hover {
            background: rgba(212, 175, 55, 0.15);
            border-color: var(--gold-primary);
            color: #ffffff;
            transform: translateX(-3px);
        }

        .agenda-header-box {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .agenda-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 16px;
            background: rgba(212, 175, 55, 0.1);
            border: 1px solid var(--gold-border);
            border-radius: 30px;
            color: var(--gold-light);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .agenda-title {
            font-family: var(--font-heading);
            font-size: clamp(28px, 4vw, 42px);
            font-weight: 600;
            color: #ffffff;
            margin: 0 0 10px 0;
            line-height: 1.2;
        }

        .agenda-subtitle {
            font-size: 15px;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Barra de Filtros Sofisticada */
        .filter-glass-card {
            background: linear-gradient(145deg, #18181c, #121214);
            border: 1px solid var(--gold-border);
            border-radius: 14px;
            padding: 22px 28px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-item label {
            font-size: 13px;
            font-weight: 600;
            color: var(--gold-light);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .filter-select {
            background: #202026;
            color: #ffffff;
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 8px;
            height: 42px;
            padding: 0 14px;
            font-size: 13.5px;
            font-family: var(--font-body);
            outline: none;
            cursor: pointer;
            transition: var(--transition-smooth);
        }

        .filter-select:focus {
            border-color: var(--gold-primary);
            box-shadow: 0 0 10px rgba(212, 175, 55, 0.3);
        }

        .filter-select option {
            background: #18181c;
            color: #ffffff;
        }

        .total-badge-count {
            background: rgba(212, 175, 55, 0.12);
            color: var(--gold-light);
            border: 1px solid var(--gold-border);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 600;
        }

        /* Container e Tabela de Agendamentos */
        .table-responsive-wrapper {
            background: linear-gradient(145deg, #18181c, #121215);
            border: 1px solid var(--gold-border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }

        .agenda-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13.5px;
        }

        .agenda-table thead {
            background: rgba(212, 175, 55, 0.08);
            border-bottom: 2px solid var(--gold-border);
        }

        .agenda-table th {
            padding: 18px 16px;
            color: var(--gold-light);
            font-family: var(--font-heading);
            font-size: 14.5px;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .agenda-table tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            transition: var(--transition-smooth);
        }

        .agenda-table tbody tr:hover {
            background-color: rgba(212, 175, 55, 0.04);
        }

        .agenda-table td {
            padding: 18px 16px;
            color: #e0e0e0;
            vertical-align: middle;
        }

        .client-name-cell {
            font-weight: 600;
            color: #ffffff;
            font-size: 14px;
        }

        .service-info-cell strong {
            color: var(--gold-light);
            display: block;
            margin-bottom: 2px;
        }

        .service-info-cell small {
            color: var(--gold-primary);
            font-weight: 500;
        }

        .obs-text {
            color: #9e9ea6;
            font-style: italic;
            max-width: 180px;
            word-break: break-word;
        }

        /* Status Badges */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .status-pendente {
            background: rgba(241, 196, 15, 0.12);
            color: #f1c40f;
            border: 1px solid rgba(241, 196, 15, 0.35);
        }

        .status-confirmado {
            background: rgba(52, 152, 219, 0.12);
            color: #3498db;
            border: 1px solid rgba(52, 152, 219, 0.35);
        }

        .status-em-atendimento {
            background: rgba(155, 89, 182, 0.15);
            color: #9b59b6;
            border: 1px solid rgba(155, 89, 182, 0.4);
            animation: pulseSubtle 2s infinite alternate;
        }

        .status-concluido {
            background: rgba(46, 204, 113, 0.12);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.35);
        }

        .status-cancelado {
            background: rgba(231, 76, 60, 0.12);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.35);
        }

        @keyframes pulseSubtle {
            0% { opacity: 0.85; transform: scale(1); }
            100% { opacity: 1; transform: scale(1.03); }
        }

        .cancel-reason-note {
            display: block;
            margin-top: 6px;
            font-size: 11px;
            color: #ff7675;
            background: rgba(231, 76, 60, 0.08);
            padding: 4px 8px;
            border-radius: 4px;
            border-left: 2px solid #e74c3c;
        }

        /* Botões de Ações */
        .action-buttons-wrap {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 170px;
        }

        .btn-acao {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            cursor: pointer;
            border: none;
            transition: var(--transition-smooth);
            text-decoration: none;
            width: 100%;
            box-sizing: border-box;
        }

        .btn-aceitar {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(46, 204, 113, 0.25);
        }

        .btn-aceitar:hover {
            background: #2ecc71;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(46, 204, 113, 0.4);
        }

        .btn-iniciar {
            background: linear-gradient(135deg, #2980b9, #3498db);
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.25);
        }

        .btn-iniciar:hover {
            background: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        }

        .btn-concluir {
            background: linear-gradient(135deg, #d4af37, #b8860b);
            color: #0b0b0c;
            box-shadow: 0 4px 10px rgba(212, 175, 55, 0.25);
        }

        .btn-concluir:hover {
            background: linear-gradient(135deg, #f3e5ab, #d4af37);
            color: #000;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(212, 175, 55, 0.45);
        }

        /* Formulário de Cancelamento Inline */
        .form-cancelar-inline {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 4px;
        }

        .input-cancelar {
            width: 100%;
            background: #222228;
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #ffffff;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            box-sizing: border-box;
            outline: none;
            transition: var(--transition-smooth);
        }

        .input-cancelar:focus {
            border-color: #e74c3c;
            box-shadow: 0 0 8px rgba(231, 76, 60, 0.3);
        }

        .btn-cancelar {
            background: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.4);
        }

        .btn-cancelar:hover:not(:disabled) {
            background: #e74c3c;
            color: #ffffff;
            transform: translateY(-2px);
        }

        .btn-cancelar:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Estado Vazio */
        .empty-agenda-card {
            background: linear-gradient(145deg, #18181c, #121215);
            border: 2px dashed rgba(212, 175, 55, 0.3);
            border-radius: 16px;
            padding: 60px 20px;
            text-align: center;
            color: var(--text-muted);
        }

        .empty-icon {
            font-size: 40px;
            color: var(--gold-primary);
            margin-bottom: 15px;
        }

        .empty-agenda-card h3 {
            font-family: var(--font-heading);
            font-size: 22px;
            color: #ffffff;
            margin-bottom: 8px;
        }

        /* Responsividade */
        @media (max-width: 1024px) {
            .table-responsive-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .agenda-table {
                min-width: 950px;
            }
        }

        @media (max-width: 768px) {
            .agenda-container {
                padding: 25px 15px 60px;
            }

            .filter-glass-card {
                flex-direction: column;
                align-items: stretch;
                padding: 18px;
            }

            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .filter-select {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <!-- Cabeçalho Global MB Studio -->
    <?php include '../components/Header.php'; ?>
    <?php include '../components/LoginModal.php'; ?>

    <main class="agenda-container">

        <!-- Top Navigation -->
        <div class="agenda-top-nav">
            <a href="Home.php" class="btn-back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Voltar ao Início
            </a>

            <a href="Disponibilidade.php" class="btn-back-link" style="border-color: var(--gold-primary); color: var(--gold-light);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Gerenciar Disponibilidade
            </a>
        </div>

        <!-- Header da Página -->
        <div class="agenda-header-box">
            <div class="agenda-badge">
                <span>✦ Painel de Atendimento ✦</span>
            </div>
            <h1 class="agenda-title">
                Minha <span class="cursiva-gold">Agenda</span>
            </h1>
            <p class="agenda-subtitle">
                Acompanhe em tempo real os agendamentos realizados, aceite solicitações e gerencie o fluxo de atendimento dos seus clientes.
            </p>
        </div>

        <?php if (!empty($appointments)): ?>
            <!-- Barra de Filtros -->
            <div class="filter-glass-card">
                <div class="filter-group">
                    <div class="filter-item">
                        <label for="filtroData">Data:</label>
                        <select id="filtroData" class="filter-select" onchange="filtrarTabelaPorData()">
                            <option value="todas">Todas as datas</option>
                            <?php foreach ($datasCadastradas as $data): ?>
                                <option value="<?= htmlspecialchars($data) ?>"><?= date('d/m/Y', strtotime($data)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label for="filtroStatus">Status:</label>
                        <select id="filtroStatus" class="filter-select" onchange="filtrarTabelaPorData()">
                            <option value="todos">Todos os status</option>
                            <option value="Pendente">Pendente</option>
                            <option value="Confirmado">Confirmado</option>
                            <option value="Em Atendimento">Em Atendimento</option>
                            <option value="Concluido">Concluído</option>
                            <option value="Cancelado">Cancelado</option>
                        </select>
                    </div>
                </div>

                <div class="total-badge-count">
                    <span>Total: <?= count($appointments) ?> agendamento(s)</span>
                </div>
            </div>

            <!-- Tabela de Agendamentos -->
            <div class="table-responsive-wrapper">
                <table class="agenda-table" id="tabelaAgenda">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Início</th>
                            <th>Fim</th>
                            <th>Cliente</th>
                            <th>Serviço</th>
                            <th>Observação</th>
                            <th>Status Atual</th>
                            <th style="text-align: center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $app): ?>
                            <tr data-data="<?= htmlspecialchars($app['Appo_date']) ?>" data-status="<?= htmlspecialchars($app['Appo_status']) ?>">
                                <td>
                                    <strong><?= date('d/m/Y', strtotime($app['Appo_date'])) ?></strong>
                                </td>
                                <td>
                                    <span style="color: var(--gold-light); font-weight: 600;"><?= htmlspecialchars(substr($app['Appo_start'], 0, 5)) ?></span>
                                </td>
                                <td>
                                    <span style="color: var(--text-muted);"><?= htmlspecialchars(substr($app['Appo_end'], 0, 5)) ?></span>
                                </td>
                                <td>
                                    <span class="client-name-cell"><?= htmlspecialchars($app['client_name']) ?></span>
                                </td>
                                <td class="service-info-cell">
                                    <strong><?= htmlspecialchars($app['Ser_name']) ?></strong>
                                    <small>R$ <?= number_format($app['Ser_price'], 2, ',', '.') ?> (<?= $app['Ser_duration'] ?> min)</small>
                                </td>
                                <td>
                                    <span class="obs-text">
                                        <?= htmlspecialchars(!empty($app['Appo_observation']) ? $app['Appo_observation'] : 'Nenhuma') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                        $statusClass = 'status-pendente';
                                        if ($app['Appo_status'] == 'Confirmado') $statusClass = 'status-confirmado';
                                        elseif ($app['Appo_status'] == 'Em Atendimento') $statusClass = 'status-em-atendimento';
                                        elseif ($app['Appo_status'] == 'Concluido') $statusClass = 'status-concluido';
                                        elseif (strpos($app['Appo_status'], 'Cancelado') !== false) $statusClass = 'status-cancelado';
                                    ?>
                                    <span class="status-pill <?= $statusClass ?>">
                                        ● <?= htmlspecialchars($app['Appo_status']) ?>
                                    </span>
                                    <?php if (!empty($app['Appo_cancel_reason'])): ?>
                                        <span class="cancel-reason-note">
                                            <strong>Motivo:</strong> <?= htmlspecialchars($app['Appo_cancel_reason']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons-wrap">
                                        <!-- SE PENDENTE -->
                                        <?php if ($app['Appo_status'] == 'Pendente'): ?>
                                            <form action="" method="POST" style="margin: 0;">
                                                <input type="hidden" name="appo_id" value="<?= $app['Appo_id'] ?>">
                                                <button type="submit" name="action" value="accept" class="btn-acao btn-aceitar">
                                                    ✓ Aceitar Agendamento
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- SE CONFIRMADO -->
                                        <?php if ($app['Appo_status'] == 'Confirmado'): ?>
                                            <form action="" method="POST" style="margin: 0;">
                                                <input type="hidden" name="appo_id" value="<?= $app['Appo_id'] ?>">
                                                <button type="submit" name="action" value="start" class="btn-acao btn-iniciar">
                                                    ▶ Iniciar Atendimento
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- SE EM ATENDIMENTO -->
                                        <?php if ($app['Appo_status'] == 'Em Atendimento'): ?>
                                            <form action="" method="POST" style="margin: 0;">
                                                <input type="hidden" name="appo_id" value="<?= $app['Appo_id'] ?>">
                                                <button type="submit" name="action" value="finish" class="btn-acao btn-concluir">
                                                    ★ Finalizar Atendimento
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- CANCELAMENTO (SE NÃO ESTIVER CANCELADO OU CONCLUIDO) -->
                                        <?php if (strpos($app['Appo_status'], 'Cancelado') === false && $app['Appo_status'] != 'Concluido'): ?>
                                            <form action="" method="POST" class="form-cancelar-inline" onsubmit="return confirm('Deseja realmente cancelar este agendamento?');">
                                                <input type="hidden" name="appo_id" value="<?= $app['Appo_id'] ?>">
                                                <input type="text" name="cancel_reason" class="input-cancelar" placeholder="Motivo do cancelamento..." required oninput="this.nextElementSibling.disabled = this.value.trim() === '';">
                                                <button type="submit" name="action" value="cancel" class="btn-acao btn-cancelar" disabled>
                                                    ✕ Cancelar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <div class="empty-agenda-card">
                <div class="empty-icon">📅</div>
                <h3>Nenhum agendamento encontrado</h3>
                <p>Assim que os clientes realizarem agendamentos com você, eles aparecerão detalhados aqui.</p>
                <div style="margin-top: 25px;">
                    <a href="Disponibilidade.php" class="btn-back-link" style="border-color: var(--gold-primary); color: var(--gold-light);">
                        Cadastrar Horários Disponíveis
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <script>
        function filtrarTabelaPorData() {
            const filtroData = document.getElementById('filtroData');
            const filtroStatus = document.getElementById('filtroStatus');
            if (!filtroData || !filtroStatus) return;

            const dataSelecionada = filtroData.value;
            const statusSelecionado = filtroStatus.value;

            const linhas = document.querySelectorAll('#tabelaAgenda tbody tr');

            linhas.forEach(linha => {
                const dataLinha = linha.getAttribute('data-data');
                const statusLinha = linha.getAttribute('data-status');

                let matchData = (dataSelecionada === 'todas' || dataLinha === dataSelecionada);
                let matchStatus = (statusSelecionado === 'todos');

                if (statusSelecionado === 'Cancelado') {
                    matchStatus = statusLinha.includes('Cancelado');
                } else if (statusSelecionado !== 'todos') {
                    matchStatus = (statusLinha === statusSelecionado);
                }

                if (matchData && matchStatus) {
                    linha.style.display = '';
                } else {
                    linha.style.display = 'none';
                }
            });
        }
    </script>
</body>

</html>
