<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

// Buscar apenas os serviços ativos para os clientes escolherem
$sql = "SELECT * FROM services WHERE Ser_active = 1 ORDER BY Ser_name ASC";
$stmt = $pdo->query($sql);
$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Escolha um Serviço</title>
    <!-- Adicionando um estilo CSS mínimo dentro do arquivo apenas para deixar os botões "clicáveis" bonitos -->
    <style>
        .service-card {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: inline-block;
            width: 300px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: 0.3s;
            cursor: pointer;
        }
        .service-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            background-color: #f9f9f9;
        }
        .service-img {
            max-width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
        }
        .services-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
    </style>
</head>
<body>
    <?php include '../components/Header.php'; ?>

    <h1>Agendamento - Passo 1: Escolha um Serviço</h1>
    <a href="../../Index.php">Voltar para a Home</a>
    <hr>

    <?php if(count($servicos) > 0): ?>
        <div class="services-container">
            <?php foreach($servicos as $s): ?>
                
                <!-- O SERVIÇO AGORA É CLICÁVEL E REDIRECIONA PARA A ESCOLHA DA DATA -->
                <a href="#" onclick="checkAuthAndExecute(event, 'Data.php?Ser_id=<?= $s['Ser_id'] ?>')" class="service-card">
                    
                    <?php if(!empty($s['Ser_image'])): ?>
                        <!-- Caminho da imagem deve ser ajustado dependendo de onde o View é chamado -->
                        <img src="../../<?= htmlspecialchars($s['Ser_image']) ?>" alt="<?= htmlspecialchars($s['Ser_name']) ?>" class="service-img">
                    <?php else: ?>
                        <div style="height:150px; background:#e0e0e0; display:flex; align-items:center; justify-content:center; border-radius:4px;">
                            <span>Sem Imagem</span>
                        </div>
                    <?php endif; ?>

                    <h2><?= htmlspecialchars($s['Ser_name']) ?></h2>
                    <p><?= htmlspecialchars($s['Ser_description']) ?></p>
                    <p><strong>Preço:</strong> R$ <?= number_format($s['Ser_price'], 2, ',', '.') ?></p>
                    <p><strong>Tempo:</strong> <?= $s['Ser_duration'] ?> min</p>
                </a>
                
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No momento, não há serviços disponíveis.</p>
    <?php endif; ?>

    <?php include '../components/LoginModal.php'; ?>
</body>
</html>
