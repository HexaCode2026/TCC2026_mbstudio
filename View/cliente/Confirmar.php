<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'C') {
    header("Location: ../../Index.php");
    exit;
}

$ser_id = $_GET['Ser_id'] ?? null;
$data = $_GET['data'] ?? null;
$emp_id = $_GET['Emp_id'] ?? null;
$hora = $_GET['hora'] ?? null;

if (!$ser_id || !$data || !$emp_id || !$hora) {
    header("Location: Servicos.php");
    exit;
}

// Buscar dados do serviço
$stmtSer = $pdo->prepare("SELECT Ser_name, Ser_duration, Ser_price FROM services WHERE Ser_id = ?");
$stmtSer->execute([$ser_id]);
$servico = $stmtSer->fetch(PDO::FETCH_ASSOC);

// Buscar dados do funcionário
$stmtEmp = $pdo->prepare("
    SELECT u.User_name 
    FROM employees e 
    JOIN users u ON e.User_id = u.User_id 
    WHERE e.Emp_id = ?
");
$stmtEmp->execute([$emp_id]);
$funcionario = $stmtEmp->fetch(PDO::FETCH_ASSOC);

if (!$servico || !$funcionario) {
    echo "Dados inválidos.";
    exit;
}

// Calcular a hora de término baseada na duração do serviço
$horaFim = date('H:i', strtotime($hora) + ($servico['Ser_duration'] * 60));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Confirmar Agendamento</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
    <style>
        .confirm-box {
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            margin: 0 auto;
            background-color: #f9f9f9;
        }
        .btn-confirmar {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-confirmar:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <?php include '../components/Header.php'; ?>

    <div style="padding: 20px; text-align: center;">
        <h1>Agendamento - Passo 4: Confirmar</h1>
        
        <div class="confirm-box">
            <h2>Resumo do Agendamento</h2>
            <p><strong>Serviço:</strong> <?= htmlspecialchars($servico['Ser_name']) ?></p>
            <p><strong>Profissional:</strong> <?= htmlspecialchars($funcionario['User_name']) ?></p>
            <p><strong>Data:</strong> <?= date("d/m/Y", strtotime($data)) ?></p>
            <p><strong>Horário:</strong> <?= htmlspecialchars($hora) ?> às <?= htmlspecialchars($horaFim) ?></p>
            <p><strong>Valor:</strong> R$ <?= number_format($servico['Ser_price'], 2, ',', '.') ?></p>

            <form action="../../controller/ConfirmarAgendamento.php" method="POST">
                <input type="hidden" name="Ser_id" value="<?= htmlspecialchars($ser_id) ?>">
                <input type="hidden" name="data" value="<?= htmlspecialchars($data) ?>">
                <input type="hidden" name="Emp_id" value="<?= htmlspecialchars($emp_id) ?>">
                <input type="hidden" name="hora_inicio" value="<?= htmlspecialchars($hora) ?>">
                <input type="hidden" name="hora_fim" value="<?= htmlspecialchars($horaFim) ?>">
                <button type="submit" class="btn-confirmar">Confirmar e Agendar</button>
            </form>
            <br>
            <a href="Horarios.php?Ser_id=<?= htmlspecialchars($ser_id) ?>&data=<?= htmlspecialchars($data) ?>">Voltar</a>
        </div>
    </div>
</body>
</html>
