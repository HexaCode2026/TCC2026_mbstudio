<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";
require_once "../../controller/RelatorioController.php";

Session::iniciar();

// Proteção Admin
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'A') {
    header("Location: ../../Index.php");
    exit;
}

$controller = new RelatorioController($pdo);
$relatorioDados = $controller->processarFiltro();

$dataInicial = $relatorioDados['dataInicial'];
$dataFinal = $relatorioDados['dataFinal'];
$empId = $relatorioDados['empId'];
$dados = $relatorioDados['dados'];
$totaisStatus = $relatorioDados['totaisStatus'];
$totalRegistros = $relatorioDados['totalRegistros'];
$faturamentoTotal = $relatorioDados['faturamentoTotal'];
$funcionarios = $relatorioDados['funcionarios'];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios | MB Studio</title>

    <?php
    $globalCssVer = @filemtime(__DIR__ . '/../../assets/css/global.css') ?: '3.0';
    $adminCssVer  = @filemtime(__DIR__ . '/../../assets/css/admin.css') ?: '3.0';
    $relCssVer    = @filemtime(__DIR__ . '/../../assets/css/admin/relatorios.css') ?: '3.0';

    $formatarStatus = static function (string $st): string {
        $map = [
            'Concluido' => 'Concluído',
            'Nao Compareceu' => 'Não Compareceu',
            'Cancelado pelo Funcionario' => 'Cancelado pelo Profissional',
            'Cancelado pelo Cliente' => 'Cancelado pelo Cliente',
            'Cancelado pelo Administrador' => 'Cancelado pelo Administrador',
        ];
        return $map[$st] ?? $st;
    };
    ?>
    <link rel="stylesheet" href="../../assets/css/global.css?v=<?= $globalCssVer ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?= $adminCssVer ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin/relatorios.css?v=<?= $relCssVer ?>">
</head>

<body>
    <!-- Cabeçalho Global MB Studio -->
    <?php include '../components/Header.php'; ?>

    <main class="admin-dashboard-wrapper">
        <div class="admin-welcome-card">
            <div>
                <div class="welcome-badge">
                    <span>✦ Inteligência & Desempenho ✦</span>
                </div>
                <h1 class="welcome-title">Relatório Gerencial</h1>
                <p class="welcome-subtitle">
                    Filtre por período e profissional para analisar atendimentos, taxas de conclusão e o desempenho financeiro do <strong>MB Studio</strong>.
                </p>
            </div>
            <div class="welcome-actions">
                <button type="button" class="btn-outline" onclick="window.print()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    <span>Imprimir / PDF</span>
                </button>
            </div>
        </div>

        <!-- Formulário de Filtro de Período -->
        <div class="report-filter-card">
            <div class="report-presets-bar">
                <div class="report-presets-group">
                    <span class="report-presets-label">Período rápido:</span>
                    <button type="button" class="btn-period-chip" onclick="aplicarPeriodoRelatorio('hoje')">Hoje</button>
                    <button type="button" class="btn-period-chip" onclick="aplicarPeriodoRelatorio('7dias')">Últimos 7 dias</button>
                    <button type="button" class="btn-period-chip" onclick="aplicarPeriodoRelatorio('mes')">Este mês</button>
                    <button type="button" class="btn-period-chip" onclick="aplicarPeriodoRelatorio('mes_anterior')">Mês passado</button>
                </div>
            </div>
            <form method="GET" class="report-filter-form" id="reportFilterForm">
                <div class="report-form-group">
                    <label for="data_inicial">Data Inicial</label>
                    <input type="date" name="data_inicial" id="data_inicial" value="<?= htmlspecialchars($dataInicial) ?>" required>
                </div>
                <div class="report-form-group">
                    <label for="data_final">Data Final</label>
                    <input type="date" name="data_final" id="data_final" value="<?= htmlspecialchars($dataFinal) ?>" required>
                </div>
                <div class="report-form-group">
                    <label for="emp_id">Profissional</label>
                    <select name="emp_id" id="emp_id">
                        <option value="">Todos os Profissionais</option>
                        <?php foreach ($funcionarios as $func): ?>
                            <option value="<?= $func['Emp_id'] ?>" <?= $empId == $func['Emp_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($func['User_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-report-filter">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    <span>Aplicar Filtro</span>
                </button>
            </form>
        </div>

        <!-- KPIs Executivos Principais -->
        <div class="report-kpi-primary-grid">
            <div class="metric-card-white">
                <div class="metric-card-header">
                    <div class="metric-icon-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <span class="metric-tag-label">Volume</span>
                </div>
                <div>
                    <div class="metric-title">Total de Agendamentos</div>
                    <div class="metric-number"><?= $totalRegistros ?></div>
                    <p class="metric-description">Registros encontrados no período selecionado.</p>
                </div>
            </div>
            <div class="metric-card-white">
                <div class="metric-card-header">
                    <div class="metric-icon-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <span class="metric-tag-label">Receita</span>
                </div>
                <div>
                    <div class="metric-title">Faturamento (Concluídos)</div>
                    <div class="metric-number">R$ <?= number_format($faturamentoTotal, 2, ',', '.') ?></div>
                    <p class="metric-description">Receita bruta gerada pelos atendimentos concluídos.</p>
                </div>
            </div>
        </div>

        <!-- Distribuição Compacta por Status -->
        <?php if (!empty($totaisStatus)): ?>
            <div class="report-status-grid" aria-label="Totais por status">
                <?php foreach ($totaisStatus as $status): ?>
                    <div class="report-status-chip-card">
                        <span class="report-status-chip-label"><?= htmlspecialchars($formatarStatus((string)$status['Status'])) ?></span>
                        <span class="report-status-chip-val"><?= (int)$status['Total'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Tabela de Dados Detalhados -->
        <div class="report-table-card">
            <div class="table-container" style="margin-bottom: 0;">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data e Hora</th>
                            <th>Cliente</th>
                            <th>Profissional</th>
                            <th>Serviço</th>
                            <th>Valor (R$)</th>
                            <th>Forma Pgto</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dados)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 36px 20px; color: var(--text-muted, #71717a);">
                                    Nenhum agendamento encontrado para o período selecionado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($dados as $linha):
                                $badgeClass = 'default';
                                if (stripos($linha['Status'], 'Pendente') !== false)
                                    $badgeClass = 'pendente';
                                if (stripos($linha['Status'], 'Concluido') !== false)
                                    $badgeClass = 'concluido';
                                if (stripos($linha['Status'], 'Cancelado') !== false)
                                    $badgeClass = 'cancelado';
                                if (stripos($linha['Status'], 'Confirmado') !== false)
                                    $badgeClass = 'confirmado';
                                ?>
                                <tr>
                                    <td><strong>#<?= $linha['ID_Agendamento'] ?></strong></td>
                                    <td>
                                        <strong><?= date('d/m/Y', strtotime($linha['Data_Agendamento'])) ?></strong><br>
                                        <span style="font-size: 0.85rem; color: var(--text-muted, #71717a);"><?= date('H:i', strtotime($linha['Hora_Inicio'])) ?> - <?= date('H:i', strtotime($linha['Hora_Fim'])) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($linha['Nome_Cliente']) ?></td>
                                    <td><?= htmlspecialchars($linha['Nome_Profissional']) ?></td>
                                    <td><?= htmlspecialchars($linha['Servico_Realizado']) ?></td>
                                    <td><strong>R$ <?= number_format($linha['Valor_Servico'], 2, ',', '.') ?></strong></td>
                                    <td><?= htmlspecialchars($linha['Forma_Pagamento'] ?: '—') ?></td>
                                    <td><span class="status-pill <?= $badgeClass ?>"><?= htmlspecialchars($formatarStatus((string)$linha['Status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="admin-panel-footer">
        <p>&copy; <?= date('Y') ?> MB Studio. Painel Administrativo de Gestão.</p>
    </footer>

    <script>
        function aplicarPeriodoRelatorio(tipo) {
            const hoje = new Date();
            const fmt = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
            let inicio = new Date(hoje);
            let fim = new Date(hoje);

            if (tipo === '7dias') {
                inicio.setDate(hoje.getDate() - 6);
            } else if (tipo === 'mes') {
                inicio = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
                fim = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0);
            } else if (tipo === 'mes_anterior') {
                inicio = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
                fim = new Date(hoje.getFullYear(), hoje.getMonth(), 0);
            }

            const elIni = document.getElementById('data_inicial');
            const elFim = document.getElementById('data_final');
            const form = document.getElementById('reportFilterForm');
            if (elIni && elFim) {
                elIni.value = fmt(inicio);
                elFim.value = fmt(fim);
                if (form) form.submit();
            }
        }
    </script>
</body>
</html>