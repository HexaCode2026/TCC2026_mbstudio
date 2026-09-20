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
    <link
        href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin/dashboard.css">

    <style>
        .report-form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
            display: flex;
            gap: 16px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 0.9rem;
            font-weight: 500;
            color: #4b5563;
        }

        .form-group input {
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            outline: none;
            font-family: 'Inter', sans-serif;
        }

        .btn-filter {
            background-color: #111827;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
            font-family: 'Inter', sans-serif;
            height: 40px;
            /* Alinhar com inputs */
        }

        .btn-filter:hover {
            background-color: #374151;
        }

        .metrics-grid-report {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .report-table-wrapper {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th,
        .report-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .report-table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        .report-table td {
            font-size: 0.9rem;
            color: #4b5563;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge.pendente {
            background: #fef3c7;
            color: #92400e;
        }

        .badge.concluido {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.cancelado {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge.confirmado {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge.default {
            background: #f3f4f6;
            color: #374151;
        }
    </style>
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
        <form method="GET" class="report-form">
            <div class="form-group">
                <label for="data_inicial">Data Inicial</label>
                <input type="date" name="data_inicial" id="data_inicial" value="<?= htmlspecialchars($dataInicial) ?>"
                    required>
            </div>
            <div class="form-group">
                <label for="data_final">Data Final</label>
                <input type="date" name="data_final" id="data_final" value="<?= htmlspecialchars($dataFinal) ?>"
                    required>
            </div>
            <div class="form-group">
                <label for="emp_id">Profissional</label>
                <select name="emp_id" id="emp_id" style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; font-family: 'Inter', sans-serif; background: #fff; height: 40px; min-width: 200px;">
                    <option value="">Todos os Profissionais</option>
                    <?php foreach ($funcionarios as $func): ?>
                        <option value="<?= $func['Emp_id'] ?>" <?= $empId == $func['Emp_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($func['User_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-filter">Aplicar Filtro</button>
        </form>

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
        <div class="report-table-wrapper">
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
                            <td colspan="8" style="text-align: center; padding: 24px;">Nenhum agendamento encontrado para o
                                período selecionado.</td>
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
                                <td>#<?= $linha['ID_Agendamento'] ?></td>
                                <td>
                                    <strong><?= date('d/m/Y', strtotime($linha['Data_Agendamento'])) ?></strong><br>
                                    <span
                                        style="font-size: 0.85rem; color: #6b7280;"><?= date('H:i', strtotime($linha['Hora_Inicio'])) ?>
                                        - <?= date('H:i', strtotime($linha['Hora_Fim'])) ?></span>
                                </td>
                                <td><?= htmlspecialchars($linha['Nome_Cliente']) ?></td>
                                <td><?= htmlspecialchars($linha['Nome_Profissional']) ?></td>
                                <td><?= htmlspecialchars($linha['Servico_Realizado']) ?></td>
                                <td><strong><?= number_format($linha['Valor_Servico'], 2, ',', '.') ?></strong></td>
                                <td><?= htmlspecialchars($linha['Forma_Pagamento'] ?: '-') ?></td>
                                <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($linha['Status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer class="admin-panel-footer">
        <p>&copy; <?= date('Y') ?> MB Studio. Painel Administrativo de Gestão.</p>
    </footer>
</body>

</html>