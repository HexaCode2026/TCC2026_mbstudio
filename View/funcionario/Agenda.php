<?php
date_default_timezone_set('Etc/GMT+3');
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

    <link rel="stylesheet" href="../../assets/css/funcionario/agenda.css">
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
                            <option value="Nao Compareceu">Não Compareceu</option>
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
                                        elseif ($app['Appo_status'] == 'Nao Compareceu') $statusClass = 'status-nao-compareceu';
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
                                            <form action="../../controller/ConfirmarAgendamento.php" method="POST" style="margin: 0;">
                                                <input type="hidden" name="id" value="<?= $app['Appo_id'] ?>">
                                                <button type="submit" class="btn-acao btn-aceitar">
                                                    ✓ Aceitar Agendamento
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- SE CONFIRMADO -->
                                        <?php if ($app['Appo_status'] == 'Confirmado'): ?>
                                            <form action="../../controller/IniciarAtendimento.php" method="POST" style="margin: 0;">
                                                <input type="hidden" name="appo_id" value="<?= $app['Appo_id'] ?>">
                                                <button type="submit" class="btn-acao btn-iniciar">
                                                    ▶ Iniciar Atendimento
                                                </button>
                                            </form>
                                            <form action="../../controller/NaoCompareceu.php" method="POST" style="margin: 0;" onsubmit="return confirm('Deseja realmente marcar que o cliente não compareceu?');">
                                                <input type="hidden" name="appo_id" value="<?= $app['Appo_id'] ?>">
                                                <button type="submit" class="btn-acao btn-falta">
                                                    ∅ Não Compareceu
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- SE EM ATENDIMENTO -->
                                        <?php if ($app['Appo_status'] == 'Em Atendimento'): ?>
                                            <form action="../../controller/FinalizarAgendamento.php" method="POST" style="margin: 0;">
                                                <input type="hidden" name="appo_id" value="<?= $app['Appo_id'] ?>">
                                                <button type="submit" class="btn-acao btn-concluir">
                                                    ★ Finalizar Atendimento
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- CANCELAMENTO (SE NÃO ESTIVER CANCELADO, CONCLUIDO OU NAO COMPARECEU) -->
                                        <?php if (strpos($app['Appo_status'], 'Cancelado') === false && $app['Appo_status'] != 'Concluido' && $app['Appo_status'] != 'Nao Compareceu'): ?>
                                            <form action="../../controller/CancelarAgendamento.php" method="POST" class="form-cancelar-inline" onsubmit="return confirm('Deseja realmente cancelar este agendamento?');">
                                                <input type="hidden" name="appo_id" value="<?= $app['Appo_id'] ?>">
                                                <input type="text" name="cancel_reason" class="input-cancelar" placeholder="Motivo do cancelamento..." required oninput="this.nextElementSibling.disabled = this.value.trim() === '';">
                                                <button type="submit" class="btn-acao btn-cancelar" disabled>
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
