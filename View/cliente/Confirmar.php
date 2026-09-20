<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

// Proteção para Cliente
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'C') {
    header("Location: ../../Index.php");
    exit;
}

$ser_id = $_GET['Ser_id'] ?? null;
$emp_id = $_GET['Emp_id'] ?? null;
$data = $_GET['data'] ?? null;
$hora = $_GET['hora'] ?? null;

if (!$ser_id || !$emp_id || !$data || !$hora) {
    header("Location: Servicos.php");
    exit;
}

// Buscar detalhes do serviço
$stmtServico = $pdo->prepare("SELECT Ser_name, Ser_price, Ser_duration FROM services WHERE Ser_id = ?");
$stmtServico->execute([$ser_id]);
$servico = $stmtServico->fetch(PDO::FETCH_ASSOC);

// Buscar detalhes do profissional
$stmtFunc = $pdo->prepare("SELECT u.User_name FROM employees e JOIN users u ON e.User_id = u.User_id WHERE e.Emp_id = ?");
$stmtFunc->execute([$emp_id]);
$funcionario = $stmtFunc->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Agendamento</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/cliente/confirmar.css">
</head>
<body style="background: #fdfcf9; text-align: center;">

    <?php include '../components/Header.php'; ?>

    <h2 style="margin-top: 80px; color: #111;">Confirmação de Agendamento</h2>

    <div class="mini-card">
        <h3 style="color: #b98527; margin-bottom: 15px; text-align: center;">Resumo do Pedido</h3>
        <p><strong>Serviço:</strong> <?= htmlspecialchars($servico['Ser_name'] ?? '') ?></p>
        <p><strong>Preço:</strong> R$ <?= number_format($servico['Ser_price'] ?? 0, 2, ',', '.') ?></p>
        <p><strong>Profissional:</strong> <?= htmlspecialchars($funcionario['User_name'] ?? '') ?></p>
        <p><strong>Data:</strong> <?= date('d/m/Y', strtotime($data)) ?></p>
        <p><strong>Hora:</strong> <?= htmlspecialchars($hora) ?></p>

        <form action="../../controller/Agendar.php" method="POST" style="margin-top: 20px;">
            <input type="hidden" name="ser_id" value="<?= htmlspecialchars($ser_id) ?>">
            <input type="hidden" name="emp_id" value="<?= htmlspecialchars($emp_id) ?>">
            <input type="hidden" name="data" value="<?= htmlspecialchars($data) ?>">
            <input type="hidden" name="hora" value="<?= htmlspecialchars($hora) ?>">
            
            <label style="font-weight: bold; display: block; margin-bottom: 5px;">Observação (Opcional):</label>
            <textarea name="observacao" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box;"></textarea>
            
            <button type="submit" class="btn-gold">Confirmar e Solicitar Horário</button>
        </form>
    </div>

</body>
</html>
