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

if (!$ser_id) {
    header("Location: Servicos.php");
    exit;
}

require_once "../../model/Service.php";

// Buscar detalhes do Serviço (para mostrar ao usuário)
$serviceModel = new Service($pdo);
$servico = $serviceModel->buscarPorId($ser_id);

if (!$servico) {
    header("Location: Servicos.php");
    exit;
}

// Para UX básica: Não deixar escolher data no passado
$hoje = date("Y-m-d");
?>
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="../../assets/css/global.css">
</head>

<body>
    <?php include '../components/Header.php'; ?>
    <?php include '../components/LoginModal.php'; ?>

    <div style="padding: 20px;">
        <h1>Agendamento - Passo 2: Escolha a Data</h1>
    <a href="Servicos.php">Voltar para a escolha de serviços</a>
    <hr>

    <h2>Serviço Selecionado: <u><?= htmlspecialchars($servico['Ser_name']) ?></u></h2>

    <form action="Horarios.php" method="GET">
        <input type="hidden" name="Ser_id" value="<?= htmlspecialchars($ser_id) ?>">

        <label for="data">Selecione o dia do agendamento:</label><br><br>
        <!-- input do tipo date com restrição mínima para o dia atual -->
        <input type="date" id="data" name="data" min="<?= $hoje ?>" required>
        <br><br>

        <button type="submit">Avançar para ver horários</button>
    </form>
    </div>

</body>

</html>