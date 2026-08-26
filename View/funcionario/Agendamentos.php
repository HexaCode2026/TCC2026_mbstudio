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
        $appoId = (int) $_POST['appo_id'];

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
        } elseif ($_POST['action'] === 'noshow') {
            $stmt = $pdo->prepare("UPDATE appointments SET Appo_status = 'Nao Compareceu' WHERE Appo_id = :id");
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

        header("Location: Agendamentos.php");
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
    usort($datasCadastradas, function ($a, $b) {
        return strtotime($a) - strtotime($b);
    });
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Agenda</title>
    <link rel="stylesheet" href="../../assets/css/Agenda.css">
</head>

<body>
    <?php include '../components/AuthButton.php'; ?>
    <?php include '../components/LoginModal.php'; ?>

    <main class="pagina-disponibilidade">

        <h1 class="tituloPagina"> Minha <span> Agenda </span> </h1>
        <p class="subtitulo"> Visualização e gerenciamento dos agendamentos de clientes </p>

        <!-- LISTAGEM DOS AGENDAMENTOS -->
        <h2>Agendamentos</h2>

        <?php if (!empty($appointments)): ?>
            <div style="margin-top: 15px; margin-bottom: 15px;">
                <label for="filtroData"><strong>Selecionar Data:</strong></label>
                <select id="filtroData" onchange="filtrarTabelaPorData()"
                    style="height: 38px; padding: 0 10px; border: 1px solid #ddd4c8; border-radius: 8px;">
                    <option value="todas">Todas as datas</option>
                    <?php foreach ($datasCadastradas as $data): ?>
                        <option value="<?= htmlspecialchars($data) ?>"><?= date('d/m/Y', strtotime($data)) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="filtroStatus" style="margin-left: 20px;"><strong>Status:</strong></label>
                <select id="filtroStatus" onchange="filtrarTabelaPorData()"
                    style="height: 38px; padding: 0 10px; border: 1px solid #ddd4c8; border-radius: 8px;">
                    <option value="todos">Todos os status</option>
                    <option value="Pendente">Pendente</option>
                    <option value="Confirmado">Confirmado</option>
                    <option value="Em Atendimento">Em Atendimento</option>
                    <option value="Concluido">Concluído</option>
                    <option value="Cancelado">Cancelado</option>
                    <option value="Nao Compareceu">Não Compareceu</option>
                </select>
            </div>

            <table id="tabelaAgenda">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th>Cliente</th>
                        <th>Serviço</th>
                        <th>Observação</th>
                        <th>Status Atual</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $app): ?>
                        <tr data-data="<?= htmlspecialchars($app['Appo_date']) ?>"
                            data-status="<?= htmlspecialchars($app['Appo_status']) ?>">
                            <td><?= date('d/m/Y', strtotime($app['Appo_date'])) ?></td>
                            <td><?= htmlspecialchars(substr($app['Appo_start'], 0, 5)) ?></td>
                            <td><?= htmlspecialchars(substr($app['Appo_end'], 0, 5)) ?></td>
                            <td>
                                <?= htmlspecialchars($app['client_name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($app['Ser_name']) ?>
                                <br><small style="color: #888;">R$ <?= number_format($app['Ser_price'], 2, ',', '.') ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars(!empty($app['Appo_observation']) ? $app['Appo_observation'] : 'Nenhuma') ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($app['Appo_status']) ?></strong>
                                <?php if ($app['Appo_status'] === 'Concluido' && !empty($app['Appo_updated'])): ?>
                                    <br><small style="color: #28a745;">Finalizado em:
                                        <?= date('d/m/Y H:i', strtotime($app['Appo_updated'])) ?></small>
                                <?php endif; ?>
                                <?php if (!empty($app['Appo_cancel_reason'])): ?>
                                    <br><small style="color: #c62828;">Motivo:
                                        <?= htmlspecialchars($app['Appo_cancel_reason']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- SE PENDENTE -->
                                <?php if ($app['Appo_status'] == 'Pendente'): ?>
                                    <form action="" method="POST" style="display:inline;">
                                        <input type="hidden" name="appo_id" value="<?= $app['Appo_id'] ?>">
                                        <button type="submit" name="action" value="accept"
                                            class="btn-acao btn-aceitar">Aceitar</button>
                                    </form>
                                <?php endif; ?>

                                <!-- SE CONFIRMADO -->
                                <?php if ($app['Appo_status'] == 'Confirmado'): ?>
                                    <form action="" method="POST" style="display:inline;">
                                        <input type="hidden" name="appo_id" value="<?= $app['Appo_id'] ?>">
                                        <button type="submit" name="action" value="start"
                                            class="btn-acao btn-iniciar">Iniciar</button>
                                    </form>
                                    <form action="" method="POST" style="display:inline;"
                                        onsubmit="return confirm('Deseja registrar como Não Compareceu?');">
                                        <input type="hidden" name="appo_id" value="<?= $app['Appo_id'] ?>">
                                        <button type="submit" name="action" value="noshow" class="btn-acao btn-cancelar"
                                            style="background-color: #f39c12; margin-top: 5px;">Não Compareceu</button>
                                    </form>
                                <?php endif; ?>

                                <!-- SE EM ATENDIMENTO -->
                                <?php if ($app['Appo_status'] == 'Em Atendimento'): ?>
                                    <form action="" method="POST" style="display:inline;">
                                        <input type="hidden" name="appo_id" value="<?= $app['Appo_id'] ?>">
                                        <button type="submit" name="action" value="finish"
                                            class="btn-acao btn-concluir">Finalizar</button>
                                    </form>
                                <?php endif; ?>

                                <!-- CANCELAMENTO (SE NÃO ESTIVER CANCELADO, CONCLUIDO OU NAO COMPARECEU) -->
                                <?php if (strpos($app['Appo_status'], 'Cancelado') === false && $app['Appo_status'] != 'Concluido' && $app['Appo_status'] != 'Nao Compareceu'): ?>
                                    <form action="" method="POST" class="form-cancelar-inline"
                                        onsubmit="return confirm('Deseja realmente cancelar este agendamento?');">
                                        <input type="hidden" name="appo_id" value="<?= $app['Appo_id'] ?>">
                                        <input type="text" name="cancel_reason" class="input-cancelar"
                                            placeholder="Motivo do cancelamento..." required
                                            oninput="this.nextElementSibling.disabled = this.value.trim() === '';">
                                        <button type="submit" name="action" value="cancel" class="btn-acao btn-cancelar"
                                            disabled>Cancelar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else: ?>
            <p style="margin-top: 20px; color: #7a756f;">Nenhum agendamento encontrado.</p>
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