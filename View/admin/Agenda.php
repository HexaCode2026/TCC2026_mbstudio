<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

// Proteção Admin
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'A') {
    header("Location: ../../Index.php");
    exit;
}

// Busca todos os agendamentos
$query = "SELECT a.*, 
                 c_user.User_name as client_name, 
                 e_user.User_name as emp_name,
                 s.Ser_name, 
                 s.Ser_price, 
                 s.Ser_duration 
          FROM appointments a
          JOIN clients c ON a.Cli_id = c.Cli_id
          JOIN users c_user ON c.User_id = c_user.User_id
          JOIN employees e ON a.Emp_id = e.Emp_id
          JOIN users e_user ON e.User_id = e_user.User_id
          JOIN services s ON a.Ser_id = s.Ser_id
          ORDER BY a.Appo_date DESC, a.Appo_start ASC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamentos | MB Studio</title>

    <?php
    $globalCssVer = @filemtime(__DIR__ . '/../../assets/css/global.css') ?: '3.0';
    $adminCssVer  = @filemtime(__DIR__ . '/../../assets/css/admin.css') ?: '3.0';
    $agendaCssVer = @filemtime(__DIR__ . '/../../assets/css/funcionario/agenda.css') ?: '3.0';
    ?>
    <link rel="stylesheet" href="../../assets/css/global.css?v=<?= $globalCssVer ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?= $adminCssVer ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/funcionario/agenda.css?v=<?= $agendaCssVer ?>">
</head>

<body>
    <?php include '../components/Header.php'; ?>

    <main class="agenda-container">
        <div class="agenda-top-nav">
            <a href="Dashboard.php" class="btn-back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Voltar ao Painel
            </a>
        </div>

        <div class="admin-welcome-card">
            <div>
                <div class="welcome-badge">
                    <span>✦ Controle Geral de Agenda ✦</span>
                </div>
                <h1 class="welcome-title">Agenda Global de Atendimentos</h1>
                <p class="welcome-subtitle">
                    Acompanhe globalmente o fluxo de todos os profissionais e finalize recebimentos que estão em atendimento no <strong>MB Studio</strong>.
                </p>
            </div>
        </div>

        <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 'atendimento_concluido'): ?>
            <div class="alert alert-success" style="margin-bottom: 25px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span>Atendimento finalizado e pagamento registrado com sucesso.</span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['erro'])): ?>
            <?php 
                $msgErro = "Ocorreu um erro ao tentar finalizar.";
                if ($_GET['erro'] == 'dados_incompletos') $msgErro = "Dados incompletos fornecidos.";
                elseif ($_GET['erro'] == 'pagamento_invalido') $msgErro = "Método de pagamento inválido selecionado.";
                elseif ($_GET['erro'] == 'falha_permissao_ou_status') $msgErro = "Falha ao finalizar: verifique se o atendimento realmente se encontra 'Em Atendimento'.";
                elseif ($_GET['erro'] == 'funcionario_nao_encontrado') $msgErro = "Funcionário não localizado no sistema.";
            ?>
            <div class="alert alert-error" style="margin-bottom: 25px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span><?= htmlspecialchars($msgErro, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($appointments)): ?>
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

            <div class="table-responsive-wrapper">
                <table class="agenda-table" id="tabelaAgenda">
                    <thead>
                        <tr>
                            <th>Data / Hora</th>
                            <th>Cliente</th>
                            <th>Profissional</th>
                            <th>Serviço</th>
                            <th>Status Atual</th>
                            <th style="text-align: center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $app): ?>
                            <tr data-data="<?= htmlspecialchars($app['Appo_date']) ?>" data-status="<?= htmlspecialchars($app['Appo_status']) ?>">
                                <td>
                                    <strong><?= date('d/m/Y', strtotime($app['Appo_date'])) ?></strong><br>
                                    <span class="time-range-badge"><?= htmlspecialchars(substr($app['Appo_start'], 0, 5)) ?> às <?= htmlspecialchars(substr($app['Appo_end'], 0, 5)) ?></span>
                                </td>
                                <td><span class="client-name-cell"><?= htmlspecialchars($app['client_name']) ?></span></td>
                                <td><span style="color: var(--text-secondary); font-weight: 500;"><?= htmlspecialchars($app['emp_name']) ?></span></td>
                                <td class="service-info-cell">
                                    <strong><?= htmlspecialchars($app['Ser_name']) ?></strong>
                                    <small>R$ <?= number_format($app['Ser_price'], 2, ',', '.') ?></small>
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
                                    <?php if ($app['Appo_payment_method']): ?>
                                        <div style="margin-top: 4px; font-size: 11.5px; font-weight: 600; color: var(--gold-darker);">Pago via: <?= htmlspecialchars($app['Appo_payment_method']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons-wrap">
                                        <!-- ADMINISTRADOR SÓ DEVE POSSUIR BOTÃO DE FINALIZAR -->
                                        <?php if ($app['Appo_status'] == 'Em Atendimento'): ?>
                                            <button type="button" class="btn-acao btn-concluir" 
                                                    data-appo-id="<?= $app['Appo_id'] ?>" 
                                                    data-cliente="<?= htmlspecialchars($app['client_name'], ENT_QUOTES, 'UTF-8') ?>" 
                                                    data-profissional="<?= htmlspecialchars($app['emp_name'], ENT_QUOTES, 'UTF-8') ?>" 
                                                    data-servico="<?= htmlspecialchars($app['Ser_name'], ENT_QUOTES, 'UTF-8') ?>" 
                                                    data-valor="<?= $app['Ser_price'] ?>" 
                                                    onclick="abrirModalFinalizacao(this)">
                                                ★ Finalizar
                                            </button>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 12px; font-style: italic; display: block; text-align: center;">Nenhuma ação</span>
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
                <div class="empty-icon empty-icon-svg" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <h3>Nenhum agendamento encontrado</h3>
                <p>O salão ainda não possui agendamentos registrados. Certifique-se de que há serviços ativos e profissionais vinculados na equipe.</p>
                <div style="margin-top: 22px; display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
                    <a href="Funcionarios.php" class="btn-gold">Ver Equipe</a>
                    <a href="Servicos.php" class="btn-outline">Ver Serviços</a>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="admin-panel-footer">
        <p>&copy; <?= date('Y') ?> MB Studio. Painel Administrativo de Gestão.</p>
    </footer>

    <!-- Modal Finalização (idêntico ao do funcionário) -->
    <div id="modalFinalizacao" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Finalizar Atendimento (Admin)</h2>
                <button type="button" class="btn-close-modal" onclick="fecharModalFinalizacao()">×</button>
            </div>
            <div class="modal-body">
                <div class="info-resumo">
                    <p><strong>Cliente:</strong> <span id="modalClienteNome"></span></p>
                    <p><strong>Profissional:</strong> <span id="modalProfissionalNome"></span></p>
                    <p><strong>Serviço:</strong> <span id="modalServicoNome"></span></p>
                    <p><strong>Valor:</strong> R$ <span id="modalServicoValor"></span></p>
                </div>
                
                <form action="../../controller/FinalizarAgendamento.php" method="POST" id="formFinalizacao">
                    <?= Session::csrfField() ?>
                    <input type="hidden" name="appo_id" id="modalAppoId" value="">
                    
                    <div class="payment-section">
                        <p class="payment-title">Forma de Pagamento</p>
                        <div class="payment-options">
                            <label class="payment-card">
                                <input type="radio" name="payment_method" value="Pix" required>
                                <span class="payment-label">Pix</span>
                            </label>
                            <label class="payment-card">
                                <input type="radio" name="payment_method" value="Dinheiro" required>
                                <span class="payment-label">Dinheiro</span>
                            </label>
                            <label class="payment-card">
                                <input type="radio" name="payment_method" value="Cartão" required>
                                <span class="payment-label">Cartão</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn-acao btn-cancelar-modal" onclick="fecharModalFinalizacao()">Cancelar</button>
                        <button type="submit" class="btn-acao btn-aceitar">Confirmar Finalização</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function abrirModalFinalizacao(btn) {
            document.getElementById('modalAppoId').value = btn.dataset.appoId;
            document.getElementById('modalClienteNome').textContent = btn.dataset.cliente;
            document.getElementById('modalProfissionalNome').textContent = btn.dataset.profissional;
            document.getElementById('modalServicoNome').textContent = btn.dataset.servico;
            document.getElementById('modalServicoValor').textContent = parseFloat(btn.dataset.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            const radios = document.querySelectorAll('input[name="payment_method"]');
            radios.forEach(r => r.checked = false);

            document.getElementById('modalFinalizacao').style.display = 'flex';
        }

        function fecharModalFinalizacao() {
            document.getElementById('modalFinalizacao').style.display = 'none';
        }

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
