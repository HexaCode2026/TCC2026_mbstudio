<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

// Proteção para Cliente
if(!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'C'){
    header("Location: ../Login.php");
    exit;
}

$ser_id = $_GET['Ser_id'] ?? null;

if (!$ser_id) {
    header("Location: Servicos.php");
    exit;
}

// Buscar detalhes do Serviço escolhido (opcional, para exibir no topo)
$sqlServico = "SELECT Ser_name FROM services WHERE Ser_id = ?";
$stmtServico = $pdo->prepare($sqlServico);
$stmtServico->execute([$ser_id]);
$servico = $stmtServico->fetch(PDO::FETCH_ASSOC);

if (!$servico) {
    header("Location: Servicos.php");
    exit;
}

// Buscar os funcionários habilitados para este serviço específico,
// garantindo que são marcados como Funcionários (User_perm = 'F')
$sql = "SELECT e.Emp_id, u.User_name, e.Emp_photo, e.Emp_specialty 
        FROM employee_services es
        JOIN employees e ON es.Emp_id = e.Emp_id
        JOIN users u ON e.User_id = u.User_id
        WHERE es.Ser_id = ? AND u.User_perm = 'F'
        ORDER BY u.User_name ASC";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([$ser_id]);
$funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Escolha o Funcionário</title>
    <!-- Estilo mínimo sem depender de bibliotecas externas -->
    <style>
        .emp-card {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: inline-block;
            width: 250px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: 0.3s;
            cursor: pointer;
        }
        .emp-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            background-color: #f9f9f9;
        }
        .emp-photo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        .emp-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
    </style>
</head>
<body>

    <h1>Agendamento - Passo 2: Escolha o Profissional</h1>
    <a href="Servicos.php">Voltar para a escolha de serviços</a>
    <hr>
    
    <h2>Profissionais que realizam: <u><?= htmlspecialchars($servico['Ser_name']) ?></u></h2>

    <?php if(count($funcionarios) > 0): ?>
        <div class="emp-container">
            <?php foreach($funcionarios as $f): ?>
                
                <!-- Redireciona para o passo 3: Escolha do Horário -->
                <a href="Horarios.php?Ser_id=<?= $ser_id ?>&Emp_id=<?= $f['Emp_id'] ?>" class="emp-card">
                    
                    <?php if(!empty($f['Emp_photo'])): ?>
                        <img src="../../<?= htmlspecialchars($f['Emp_photo']) ?>" alt="<?= htmlspecialchars($f['User_name']) ?>" class="emp-photo">
                    <?php else: ?>
                        <!-- Placeholder se o funcionário não tiver foto -->
                        <div style="width:100px; height:100px; background:#e0e0e0; border-radius:50%; margin:0 auto 10px auto; display:flex; align-items:center; justify-content:center;">
                            <span>Sem Foto</span>
                        </div>
                    <?php endif; ?>

                    <h3><?= htmlspecialchars($f['User_name']) ?></h3>
                    <p><em><?= htmlspecialchars($f['Emp_specialty'] ?: 'Especialista') ?></em></p>
                </a>
                
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No momento, não há funcionários (F) disponíveis para este serviço.</p>
    <?php endif; ?>

</body>
</html>
