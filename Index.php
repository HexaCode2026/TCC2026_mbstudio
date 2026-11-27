<?php
// ============================================================================
// CONTROLLER / MODEL: Inicialização de Sessão e Carregamento via MVC
// ============================================================================
require_once __DIR__ . '/controller/HomeController.php';

$homeController = new HomeController($pdo ?? null);
$homeController->verificarAcesso();

$dadosHome    = $homeController->carregarDadosHome(__DIR__);
$servicos     = $dadosHome['servicos'];
$funcionarios = $dadosHome['funcionarios'];
$novidades    = $dadosHome['novidades'];
$destaques    = $dadosHome['destaques'];

$globalCssVer = @filemtime(__DIR__ . '/assets/css/global.css') ?: '2.5';
$homeCssVer   = @filemtime(__DIR__ . '/assets/css/home.css') ?: '2.5';
$transCssVer  = @filemtime(__DIR__ . '/assets/css/cliente-transitions.css') ?: '2.5';
?>
<!-- =========================================================================
     VIEW: Interface do Usuário (MB Studio Home)
     ========================================================================= -->
<!DOCTYPE html>
<html lang="pt-BR" class="page-index">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MB Studio | Beleza, Confiança & Elegância</title>

    <script>document.documentElement.classList.add('cliente-transitions-enabled');</script>
    <link rel="stylesheet" href="assets/css/global.css?v=<?= $globalCssVer ?>">
    <link rel="stylesheet" href="assets/css/home.css?v=<?= $homeCssVer ?>" id="theme-home-css">
</head>

<body class="cliente-page page-index-body">
    <!-- Cabeçalho Principal Global -->
    <?php include 'View/components/Header.php'; ?>

    <!-- Conteúdo da Página abaixo do Cabeçalho -->
    <div class="page-content-wrapper" id="page-content">
        <main>
            <?php include __DIR__ . '/View/components/FlashMessage.php'; ?>

        <!-- =================================================================
         1. HERO SECTION (APRESENTAÇÃO PRINCIPAL & QUADRADO DE DESTAQUE)
         ================================================================= -->
        <section class="hero-luxury">
            <div class="hero-wrapper">

                <div class="hero-content">
                    <div class="hero-badge-tag">
                        <span class="dot" aria-hidden="true"></span>
                        <span>Beleza | Confiança | <span class="cursiva hero-badge-cursiva">Elegância</span></span>
                    </div>

                    <h1 class="hero-title">
                        Realçando sua <span class="text-gold">Beleza</span> com
                        <span class="cursiva-gold">Sofisticação</span>
                    </h1>

                    <p class="hero-description">
                        Serviços de alto padrão, visagismo e atendimento personalizado em um ambiente acolhedor e
                        moderno
                        para transformar a sua experiência de beleza e bem-estar no dia a dia.
                    </p>

                    <div class="hero-cta-group">
                        <a href="#" onclick="checkAuthAndExecute(event, 'View/cliente/Servicos.php')" class="btn-gold">
                            Agendar Horário
                        </a>
                        <a href="View/cliente/Servicos.php" class="btn-outline">
                            Conhecer Serviços
                        </a>
                    </div>
                </div>

                <!-- Quadrado de Destaque / Slideshow de Cortes em Destaque -->
                <div class="hero-visual-frame">
                    <div class="cuts-main-placeholder" id="hero-slideshow">

                        <?php if (!empty($destaques)): ?>
                            <?php foreach ($destaques as $idx => $dest): ?>
                                <div class="hero-slide<?= $idx === 0 ? ' active' : '' ?>"
                                    data-name="<?= htmlspecialchars($dest['title']) ?>"
                                    data-price="<?= htmlspecialchars($dest['subtitle']) ?>">
                                    <img src="<?= htmlspecialchars($dest['image']) ?>"
                                        alt="<?= htmlspecialchars($dest['title']) ?>" class="hero-slide-img" loading="eager">
                                </div>
                            <?php endforeach; ?>

                            <?php if (count($destaques) > 1): ?>
                                <!-- Botões de passar para o lado (Anterior / Próxima) -->
                                <button type="button" class="slideshow-arrow slideshow-prev" id="heroSlidePrev" onclick="if(window.heroSlideStep) window.heroSlideStep(-1, event);" aria-label="Imagem anterior" title="Imagem anterior">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                </button>
                                <button type="button" class="slideshow-arrow slideshow-next" id="heroSlideNext" onclick="if(window.heroSlideStep) window.heroSlideStep(1, event);" aria-label="Próxima imagem" title="Próxima imagem">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                </button>
                            <?php endif; ?>

                            <!-- Overlay com Nome e Preço do slide atual -->
                            <div class="slide-info-overlay">
                                <span class="slide-label-name"><?= htmlspecialchars($destaques[0]['title']) ?></span>
                                <span class="slide-label-price"><?= htmlspecialchars($destaques[0]['subtitle']) ?></span>
                            </div>

                            <!-- Indicadores de navegação (dots) -->
                            <div class="slideshow-dots">
                                <?php foreach ($destaques as $idx => $_d): ?>
                                    <button type="button" class="slideshow-dot<?= $idx === 0 ? ' active' : '' ?>" onclick="if(window.heroSlideGoTo) window.heroSlideGoTo(<?= $idx ?>, event);" aria-label="Ir para imagem <?= $idx + 1 ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <!-- Fallback quando não há imagens cadastradas -->
                            <div class="cuts-placeholder-content" style="margin: auto;">
                                <h3 style="color: #fff; font-family: var(--font-heading); font-size: 20px;">✂ MB Studio</h3>
                                <p style="color: #a0a0a8; font-size: 13px;">Beleza, Confiança & Elegância</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </section>

        <!-- =================================================================
         2. SOBRE O SALÃO & O ESPAÇO (TEXTO EDITÁVEL)
         ================================================================= -->
        <section class="about-salon-section">
            <div class="about-salon-wrapper">

                <div class="about-salon-text">
                    <span class="section-tag">Conceito & Filosofia</span>
                    <h2 class="section-title">
                        Um refúgio de <span class="cursiva-gold">beleza</span>, conforto e autenticidade
                    </h2>
                    <div class="divider-gold"></div>

                    <p class="lead-text">
                        "Acreditamos que a beleza não segue padrões pré-definidos: ela nasce da sua identidade única e
                        do
                        cuidado em realçar o que você tem de melhor."
                    </p>

                    <p class="generic-text">
                        No <strong>MB Studio</strong>, cada detalhe foi cuidadosamente planejado para oferecer uma
                        experiência sensorial e relaxante. Desde a consultoria personalizada de visagismo até a
                        aplicação de
                        produtos de alta performance, nosso compromisso é entregar resultados impecáveis com saúde
                        capilar e
                        bem-estar.
                    </p>

                    <p class="generic-text">
                        Nosso ambiente moderno e sofisticado conta com estações individuais, iluminação pensada para a
                        precisão das cores e um atendimento acolhedor para que o seu momento de autocuidado seja
                        inesquecível.
                    </p>
                </div>

            </div>
        </section>

        <!-- =================================================================
         3. GALERIA & QUADRADO DE CORTES / TRANSFORMAÇÕES
         ================================================================= -->
        <section class="cuts-gallery-section">
            <div class="cuts-gallery-wrapper">
                <span class="section-tag">Portfólio & Visagismo</span>
                <h2 class="section-title">
                    Galeria de <span class="cursiva-gold">Cortes</span> & Estilos
                </h2>
                <div class="divider-gold"></div>
                <p class="section-subtitle">
                    Confira uma prévia dos estilos, cortes geométricos, morenas iluminadas e tratamentos mais pedidos no
                    salão.
                </p>

                <?php if (!empty($servicos)): ?>
                    <div class="cuts-grid">
                        <?php foreach ($servicos as $s): ?>
                            <div class="cut-item-card <?= count($servicos) === 1 ? 'single-card' : '' ?>">
                                <div class="servico-icone">
                                    <?php
                                    $sPhoto = $s['Ser_image'] ?? '';
                                    $hasSPhoto = !empty($sPhoto) && (file_exists($sPhoto) || file_exists(__DIR__ . '/' . $sPhoto));
                                    ?>
                                    <?php if ($hasSPhoto): ?>
                                        <img src="<?= htmlspecialchars($sPhoto) ?>" alt="<?= htmlspecialchars($s['Ser_name']) ?>">
                                    <?php else: ?>
                                        <span class="icone-tesoura">✂</span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="cut-title"><?= htmlspecialchars($s['Ser_name']) ?></h3>
                                <p class="cut-desc">
                                    <?= htmlspecialchars($s['Ser_description'] ?: 'Serviço exclusivo MB Studio com produtos de alta performance.') ?>
                                </p>
                                <div class="cut-card-footer">
                                    <div class="cut-meta-price">
                                        <span>R$ <?= number_format($s['Ser_price'], 2, ',', '.') ?></span>
                                        <?php if ((int)($s['Ser_duration'] ?? 0) > 0): ?>
                                            <span class="cut-duration">(<?= (int)$s['Ser_duration'] ?> min)</span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="#"
                                        onclick="checkAuthAndExecute(event, 'View/cliente/Data.php?Ser_id=<?= $s['Ser_id'] ?>')"
                                        class="btn-outline-gold">Agendar</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-card">
                        <div class="empty-state-icon" aria-hidden="true">✂</div>
                        <h3 class="empty-state-title">Catálogo em preparação</h3>
                        <p class="empty-state-desc">No momento, não temos serviços cadastrados para exibição.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- =================================================================
         4. NOSSA EQUIPE (ESPECIALISTAS)
         ================================================================= -->
        <section class="team-home-section">
            <div class="team-home-wrapper">
                <span class="section-tag">Talento & Dedicação</span>
                <h2 class="section-title title-light">
                    Nossos <span class="cursiva-gold">Especialistas</span>
                </h2>
                <div class="divider-gold"></div>
                <p class="section-subtitle sub-light">
                    Conheça os profissionais apaixonados que tornam cada visita ao MB Studio uma experiência singular.
                </p>

                <?php if (!empty($funcionarios)): ?>
                    <div class="team-home-grid">
                        <?php foreach ($funcionarios as $f): ?>
                            <div class="specialist-card <?= count($funcionarios) === 1 ? 'single-card' : '' ?>">
                                <div class="specialist-avatar">
                                    <?php
                                    $fPhoto = $f['Emp_photo'] ?? '';
                                    $hasFPhoto = !empty($fPhoto) && (file_exists($fPhoto) || file_exists(__DIR__ . '/' . $fPhoto));
                                    ?>
                                    <?php if ($hasFPhoto): ?>
                                        <img src="<?= htmlspecialchars($fPhoto) ?>" alt="<?= htmlspecialchars($f['User_name']) ?>">
                                    <?php else: ?>
                                        <div class="specialist-avatar-placeholder">
                                            <?= htmlspecialchars(mb_strtoupper(mb_substr(trim($f['User_name']), 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <h3 class="specialist-name"><?= htmlspecialchars($f['User_name']) ?></h3>
                                <p class="specialist-role">
                                    <?= htmlspecialchars($f['Emp_specialty'] ?: 'Especialista da Beleza') ?>
                                </p>
                                <?php if (!empty($f['Emp_bio'])): ?>
                                    <p class="specialist-bio">"<?= htmlspecialchars($f['Emp_bio']) ?>"</p>
                                <?php else: ?>
                                    <p class="specialist-bio">"Dedicada a realçar a sua melhor versão com técnica e sensibilidade."
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-card empty-state-card--dark">
                        <div class="empty-state-icon" aria-hidden="true">✦</div>
                        <h3 class="empty-state-title">Equipe em atualização</h3>
                        <p class="empty-state-desc">No momento, não temos profissionais cadastrados para exibição.</p>
                    </div>
                <?php endif; ?>

                <a href="View/cliente/Funcionarios.php" class="btn-gold">
                    Conhecer Toda a Equipe
                </a>
            </div>
        </section>

        <!-- =================================================================
         5. SEÇÃO "NOSSAS NOVIDADES"
         ================================================================= -->
        <section class="news-section">
            <div class="news-wrapper">
                <span class="section-tag">Lançamentos & Tendências</span>
                <h2 class="section-title">
                    Nossas <span class="cursiva-gold">Novidades</span>
                </h2>
                <div class="divider-gold"></div>
                <p class="section-subtitle">
                    Fique por dentro dos novos tratamentos, protocolos inovadores e rituais exclusivos recém-chegados ao
                    nosso estúdio.
                </p>

                <?php if (!empty($novidades)): ?>
                    <div class="news-grid">
                        <?php foreach ($novidades as $n): ?>
                            <div class="news-card <?= count($novidades) === 1 ? 'single-card' : '' ?>">
                                <span class="news-badge">Novidade</span>

                                <div class="servico-icone">
                                    <?php
                                    $nPhoto = $n['Ser_image'] ?? '';
                                    $hasNPhoto = !empty($nPhoto) && (file_exists($nPhoto) || file_exists(__DIR__ . '/' . $nPhoto));
                                    ?>
                                    <?php if ($hasNPhoto): ?>
                                        <img src="<?= htmlspecialchars($nPhoto) ?>" alt="<?= htmlspecialchars($n['Ser_name']) ?>">
                                    <?php else: ?>
                                        <span class="icone-tesoura">✂</span>
                                    <?php endif; ?>
                                </div>
                                <div class="news-body">
                                    <h3 class="news-title"><?= htmlspecialchars($n['Ser_name']) ?></h3>
                                    <p class="news-description">
                                        <?= htmlspecialchars($n['Ser_description'] ?: 'Protocolo exclusivo para renovar a saúde e o visual dos seus cabelos.') ?>
                                    </p>
                                    <div class="news-footer">
                                        <div class="news-price-wrap">
                                            <span>A partir de</span>
                                            <strong>R$ <?= number_format($n['Ser_price'], 2, ',', '.') ?></strong>
                                        </div>
                                        <a href="#"
                                            onclick="checkAuthAndExecute(event, 'View/cliente/Data.php?Ser_id=<?= $n['Ser_id'] ?>')"
                                            class="btn-gold news-cta-btn">
                                            Agendar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-card">
                        <div class="empty-state-icon" aria-hidden="true">✦</div>
                        <h3 class="empty-state-title">Em breve novidades</h3>
                        <p class="empty-state-desc">No momento, não há lançamentos cadastrados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- =================================================================
         6. BANNER DE AGENDAMENTO RÁPIDO (CTA LUXURY)
         ================================================================= -->
        <section class="luxury-cta-banner">
            <div class="cta-banner-content">
                <span class="section-tag">Viva a Experiência</span>
                <h2 class="cta-banner-title">
                    Pronta para realçar sua melhor <span class="cursiva-gold">versão</span>?
                </h2>
                <p class="cta-banner-desc">
                    Agende online seu atendimento com antecedência, escolha seus serviços favoritos e o profissional da
                    sua
                    preferência.
                </p>
                <a href="#" onclick="checkAuthAndExecute(event, 'View/cliente/Servicos.php')" class="btn-gold cta-banner-btn">
                    AGENDE SEU HORÁRIO AGORA
                </a>
            </div>
        </section>

        </main>

        <?php include __DIR__ . '/View/components/Footer.php'; ?>
    </div><!-- /.page-content-wrapper -->

    <!-- Modal Global de Login / Cadastro -->
    <?php include 'View/components/LoginModal.php'; ?>
</body>

</html>