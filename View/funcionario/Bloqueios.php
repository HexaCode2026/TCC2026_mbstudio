<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";
require_once "../../model/EmployeeBlock.php";

Session::iniciar();

// Apenas Funcionários
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'F') {
    header("Location: ../../Index.php");
    exit;
}

$userId = $_SESSION['User_id'];

// Obter Emp_id
$stmtEmp = $pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
$stmtEmp->execute([$userId]);
$empId = $stmtEmp->fetchColumn();

if (!$empId) {
    echo "Erro: Conta de funcionário não configurada corretamente.";
    exit;
}

// Buscar bloqueios
$blockModel = new EmployeeBlock($pdo);
$bloqueios = $blockModel->listarPorFuncionario($empId);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pausas e Bloqueios | MB Studio</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/funcionario/bloqueios.css">
</head>
<body style="background: #fdfcf9;">

    <?php include '../components/Header.php'; ?>

    <div class="page-container">
        
        <div style="width: 100%;">
            <h2 style="color: #111; margin-bottom: 5px;">Minhas Pausas e Exceções</h2>
            <p style="color: #666; margin-bottom: 25px;">Adicione intervalos pontuais onde você não poderá atender (Ex: Almoço, Médico).</p>
            
            <?php if (isset($_GET['sucesso'])): ?>
                <div class="alert alert-success">Operação realizada com sucesso!</div>
            <?php endif; ?>
            <?php if (isset($_GET['erro'])): ?>
                <?php if ($_GET['erro'] == 'conflito_agendamento'): ?>
                    <div class="alert alert-error">Você já possui um agendamento marcado neste horário. Cancele-o primeiro antes de adicionar a pausa.</div>
                <?php elseif ($_GET['erro'] == 'conflito_bloqueio'): ?>
                    <div class="alert alert-error">Você já cadastrou uma pausa que entra em conflito com este horário.</div>
                <?php else: ?>
                    <div class="alert alert-error">Ocorreu um erro na operação. Verifique os dados.</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Formulário de Cadastro de Bloqueio -->
        <div class="form-section">
            <h3 style="color: #b98527; margin-bottom: 15px;">Novo Bloqueio</h3>
            <form action="../../controller/SalvarBloqueio.php" method="POST">
                <div class="form-group">
                    <label>Data:</label>
                    <input type="date" name="data" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Hora Inicial:</label>
                    <input type="time" name="hora_inicio" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Hora Final:</label>
                    <input type="time" name="hora_fim" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Motivo (Opcional):</label>
                    <input type="text" name="motivo" class="form-control" placeholder="Ex: Almoço">
                </div>
                <button type="submit" class="btn-gold">Adicionar Bloqueio</button>
            </form>
        </div>

        <!-- Listagem de Bloqueios Futuros -->
        <div class="list-section">
            <h3 style="color: #111; margin-bottom: 15px;">Bloqueios Cadastrados</h3>
            
            <?php if (count($bloqueios) > 0): ?>
                <table class="table-mini">
                    <tr>
                        <th>Data</th>
                        <th>Período</th>
                        <th>Motivo</th>
                        <th>Ação</th>
                    </tr>
                    <?php foreach ($bloqueios as $b): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($b['Block_date'])) ?></td>
                        <td><?= date('H:i', strtotime($b['Block_start'])) ?> às <?= date('H:i', strtotime($b['Block_end'])) ?></td>
                        <td><?= htmlspecialchars($b['Block_reason'] ?: '-') ?></td>
                        <td>
                            <a href="../../controller/ExcluirBloqueio.php?id=<?= $b['Block_id'] ?>" 
                               class="btn-danger" 
                               onclick="return confirm('Tem certeza que deseja remover esta pausa?')">
                               Remover
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <div style="padding: 30px; text-align: center; color: #999; border: 1px dashed #ccc; border-radius: 8px;">
                    Nenhuma pausa programada para os próximos dias.
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>
