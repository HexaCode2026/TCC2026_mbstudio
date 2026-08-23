<?php
date_default_timezone_set('America/Sao_Paulo');
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

// Proteção para Cliente
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'C') {
    header("Location: ../../Index.php");
    exit;
}

$userId = $_SESSION['User_id'];

require_once "../../model/Client.php";
require_once "../../model/Appointment.php";

$clientModel = new Client($pdo);
$appointmentModel = new Appointment($pdo);

// Processa cancelamento do lado do cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'cancel' && isset($_POST['appo_id']) && isset($_POST['cancel_reason'])) {
        $appoId = (int)$_POST['appo_id'];
        $reason = trim($_POST['cancel_reason']);
        
        if (!empty($reason)) {
            // Verificar se o agendamento pertence ao cliente
            if ($appointmentModel->verificarDonoAgendamento($appoId, $userId)) {
                $appointmentModel->atualizarStatus($appoId, null, 'Cancelado pelo Cliente', 'Cliente', $reason);
            }
        }
        
        header("Location: MeusAgendamentos.php");
        exit;
    }
}

// Busca os agendamentos do cliente logado
$cliId = $clientModel->getOrCreateClientId($userId);
$appointments = $appointmentModel->listarMeusAgendamentos($cliId);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
    <style>
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }
        .btn-cancelar {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-cancelar:hover:not(:disabled) {
            background-color: #c82333;
        }
        .btn-cancelar:disabled {
            background-color: #e6a8af;
            cursor: not-allowed;
        }
        .input-cancelar {
            padding: 6px;
            margin-right: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 200px;
        }
        .form-cancelar-inline {
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <?php include '../components/Header.php'; ?>

    <div class="container">
        <h1>Meus Agendamentos</h1>
        <p>Acompanhe e gerencie seus serviços agendados.</p>

        <?php if (!empty($appointments)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Profissional</th>
                        <th>Serviço</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $app): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($app['Appo_date'])) ?></td>
                            <td>
                                <?= substr($app['Appo_start'], 0, 5) ?> às <?= substr($app['Appo_end'], 0, 5) ?>
                            </td>
                            <td><?= htmlspecialchars($app['employee_name']) ?></td>
                            <td>
                                <?= htmlspecialchars($app['Ser_name']) ?>
                                <br><small>R$ <?= number_format($app['Ser_price'], 2, ',', '.') ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($app['Appo_status']) ?></strong>
                                <?php if (!empty($app['Appo_cancel_reason'])): ?>
                                    <br><small style="color: #c62828;">Motivo: <?= htmlspecialchars($app['Appo_cancel_reason']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (strpos($app['Appo_status'], 'Cancelado') === false && $app['Appo_status'] != 'Concluido'): ?>
                                    <form action="" method="POST" class="form-cancelar-inline" onsubmit="return confirm('Deseja realmente cancelar este agendamento?');">
                                        <input type="hidden" name="appo_id" value="<?= $app['Appo_id'] ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="text" name="cancel_reason" class="input-cancelar" placeholder="Motivo do cancelamento..." required oninput="this.nextElementSibling.disabled = this.value.trim() === '';">
                                        <button type="submit" class="btn-cancelar" disabled>Cancelar</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #888;">Sem ações</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="margin-top: 20px; color: #7a756f;">Você não possui nenhum agendamento.</p>
        <?php endif; ?>
    </div>
</body>
</html>
