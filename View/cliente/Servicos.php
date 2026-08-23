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

    <style>
        :root {
            --preto: #111111;
            --dourado: #b98527;
            --dourado-claro: #d6b56e;
            --fundo: #fdfcf9;
            --card: #f8f2e9;
            --borda: #eee8df;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: var(--fundo);
            color: var(--preto);
            font-family: Arial, Helvetica, sans-serif;
            min-height: 100vh;
            padding-top: 75px;
            overflow-x: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .servicos-page {
            min-height: calc(100vh - 75px);
            position: relative;
            overflow: hidden;
            padding: 42px 9% 60px;
            background:
                radial-gradient(
                    circle at 15% 80%,
                    rgba(185, 133, 39, 0.035),
                    transparent 28%
                ),
                var(--fundo);
        }

        /* Decorações do Fundo */
        .decoracao {
            position: absolute;
            pointer-events: none;
            opacity: 0.45;
        }

        .decoracao-esquerda {
            width: 210px;
            height: 500px;
            left: -100px;
            top: 170px;
            border-right: 1px solid var(--dourado-claro);
            border-radius: 50%;
        }

        .decoracao-esquerda::before,
        .decoracao-esquerda::after {
            content: "";
            position: absolute;
            border-right: 1px solid var(--dourado-claro);
            border-radius: 50%;
        }

        .decoracao-esquerda::before {
            width: 180px;
            height: 450px;
            top: 20px;
            left: 25px;
        }

        .decoracao-esquerda::after {
            width: 150px;
            height: 400px;
            top: 45px;
            left: 50px;
        }

        .decoracao-direita {
            width: 300px;
            height: 650px;
            right: -180px;
            top: -50px;
            border-left: 1px solid var(--dourado-claro);
            border-radius: 50%;
        }

        .decoracao-direita::before,
        .decoracao-direita::after {
            content: "";
            position: absolute;
            border-left: 1px solid var(--dourado-claro);
            border-radius: 50%;
        }

        .decoracao-direita::before {
            width: 270px;
            height: 600px;
            right: 25px;
            top: 25px;
        }

        .decoracao-direita::after {
            width: 240px;
            height: 550px;
            right: 50px;
            top: 50px;
        }

        /* Introdução */
        .intro {
            width: 100%;
            min-height: 235px;
            display: flex;
            justify-content: space-between;
            position: relative;
            z-index: 2;
            margin-bottom: 20px;
        }

        .intro-text {
            padding-left: 5px;
        }

        .subtitulo {
            color: var(--dourado);
            font-size: 15px;
            letter-spacing: 3px;
            display: block;
            margin-bottom: 17px;
        }

        .intro h1 {
            font-family: Georgia, "Times New Roman", serif;
            font-weight: 400;
            font-size: 48px;
            line-height: 0.98;
            letter-spacing: -1px;
        }

        .intro h1 span {
            color: var(--dourado);
        }

        .linha-titulo {
            width: 65px;
            height: 2px;
            background: var(--dourado);
            margin-top: 23px;
            margin-bottom: 16px;
        }

        .intro p {
            font-size: 16px;
            line-height: 1.7;
            color: #151515;
        }

        /* Ilustração */
        .ilustracao {
            width: 500px;
            height: 200px;
            margin-right: 20px;
            position: relative;
            color: var(--dourado-claro);
            opacity: 0.72;
            user-select: none;
            pointer-events: none;
        }

        .tesoura {
            position: absolute;
            left: 20px;
            top: 42px;
            font-size: 72px;
            transform: rotate(-28deg);
            font-family: Georgia, serif;
        }

        .pente {
            position: absolute;
            left: 150px;
            top: 22px;
            font-size: 78px;
            transform: rotate(19deg);
        }

        .secador {
            position: absolute;
            right: 65px;
            top: 42px;
            width: 160px;
            height: 65px;
            border: 1px solid var(--dourado-claro);
            border-radius: 35px;
            font-size: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: rotate(5deg);
        }

        .brilho {
            position: absolute;
            font-family: Georgia, serif;
            font-size: 29px;
        }

        .brilho-1 {
            left: 0;
            top: 45px;
        }

        .brilho-2 {
            right: 190px;
            bottom: 5px;
        }

        /* Grid de Serviços Ampliado e Mais Espaçoso */
        .servicos-grid {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
            gap: 28px;
            position: relative;
            z-index: 3;
            margin-top: 25px;
            margin-bottom: 20px;
        }

        /* Cards Maiores e Mais Nobres */
        .servico-card {
            min-height: 380px;
            background:
                linear-gradient(
                    145deg,
                    rgba(255, 255, 255, 0.65),
                    rgba(248, 242, 233, 0.95)
                );
            border: 1px solid rgba(214, 181, 110, 0.45);
            border-radius: 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 28px 22px 22px;
            text-align: center;
            box-shadow: 0 6px 16px rgba(185, 133, 39, 0.06), 0 2px 4px rgba(0, 0, 0, 0.02);
            transition:
                transform 0.3s cubic-bezier(0.25, 1, 0.5, 1),
                box-shadow 0.3s cubic-bezier(0.25, 1, 0.5, 1),
                border-color 0.3s ease,
                background 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
        }

        .servico-card:hover {
            transform: translateY(-8px);
            border-color: var(--dourado);
            box-shadow: 0 16px 32px rgba(185, 133, 39, 0.18), 0 4px 10px rgba(0, 0, 0, 0.04);
            background: #ffffff;
        }

        .servico-card:active {
            transform: translateY(-3px);
        }

        /* Moldura de Foto do Serviço Maior */
        .servico-icone {
            width: 140px;
            height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dourado);
            margin-bottom: 16px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid rgba(214, 181, 110, 0.4);
            box-shadow: 0 6px 18px rgba(185, 133, 39, 0.15);
            background: linear-gradient(135deg, #fdfcf9, #f7f1e6);
            flex-shrink: 0;
            transition: transform 0.3s ease, border-color 0.3s ease;
        }

        .servico-card:hover .servico-icone {
            transform: scale(1.05);
            border-color: var(--dourado);
            box-shadow: 0 8px 24px rgba(185, 133, 39, 0.25);
        }

        .servico-icone img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            display: block;
        }

        .icone-tesoura {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 65px;
            line-height: 1;
            transform: rotate(-10deg);
            display: inline-block;
        }

        .servico-card h2 {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 24px;
            font-weight: 600;
            margin-top: 4px;
            margin-bottom: 10px;
            line-height: 1.25;
            color: var(--preto);
            letter-spacing: -0.3px;
        }

        .servico-card p.desc-servico {
            font-size: 14.5px;
            line-height: 1.55;
            color: #4a4742;
            margin-bottom: 20px;
            flex-grow: 1;
        }

        /* Informações de Preço e Duração */
        .servico-info-meta {
            width: 100%;
            border-top: 1px solid rgba(214, 181, 110, 0.35);
            padding-top: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .servico-info-meta .meta-preco {
            font-size: 15.5px;
            color: #222;
        }

        .servico-info-meta .meta-preco strong {
            color: var(--dourado);
            font-size: 17px;
        }

        .servico-info-meta .meta-duracao {
            font-size: 13.5px;
            color: #555;
            background: rgba(185, 133, 39, 0.09);
            border: 1px solid rgba(214, 181, 110, 0.3);
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            white-space: nowrap;
        }

        /* Responsividade */
        @media (max-width: 1200px) {
            .servicos-page {
                padding-left: 6%;
                padding-right: 6%;
            }
        }

        @media (max-width: 950px) {
            .intro h1 {
                font-size: 40px;
            }

            .ilustracao {
                width: 380px;
            }
        }

        @media (max-width: 700px) {
            .servicos-page {
                padding: 30px 20px 50px;
            }

            .intro {
                min-height: auto;
                flex-direction: column;
            }

            .intro h1 {
                font-size: 37px;
            }

            .intro p {
                font-size: 14px;
            }

            .ilustracao {
                width: 100%;
                height: 120px;
                transform: scale(0.75);
                transform-origin: center;
                margin-top: 15px;
            }

            .servicos-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        @media (max-width: 430px) {
            .intro h1 {
                font-size: 33px;
            }

            .subtitulo {
                font-size: 12px;
            }
        }
    </style>
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