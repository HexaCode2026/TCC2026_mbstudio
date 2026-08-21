<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

// Pega o ID do usuário logado
$userId = $_SESSION['User_id'] ?? 0;

// Processa formulários de aceite e cancelamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['appo_id'])) {
        $appoId = (int)$_POST['appo_id'];
        
        if ($_POST['action'] === 'accept') {
            $stmt = $pdo->prepare("UPDATE appointments SET Appo_status = 'Confirmado' WHERE Appo_id = :id");
            $stmt->bindParam(':id', $appoId, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($_POST['action'] === 'cancel' && isset($_POST['cancel_reason'])) {
            $reason = trim($_POST['cancel_reason']);
            $stmt = $pdo->prepare("UPDATE appointments SET Appo_status = 'Cancelado pelo Funcionario', Appo_cancel_by = 'Funcionario', Appo_cancel_reason = :reason WHERE Appo_id = :id");
            $stmt->bindParam(':reason', $reason, PDO::PARAM_STR);
            $stmt->bindParam(':id', $appoId, PDO::PARAM_INT);
            $stmt->execute();
        }
    }
}

// Busca os agendamentos do funcionário vinculado ao usuário logado
$query = "SELECT a.*, c_user.User_name as client_name, s.Ser_name 
          FROM appointments a
          JOIN clients c ON a.Cli_id = c.Cli_id
          JOIN users c_user ON c.User_id = c_user.User_id
          JOIN services s ON a.Ser_id = s.Ser_id
          JOIN employees e ON a.Emp_id = e.Emp_id
          WHERE e.User_id = :user_id
          ORDER BY a.Appo_date, a.Appo_start";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda de Agendamentos</title>
</head>
<body>
    <div class="agenda-container">
        <h1>Minha Agenda</h1>
        
        <?php if(empty($appointments)): ?>
            <p style="text-align: center;">Nenhum agendamento encontrado.</p>
        <?php else: ?>
            <?php foreach($appointments as $app): ?>
                <div class="appointment-card">
                    <?php
                        $statusClass = 'status-pendente';
                        if ($app['Appo_status'] == 'Confirmado' || $app['Appo_status'] == 'Concluido') $statusClass = 'status-confirmado';
                        if (strpos($app['Appo_status'], 'Cancelado') !== false) $statusClass = 'status-cancelado';
                    ?>
                    <p><strong>Status:</strong> <span class="status <?= $statusClass ?>"><?= htmlspecialchars($app['Appo_status']) ?></span></p>
                    <p><strong>Cliente:</strong> <?= htmlspecialchars($app['client_name']) ?></p>
                    <p><strong>Serviço:</strong> <?= htmlspecialchars($app['Ser_name']) ?></p>
                    <p><strong>Data:</strong> <?= date('d/m/Y', strtotime($app['Appo_date'])) ?></p>
                    <p><strong>Horário:</strong> <?= htmlspecialchars($app['Appo_start']) ?> às <?= htmlspecialchars($app['Appo_end']) ?></p>
                    
                    <p><strong>Observação do Cliente:</strong> <?= htmlspecialchars(!empty($app['Appo_observation']) ? $app['Appo_observation'] : 'Nenhuma') ?></p>
                    
                    <p><strong>Motivo do Cancelamento:</strong> <?= htmlspecialchars(!empty($app['Appo_cancel_reason']) ? $app['Appo_cancel_reason'] : 'N/A') ?></p>
                    <p><strong>Cancelado por:</strong> <?= htmlspecialchars(!empty($app['Appo_cancel_by']) ? $app['Appo_cancel_by'] : 'N/A') ?></p>
                    
                    <div class="actions">
                        <?php if($app['Appo_status'] == 'Pendente'): ?>
                            <form action="" method="POST" style="display:inline;">
                                <input type="hidden" name="appo_id" value="<?= $app['Appo_id'] ?>">
                                <button type="submit" name="action" value="accept" class="btn btn-accept">Aceitar Agendamento</button>
                            </form>
                        <?php endif; ?>

                        <?php if(strpos($app['Appo_status'], 'Cancelado') === false && $app['Appo_status'] != 'Concluido'): ?>
                            <form action="" method="POST" class="cancel-form">
                                <input type="hidden" name="appo_id" value="<?= $app['Appo_id'] ?>">
                                <input type="text" name="cancel_reason" class="cancel-input" placeholder="Motivo do cancelamento..." required>
                                <button type="submit" name="action" value="cancel" class="btn btn-cancel">Cancelar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
