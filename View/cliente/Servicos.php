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
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serviços | MB Studio</title>

    <link rel="stylesheet" href="../../assets/css/global.css">

    <link rel="stylesheet" href="../../assets/css/cliente/servicos.css">
</head>

<body>

    <!-- Cabeçalho Oficial do Projeto -->
    <?php include '../components/Header.php'; ?>

    <main class="servicos-page">

        <!-- Decorações Finas de Fundo -->
        <div class="decoracao decoracao-esquerda"></div>
        <div class="decoracao decoracao-direita"></div>

        <!-- Seção de Introdução -->
        <section class="intro">
            <div class="intro-text">
                <span class="subtitulo">
                    NOSSOS SERVIÇOS
                </span>

                <h1>
                    Cuidado e <span>beleza</span><br>
                    para todos os estilos.
                </h1>

                <div class="linha-titulo"></div>

                <p>
                    Serviços de qualidade para realçar<br>
                    a sua beleza no dia a dia.
                </p>
            </div>

            <!-- Ilustração Temática -->
            <div class="ilustracao" aria-hidden="true">
                <div class="tesoura">✂</div>
                <div class="pente">║</div>
                <div class="secador">◯</div>
                <div class="brilho brilho-1">✧</div>
                <div class="brilho brilho-2">✧</div>
            </div>
        </section>

        <!-- Grid de Serviços Dinâmicos do Banco de Dados -->
        <?php if (count($servicos) > 0): ?>
            <section class="servicos-grid" id="catalogo-servicos">
                <?php foreach ($servicos as $s): ?>
                    <!-- Card de Serviço Clicável que Redireciona para o Agendamento -->
                    <a href="#" onclick="checkAuthAndExecute(event, 'Data.php?Ser_id=<?= $s['Ser_id'] ?>')" class="servico-card" title="Clique para agendar <?= htmlspecialchars($s['Ser_name']) ?>">
                        
                        <div class="servico-icone">
                            <?php if (!empty($s['Ser_image'])): ?>
                                <img src="../../<?= htmlspecialchars($s['Ser_image']) ?>" alt="<?= htmlspecialchars($s['Ser_name']) ?>">
                            <?php else: ?>
                                <span class="icone-tesoura">✂</span>
                            <?php endif; ?>
                        </div>

                        <h2><?= htmlspecialchars($s['Ser_name']) ?></h2>

                        <?php if (!empty($s['Ser_description'])): ?>
                            <p class="desc-servico"><?= htmlspecialchars($s['Ser_description']) ?></p>
                        <?php endif; ?>

                        <div class="servico-info-meta">
                            <span class="meta-preco"><strong>Preço:</strong> R$ <?= number_format($s['Ser_price'], 2, ',', '.') ?></span>
                            <span class="meta-duracao"><strong>Tempo:</strong> <?= $s['Ser_duration'] ?> min</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php else: ?>
            <p style="text-align: center; padding: 40px; color: #666;">No momento, não há serviços disponíveis.</p>
        <?php endif; ?>

    </main>

    <!-- Modal Global de Login / Cadastro -->
    <?php include '../components/LoginModal.php'; ?>

</body>

</html>