<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'C') {
    header("Location: ../../Index.php");
    exit;
}

$userId = $_SESSION['User_id'];

// Obter Cli_id
$stmtCli = $pdo->prepare("SELECT Cli_id FROM clients WHERE User_id = ?");
$stmtCli->execute([$userId]);
$cli_id = $stmtCli->fetchColumn();

$agendamentos = [];
if ($cli_id) {
    // Buscar agendamentos do cliente com detalhes do serviço e especialista
    $sql = "SELECT a.Appo_id, a.Appo_date, a.Appo_start, a.Appo_status, 
                   s.Ser_name, s.Ser_price, s.Ser_duration,
                   u.User_name as Funcionario
            FROM appointments a
            JOIN services s ON a.Ser_id = s.Ser_id
            JOIN employees e ON a.Emp_id = e.Emp_id
            JOIN users u ON e.User_id = u.User_id
            WHERE a.Cli_id = ?
            ORDER BY a.Appo_date DESC, a.Appo_start DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cli_id]);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Contadores de resumo do cliente
$totalAgendamentos = count($agendamentos);
$totalAtivos = 0;
$totalConcluidos = 0;

foreach ($agendamentos as $item) {
    $st = $item['Appo_status'] ?? '';
    if ($st === 'Pendente' || $st === 'Confirmado' || $st === 'Em Atendimento') {
        $totalAtivos++;
    } elseif ($st === 'Concluido' || $st === 'Concluído') {
        $totalConcluidos++;
    }
}

$globalCssVer = @filemtime(__DIR__ . '/../../assets/css/global.css') ?: '3.2';
$appoCssVer   = @filemtime(__DIR__ . '/../../assets/css/cliente/meusagendamentos.css') ?: '3.2';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos | MB Studio</title>
    <link rel="stylesheet" href="../../assets/css/global.css?v=<?= $globalCssVer ?>">
    <link rel="stylesheet" href="../../assets/css/cliente/meusagendamentos.css?v=<?= $appoCssVer ?>">
</head>
<body class="cliente-page">

    <!-- Cabeçalho Oficial do Projeto -->
    <?php include '../components/Header.php'; ?>

    <main class="appointments-page-wrapper">
        <!-- Decorações Sutis de Fundo -->
        <div class="appo-decor appo-decor-left" aria-hidden="true"></div>
        <div class="appo-decor appo-decor-right" aria-hidden="true"></div>

        <div class="appointments-container">

            <!-- Cabeçalho Editorial da Página -->
            <header class="appointments-hero">
                <div class="appointments-header-box">
                    <span class="appointments-eyebrow">✦ Minha Conta • MB Studio ✦</span>
                    <h1 class="appointments-title">
                        Meus <span class="cursiva-gold">Agendamentos</span>
                    </h1>
                    <div class="appointments-gold-line"></div>
                    <p class="appointments-desc">
                        Acompanhe em tempo real o status das suas solicitações de atendimento e gerencie seus horários com nossos especialistas.
                    </p>
                </div>

                <div class="appointments-hero-actions">
                    <a href="Servicos.php" class="btn-new-appointment">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Novo Agendamento</span>
                    </a>
                </div>
            </header>

            <!-- Alertas de Feedback -->
            <?php if (isset($_GET['sucesso'])): ?>
                <div class="alert-feedback success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>
                        <?php
                        if ($_GET['sucesso'] == 'agendamento_cancelado') {
                            echo "Seu agendamento foi cancelado com sucesso.";
                        } else {
                            echo "Agendamento solicitado com sucesso! Aguarde a confirmação do profissional.";
                        }
                        ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['erro'])): ?>
                <div class="alert-feedback error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span>Ocorreu um erro ao processar sua solicitação. Tente novamente.</span>
                </div>
            <?php endif; ?>

            <?php if ($totalAgendamentos > 0): ?>
                <!-- Resumo Rápido de Agendamentos do Cliente -->
                <section class="appointments-kpi-grid" aria-label="Resumo dos seus agendamentos">
                    <div class="appo-kpi-card">
                        <div class="appo-kpi-icon gold">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <div class="appo-kpi-info">
                            <span class="appo-kpi-value"><?= $totalAgendamentos ?></span>
                            <span class="appo-kpi-label">Total de Solicitações</span>
                        </div>
                    </div>

                    <div class="appo-kpi-card">
                        <div class="appo-kpi-icon emerald">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div class="appo-kpi-info">
                            <span class="appo-kpi-value"><?= $totalAtivos ?></span>
                            <span class="appo-kpi-label">Horários Ativos</span>
                        </div>
                    </div>

                    <div class="appo-kpi-card">
                        <div class="appo-kpi-icon blue">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="appo-kpi-info">
                            <span class="appo-kpi-value"><?= $totalConcluidos ?></span>
                            <span class="appo-kpi-label">Atendimentos Concluídos</span>
                        </div>
                    </div>
                </section>

                <!-- Card Principal da Tabela -->
                <section class="appointments-card-shell">
                    <div class="appointments-toolbar">
                        <div class="appointments-toolbar-title">
                            <h2>Histórico de Solicitações</h2>
                            <span class="appointments-count-pill"><?= $totalAgendamentos ?> <?= $totalAgendamentos === 1 ? 'registro' : 'registros' ?></span>
                        </div>

                        <div class="appointments-filter-tabs" role="tablist" aria-label="Filtrar por status">
                            <button type="button" class="appo-filter-btn active" onclick="this.parentElement.querySelectorAll('.appo-filter-btn').forEach(b=>b.classList.remove('active'));this.classList.add('active');document.querySelectorAll('#clientAppoTable tbody tr').forEach(r=>{r.style.display='';});">
                                Todos
                            </button>
                            <button type="button" class="appo-filter-btn" onclick="this.parentElement.querySelectorAll('.appo-filter-btn').forEach(b=>b.classList.remove('active'));this.classList.add('active');document.querySelectorAll('#clientAppoTable tbody tr').forEach(r=>{r.style.display=(r.dataset.group==='ativo')?'':'none';});">
                                Ativos
                            </button>
                            <button type="button" class="appo-filter-btn" onclick="this.parentElement.querySelectorAll('.appo-filter-btn').forEach(b=>b.classList.remove('active'));this.classList.add('active');document.querySelectorAll('#clientAppoTable tbody tr').forEach(r=>{r.style.display=(r.dataset.group==='concluido')?'':'none';});">
                                Concluídos
                            </button>
                            <button type="button" class="appo-filter-btn" onclick="this.parentElement.querySelectorAll('.appo-filter-btn').forEach(b=>b.classList.remove('active'));this.classList.add('active');document.querySelectorAll('#clientAppoTable tbody tr').forEach(r=>{r.style.display=(r.dataset.group==='cancelado')?'':'none';});">
                                Cancelados
                            </button>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="appointments-table table-mini" id="clientAppoTable">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Horário</th>
                                    <th>Procedimento</th>
                                    <th>Especialista</th>
                                    <th>Status</th>
                                    <th style="text-align: center;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agendamentos as $a): 
                                    $rawStatus = $a['Appo_status'] ?? 'Pendente';
                                    $firstWord = explode(' ', $rawStatus)[0];
                                    $statusClass = 'status-' . $firstWord;

                                    if ($rawStatus === 'Pendente' || $rawStatus === 'Confirmado' || $rawStatus === 'Em Atendimento') {
                                        $rowGroup = 'ativo';
                                    } elseif ($rawStatus === 'Concluido' || $rawStatus === 'Concluído') {
                                        $rowGroup = 'concluido';
                                    } else {
                                        $rowGroup = 'cancelado';
                                    }

                                    $funcNome = trim($a['Funcionario'] ?? 'Especialista');
                                    $funcInitial = mb_strtoupper(mb_substr($funcNome, 0, 1, 'UTF-8'), 'UTF-8') ?: 'E';
                                ?>
                                <tr data-group="<?= $rowGroup ?>">
                                    <td data-label="Data">
                                        <div class="cell-date-group">
                                            <span class="date-icon-box" aria-hidden="true">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                                </svg>
                                            </span>
                                            <strong class="date-text-strong"><?= date('d/m/Y', strtotime($a['Appo_date'])) ?></strong>
                                        </div>
                                    </td>

                                    <td data-label="Horário">
                                        <span class="time-pill-badge">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                            <?= date('H:i', strtotime($a['Appo_start'])) ?>
                                        </span>
                                    </td>

                                    <td data-label="Procedimento">
                                        <div class="service-cell-info">
                                            <span class="service-cell-name"><?= htmlspecialchars($a['Ser_name']) ?></span>
                                            <?php if (isset($a['Ser_price']) || !empty($a['Ser_duration'])): ?>
                                                <span class="service-cell-meta">
                                                    <?php if (isset($a['Ser_price'])): ?>
                                                        <span class="service-cell-price">R$ <?= number_format((float)$a['Ser_price'], 2, ',', '.') ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($a['Ser_duration'])): ?>
                                                        <span>• <?= (int)$a['Ser_duration'] ?> min</span>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td data-label="Especialista">
                                        <div class="specialist-cell">
                                            <span class="specialist-avatar" aria-hidden="true"><?= htmlspecialchars($funcInitial) ?></span>
                                            <span class="specialist-name"><?= htmlspecialchars($funcNome) ?></span>
                                        </div>
                                    </td>

                                    <td data-label="Status">
                                        <span class="status-badge <?= htmlspecialchars($statusClass) ?>">
                                            <?= htmlspecialchars($rawStatus) ?>
                                        </span>
                                    </td>

                                    <td data-label="Ações" style="text-align: center;">
                                        <div class="action-cell-wrapper">
                                            <?php if ($rawStatus == 'Pendente' || $rawStatus == 'Confirmado'): ?>
                                                <form action="../../controller/CancelarAgendamento.php" method="POST" style="margin: 0; display: inline-block;" onsubmit="return confirm('Tem certeza que deseja cancelar este agendamento?');">
                                                    <input type="hidden" name="appo_id" value="<?= $a['Appo_id'] ?>">
                                                    <input type="hidden" name="cancel_reason" value="Cancelado pelo Cliente via painel">
                                                    <button type="submit" class="btn-cancel-appo" title="Cancelar este agendamento">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                                            <circle cx="12" cy="12" r="10"></circle>
                                                            <line x1="15" y1="9" x2="9" y2="15"></line>
                                                            <line x1="9" y1="9" x2="15" y2="15"></line>
                                                        </svg>
                                                        <span>Cancelar</span>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="action-muted-label">Encerrado</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php else: ?>
                <div class="empty-appo-card">
                    <div class="empty-appo-icon-ring" aria-hidden="true">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <h3 class="empty-appo-title">Nenhum agendamento encontrado</h3>
                    <p class="empty-appo-desc">Você ainda não possui horários marcados no estúdio. Escolha um serviço e agende seu horário com nossos especialistas.</p>
                    <a href="Servicos.php" class="btn-gold">
                        Agendar Primeiro Horário
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- Modal Global de Autenticação -->
    <?php include '../components/LoginModal.php'; ?>

</body>
</html>
