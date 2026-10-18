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
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios | MB Studio</title>

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/admin/relatorios.css">
</head>

<body>
    <!-- Cabeçalho Global MB Studio -->
    <?php include '../components/Header.php'; ?>

    <main class="admin-dashboard-wrapper">
        <div class="section-header-admin" style="margin-bottom: 24px;">
            <h1 class="section-title-admin">
                <span>✦</span> Relatório Gerencial
            </h1>
            <p class="section-desc-admin">Filtre e analise os agendamentos e o desempenho financeiro do estúdio.</p>
        </div>

        <!-- Formulário de Filtro de Período -->
        <div class="report-filter-card">
            <form method="GET" class="report-filter-form">
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

        <!-- Cards Contadores -->
        <div class="metrics-grid-report">
            <div class="metric-card-white">
                <div class="metric-title">Total de Agendamentos</div>
                <div class="metric-number"><?= $totalRegistros ?></div>
                <p class="metric-description">Registros encontrados no período.</p>
            </div>
            <div class="metric-card-white">
                <div class="metric-title">Faturamento (Concluídos)</div>
                <div class="metric-number">R$ <?= number_format($faturamentoTotal, 2, ',', '.') ?></div>
                <p class="metric-description">Receita bruta gerada no período.</p>
            </div>

            <?php foreach ($totaisStatus as $status): ?>
                <div class="metric-card-white">
                    <div class="metric-title">Status: <?= htmlspecialchars($status['Status']) ?></div>
                    <div class="metric-number"><?= $status['Total'] ?></div>
                    <p class="metric-description">Total neste status.</p>
                </div>
            <?php endforeach; ?>
        </div>

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
                                    <td><span class="status-pill <?= $badgeClass ?>"><?= htmlspecialchars($linha['Status']) ?></span></td>
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
</body>
</html>