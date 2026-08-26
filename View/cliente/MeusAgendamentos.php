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
    // Buscar agendamentos do cliente
    $sql = "SELECT a.Appo_id, a.Appo_date, a.Appo_start, a.Appo_status, 
                   s.Ser_name, u.User_name as Funcionario
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Meus Agendamentos</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
    <style>
        .page-container {
            max-width: 900px;
            margin: 100px auto 40px;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            font-family: sans-serif;
        }
        .table-mini {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table-mini th {
            background: #f4f0e6;
            color: #b98527;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #e2d7c1;
        }
        .table-mini td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-Pendente { background: #fff3cd; color: #856404; }
        .status-Confirmado { background: #d4edda; color: #155724; }
        .status-Cancelado { background: #f8d7da; color: #721c24; }
        .status-Concluido { background: #cce5ff; color: #004085; }
    </style>
</head>
<body style="background: #fdfcf9;">

    <?php include '../components/Header.php'; ?>

    <div class="page-container">
        <h2 style="color: #111;">Meus Agendamentos</h2>
        <p style="color: #666; margin-bottom: 20px;">Acompanhe o status das suas solicitações. Lembre-se: o salão precisa confirmar o seu horário.</p>

        <?php if (isset($_GET['sucesso'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 20px;">
                <?php
                if ($_GET['sucesso'] == 'agendamento_cancelado') {
                    echo "Seu agendamento foi cancelado com sucesso.";
                } else {
                    echo "Agendamento solicitado com sucesso! Aguarde a confirmação do profissional.";
                }
                ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['erro'])): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 6px; margin-bottom: 20px;">
                Ocorreu um erro ao processar sua solicitação. Tente novamente.
            </div>
        <?php endif; ?>

        <?php if (count($agendamentos) > 0): ?>
            <table class="table-mini">
                <tr>
                    <th>Data</th>
                    <th>Hora</th>
                    <th>Serviço</th>
                    <th>Profissional</th>
                    <th>Status</th>
                    <th style="text-align: center;">Ações</th>
                </tr>
                <?php foreach ($agendamentos as $a): 
                    $statusClass = 'status-' . explode(' ', $a['Appo_status'])[0]; 
                ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($a['Appo_date'])) ?></td>
                    <td><?= date('H:i', strtotime($a['Appo_start'])) ?></td>
                    <td><?= htmlspecialchars($a['Ser_name']) ?></td>
                    <td><?= htmlspecialchars($a['Funcionario']) ?></td>
                    <td><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($a['Appo_status']) ?></span></td>
                    <td style="text-align: center;">
                        <?php if ($a['Appo_status'] == 'Pendente' || $a['Appo_status'] == 'Confirmado'): ?>
                            <form action="../../controller/CancelarAgendamento.php" method="POST" style="margin: 0; display: inline-block;" onsubmit="return confirm('Tem certeza que deseja cancelar este agendamento?');">
                                <input type="hidden" name="appo_id" value="<?= $a['Appo_id'] ?>">
                                <input type="hidden" name="cancel_reason" value="Cancelado pelo Cliente via painel">
                                <button type="submit" style="background: none; border: none; color: #dc3545; font-weight: bold; cursor: pointer; text-decoration: underline;">
                                    Cancelar
                                </button>
                            </form>
                        <?php else: ?>
                            <span style="color: #ccc;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <p>Você não possui nenhum agendamento no momento.</p>
                <a href="Servicos.php" style="color: #b98527; font-weight: bold; text-decoration: none;">Ver Serviços</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
