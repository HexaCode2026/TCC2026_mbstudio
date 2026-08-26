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
    <style>
        .page-container {
            max-width: 900px;
            margin: 100px auto 40px;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            font-family: sans-serif;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        .form-section {
            flex: 1;
            min-width: 300px;
            background: #faf8f3;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #eee8df;
        }
        .list-section {
            flex: 2;
            min-width: 400px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }
        .btn-gold {
            background: #b98527;
            color: #fff;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
        }
        .btn-gold:hover {
            background: #d4af37;
        }
        .table-mini {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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
        .btn-danger {
            color: #dc3545;
            text-decoration: none;
            font-weight: bold;
            font-size: 13px;
        }
        .btn-danger:hover {
            text-decoration: underline;
        }
        .alert {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            width: 100%;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
    </style>
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
