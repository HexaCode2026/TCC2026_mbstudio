<?php
require_once 'core/Session.php';
Session::iniciar();

if (isset($_SESSION['User_perm'])) {
    if ($_SESSION['User_perm'] === 'A') {
        header("Location: View/admin/Home.php");
        exit;
    } elseif ($_SESSION['User_perm'] === 'F') {
        header("Location: View/funcionario/Home.php");
        exit;
    }
}

// Conexão segura para buscar equipe e novidades cadastradas no banco (com fallback)
$funcionarios = [];
$novidades = [];

try {
    if (file_exists('config/conexao.php')) {
        require_once 'config/conexao.php';

        if (isset($pdo)) {
            // Buscar funcionários cadastrados
            $stmtEmp = $pdo->query("SELECT e.Emp_id, u.User_name, e.Emp_photo, e.Emp_specialty, e.Emp_bio 
                                    FROM employees e
                                    JOIN users u ON e.User_id = u.User_id
                                    WHERE u.User_perm = 'F'
                                    ORDER BY u.User_name ASC
                                    LIMIT 4");
            $funcionarios = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);

            // Buscar serviços recentes ativos como novidades
            $stmtServ = $pdo->query("SELECT * FROM services 
                                     WHERE Ser_active = 1 
                                     ORDER BY Ser_id DESC 
                                     LIMIT 3");
            $novidades = $stmtServ->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    // Caso o banco ainda não esteja conectado ou tabelas vazias, fallback visual será exibido
    $funcionarios = [];
    $novidades = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MB Studio | Beleza, Confiança & Elegância</title>

    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/home.css">
</head>

<body>
    <!-- Cabeçalho Principal Global -->
    <?php include 'View/components/Header.php'; ?>

    <!-- =================================================================
         1. HERO SECTION (APRESENTAÇÃO PRINCIPAL & QUADRADO DE DESTAQUE)
         ================================================================= -->
    <section class="hero-luxury">
        <div class="hero-wrapper">

            <div class="hero-content">
                <div class="hero-badge-tag">
                    <span class="dot"></span>
                    <span>Beleza | Confiança | <span class="cursiva"
                            style="text-transform: capitalize; font-size: 14px;">Elegância</span></span>
                </div>

                <h1 class="hero-title">
                    Realçando sua <span class="text-gold">Beleza</span> com
                    <span class="cursiva-gold">Sofisticação</span>
                </h1>

                <p class="hero-description">
                    Serviços de alto padrão, visagismo e atendimento personalizado em um ambiente acolhedor e moderno
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

            <!-- Quadrado de Destaque / Espaço Reservado para Imagens e Cortes -->
            <div class="hero-visual-frame">
                <div class="cuts-main-placeholder">
                    <div class="cuts-placeholder-icon">
                        <!-- Ícone de Tesoura / Studio de Beleza -->
                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="6" cy="6" r="3"></circle>
                            <circle cx="6" cy="18" r="3"></circle>
                            <line x1="20" y1="4" x2="8.12" y2="15.88"></line>
                            <line x1="14.47" y1="14.48" x2="20" y2="20"></line>
                            <line x1="8.12" y1="8.12" x2="12" y2="12"></line>
                        </svg>
                    </div>

                    <div class="cuts-placeholder-content">
                        <span class="section-tag" style="margin-bottom: 8px;">Destaque do Studio</span>
                        <h3>Espaço Reservado para Fotos dos <span class="cursiva-gold">Cortes</span></h3>
                        <p>
                            Área preparada para exibição das fotos de cortes, transformações e tendências exclusivas
                            realizadas pelos nossos profissionais.
                        </p>
                    </div>

                    <a href="#" onclick="checkAuthAndExecute(event, 'View/cliente/Servicos.php')"
                        class="btn-outline-gold">
                        Ver Transformações
                    </a>

                    <div class="cuts-floating-badge">
                        <div class="badge-icon">✨</div>
                        <div class="badge-text">
                            <strong>MB Studio</strong>
                            <span>Excelência em Cada Detalhe</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- =================================================================
         2. SOBRE O SALÃO & O ESPAÇO (TEXTO GENÉRICO EDITÁVEL)
         ================================================================= -->
    <section class="about-salon-section">
        <div class="about-salon-wrapper">

            <div class="about-salon-text">
                <span class="section-tag">Conceito & Filosofia</span>
                <h2 class="section-title">
                    Um refúgio de <span class="cursiva-gold">beleza</span>, conforto e autenticidade
                </h2>
                <div class="divider-gold" style="margin: 10px 0 25px 0;"></div>

                <p class="lead-text">
                    "Acreditamos que a beleza não segue padrões pré-definidos: ela nasce da sua identidade única e do
                    cuidado em realçar o que você tem de melhor."
                </p>

                <p class="generic-text">
                    No <strong>MB Studio</strong>, cada detalhe foi cuidadosamente planejado para oferecer uma
                    experiência sensorial e relaxante. Desde a consultoria personalizada de visagismo até a aplicação de
                    produtos de alta performance, nosso compromisso é entregar resultados impecáveis com saúde capilar e
                    bem-estar.
                </p>

                <p class="generic-text">
                    Nosso ambiente moderno e sofisticado conta com estações individuais, iluminação pensada para a
                    precisão das cores e um atendimento acolhedor para que o seu momento de autocuidado seja
                    inesquecível.
                </p>

                <div class="about-highlights-grid">
                    <div class="highlight-box">
                        <div class="highlight-box-icon">✦</div>
                        <h4>Atendimento Consultivo</h4>
                        <p>Análise de perfil, visagismo e harmonia para o corte e cor ideais.</p>
                    </div>
                    <div class="highlight-box">
                        <div class="highlight-box-icon">✦</div>
                        <h4>Cosméticos de Qualidade</h4>
                        <p>Utilizamos apenas marcas de alta qualidade para o melhor resultado.</p>
                    </div>
                </div>
            </div>

            <div class="about-space-visual">
                <div class="about-space-card">
                    <div class="space-card-header">
                        <h3>O Nosso <span class="gold">Espaço</span></h3>
                        <span>Ambiente planejado para o seu conforto</span>
                    </div>

                    <ul class="space-features-list">
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            Lavatórios ergonômicos com massagem e relaxamento
                        </li>
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            Estações exclusivas para mechas, colorimetria e tratamentos
                        </li>
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            Espaço café gourmet e drinks selecionados
                        </li>
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            Ambiente climatizado com trilha sonora relaxante
                        </li>
                    </ul>

                    <a href="#" onclick="checkAuthAndExecute(event, 'View/cliente/Servicos.php')" class="btn-gold"
                        style="width: 100%; text-align: center;">
                        Viva Esta Experiência
                    </a>
                </div>
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

            <div class="cuts-grid">
                <!-- Card 1 -->
                <div class="cut-item-card">
                    <div class="cut-photo-box">
                        <span class="tag-cat">Cortes</span>
                        <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <p>Foto do Corte Feminino</p>
                    </div>
                    <h4>Corte & <span class="cursiva-gold">Visagismo</span></h4>
                    <p class="cut-desc">Cortes modernos personalizados para valorizar os traços do seu rosto.</p>
                    <a href="#" onclick="checkAuthAndExecute(event, 'View/cliente/Servicos.php')"
                        class="btn-outline-gold">Agendar</a>
                </div>

                <!-- Card 2 -->
                <div class="cut-item-card">
                    <div class="cut-photo-box">
                        <span class="tag-cat">Coloração</span>
                        <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <p>Foto Iluminação / Blond</p>
                    </div>
                    <h4>Loiros & <span class="cursiva-gold">Iluminação</span></h4>
                    <p class="cut-desc">Técnicas sutis de mechas preservando a saúde e brilho dos fios.</p>
                    <a href="#" onclick="checkAuthAndExecute(event, 'View/cliente/Servicos.php')"
                        class="btn-outline-gold">Agendar</a>
                </div>

                <!-- Card 3 -->
                <div class="cut-item-card">
                    <div class="cut-photo-box">
                        <span class="tag-cat">Tratamentos</span>
                        <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <p>Foto Terapia Capilar</p>
                    </div>
                    <h4>Terapia & <span class="cursiva-gold">Spa Capilar</span></h4>
                    <p class="cut-desc">Recuperação profunda, hidratação molecular e nutrição intensiva.</p>
                    <a href="#" onclick="checkAuthAndExecute(event, 'View/cliente/Servicos.php')"
                        class="btn-outline-gold">Agendar</a>
                </div>

                <!-- Card 4 -->
                <div class="cut-item-card">
                    <div class="cut-photo-box">
                        <span class="tag-cat">Produção</span>
                        <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <p>Foto Penteados / Noivas</p>
                    </div>
                    <h4>Penteados & <span class="cursiva-gold">Eventos</span></h4>
                    <p class="cut-desc">Produções elegantes e marcantes para ocasiões inesquecíveis.</p>
                    <a href="#" onclick="checkAuthAndExecute(event, 'View/cliente/Servicos.php')"
                        class="btn-outline-gold">Agendar</a>
                </div>
            </div>
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

            <div class="team-home-grid">
                <?php if (!empty($funcionarios)): ?>
                    <?php foreach ($funcionarios as $f): ?>
                        <div class="specialist-card">
                            <div class="specialist-avatar">
                                <?php if (!empty($f['Emp_photo']) && file_exists($f['Emp_photo'])): ?>
                                    <img src="<?= htmlspecialchars($f['Emp_photo']) ?>"
                                        alt="<?= htmlspecialchars($f['User_name']) ?>">
                                <?php else: ?>
                                    <div class="specialist-avatar-placeholder">
                                        <?= strtoupper(substr($f['User_name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h3 class="specialist-name"><?= htmlspecialchars($f['User_name']) ?></h3>
                            <p class="specialist-role"><?= htmlspecialchars($f['Emp_specialty'] ?: 'Especialista da Beleza') ?>
                            </p>
                            <?php if (!empty($f['Emp_bio'])): ?>
                                <p class="specialist-bio">"<?= htmlspecialchars($f['Emp_bio']) ?>"</p>
                            <?php else: ?>
                                <p class="specialist-bio">"Dedicada a realçar a sua melhor versão com técnica e sensibilidade."</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback de alto padrão caso não haja equipe cadastrada no banco -->
                    <div class="specialist-card">
                        <div class="specialist-avatar">
                            <div class="specialist-avatar-placeholder">M</div>
                        </div>
                        <h3 class="specialist-name">Mariana Bastos</h3>
                        <p class="specialist-role">Master Hair Stylist & Visagista</p>
                        <p class="specialist-bio">"Especialista em cortes contemporâneos e consultoria de imagem
                            personalizada."</p>
                    </div>

                    <div class="specialist-card">
                        <div class="specialist-avatar">
                            <div class="specialist-avatar-placeholder">C</div>
                        </div>
                        <h3 class="specialist-name">Carlos Eduardo</h3>
                        <p class="specialist-role">Colorista & Blonde Expert</p>
                        <p class="specialist-bio">"Referência em mechas iluminadas, transições suaves e preservação da fibra
                            capilar."</p>
                    </div>

                    <div class="specialist-card">
                        <div class="specialist-avatar">
                            <div class="specialist-avatar-placeholder">L</div>
                        </div>
                        <h3 class="specialist-name">Letícia Andrade</h3>
                        <p class="specialist-role">Terapeuta Capilar & Make</p>
                        <p class="specialist-bio">"Foco na saúde do couro cabeludo, rituais de spa e produções refinadas
                            para eventos."</p>
                    </div>
                <?php endif; ?>
            </div>

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

            <div class="news-grid">
                <?php if (!empty($novidades)): ?>
                    <?php foreach ($novidades as $n): ?>
                        <div class="news-card">
                            <div class="news-image-wrap">
                                <?php if (!empty($n['Ser_image']) && file_exists($n['Ser_image'])): ?>
                                    <img src="<?= htmlspecialchars($n['Ser_image']) ?>"
                                        alt="<?= htmlspecialchars($n['Ser_name']) ?>">
                                <?php else: ?>
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d4af37" stroke-width="1.5">
                                        <polygon
                                            points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2">
                                        </polygon>
                                    </svg>
                                <?php endif; ?>
                                <span class="news-badge">Novidade</span>
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
                                        class="btn-gold" style="padding: 8px 18px; font-size: 12px;">
                                        Agendar
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback de cards de novidades -->
                    <div class="news-card">
                        <div class="news-image-wrap">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d4af37" stroke-width="1.5">
                                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            <span class="news-badge">Lançamento</span>
                        </div>
                        <div class="news-body">
                            <h3 class="news-title">Spa Capilar & Ozonioterapia</h3>
                            <p class="news-description">Protocolo avançado de desintoxicação do couro cabeludo, estimulação
                                de crescimento e hidratação profunda com vapor de ozônio.</p>
                            <div class="news-footer">
                                <div class="news-price-wrap">
                                    <span>A partir de</span>
                                    <strong>R$ 180,00</strong>
                                </div>
                                <a href="#" onclick="checkAuthAndExecute(event, 'View/cliente/Servicos.php')"
                                    class="btn-gold" style="padding: 8px 18px; font-size: 12px;">
                                    Agendar
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="news-card">
                        <div class="news-image-wrap">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d4af37" stroke-width="1.5">
                                <circle cx="12" cy="12" r="5"></circle>
                                <line x1="12" y1="1" x2="12" y2="3"></line>
                                <line x1="12" y1="21" x2="12" y2="23"></line>
                                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                                <line x1="1" y1="12" x2="3" y2="12"></line>
                                <line x1="21" y1="12" x2="23" y2="12"></line>
                                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                            </svg>
                            <span class="news-badge">Tendência</span>
                        </div>
                        <div class="news-body">
                            <h3 class="news-title">Iluminação Express SunKiss</h3>
                            <p class="news-description">Pontos de luz estratégicos com efeito queimado de sol,
                                proporcionando luminosidade natural com baixa manutenção.</p>
                            <div class="news-footer">
                                <div class="news-price-wrap">
                                    <span>A partir de</span>
                                    <strong>R$ 290,00</strong>
                                </div>
                                <a href="#" onclick="checkAuthAndExecute(event, 'View/cliente/Servicos.php')"
                                    class="btn-gold" style="padding: 8px 18px; font-size: 12px;">
                                    Agendar
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="news-card">
                        <div class="news-image-wrap">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d4af37" stroke-width="1.5">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                            <span class="news-badge">Exclusivo</span>
                        </div>
                        <div class="news-body">
                            <h3 class="news-title">Blindagem Molecular de Fios</h3>
                            <p class="news-description">Tratamento anti-frizz e anti-porosidade de alta fixação que sela as
                                cutículas conferindo brilho espelhado prolongado.</p>
                            <div class="news-footer">
                                <div class="news-price-wrap">
                                    <span>A partir de</span>
                                    <strong>R$ 220,00</strong>
                                </div>
                                <a href="#" onclick="checkAuthAndExecute(event, 'View/cliente/Servicos.php')"
                                    class="btn-gold" style="padding: 8px 18px; font-size: 12px;">
                                    Agendar
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- =================================================================
         6. BANNER DE AGENDAMENTO RÁPIDO (CTA LUXURY)
         ================================================================= -->
    <section class="luxury-cta-banner">
        <div class="cta-banner-content">
            <span class="section-tag" style="color: var(--gold-light);">Viva a Experiência</span>
            <h2 class="cta-banner-title">
                Pronta para realçar sua melhor <span class="cursiva-gold">versão</span>?
            </h2>
            <p class="cta-banner-desc">
                Agende online seu atendimento com antecedência, escolha seus serviços favoritos e o profissional da sua
                preferência.
            </p>
            <a href="#" onclick="checkAuthAndExecute(event, 'View/cliente/Servicos.php')" class="btn-gold"
                style="font-size: 15px; padding: 16px 42px;">
                AGENDE SEU HORÁRIO AGORA
            </a>
        </div>
    </section>

    <!-- =================================================================
         7. RODAPÉ DE ALTO PADRÃO (FOOTER)
         ================================================================= -->
    <footer class="salon-footer" id="contato">
        <div class="footer-grid">
            <div class="footer-col">
                <h4 style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: var(--gold-primary); font-size: 20px;">✦</span> MB STUDIO
                </h4>
                <p>
                    Referência em sofisticação, visagismo e cuidados com a beleza feminina. Um espaço feito para
                    valorizar a sua autoestima com excelência e carinho.
                </p>
                <div class="footer-social-links">
                    <a href="https://www.instagram.com/maison.bellestudio/" target="_blank" class="social-icon-btn"
                        title="Instagram">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                    </a>
                    <a href="https://wa.me/5511991075433?text=Olá, gostaria de agendar um horário" target="_blank"
                        class="social-icon-btn" title="WhatsApp">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z">
                            </path>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="footer-col">
                <h4>Navegação</h4>
                <ul class="footer-links">
                    <li><a href="Index.php">Início</a></li>
                    <li><a href="View/cliente/Servicos.php">Nossos Serviços</a></li>
                    <li><a href="View/cliente/Funcionarios.php">Nossa Equipe</a></li>
                    <li><a href="#" onclick="checkAuthAndExecute(event, 'View/cliente/Servicos.php')">Agendamento
                            Online</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Horário de Funcionamento</h4>
                <p style="margin-bottom: 6px;"><strong>Terça a Sexta:</strong></p>
                <p style="color: var(--gold-light); margin-bottom: 12px;">09h00 às 19h00</p>
                <p style="margin-bottom: 6px;"><strong>Sábados:</strong></p>
                <p style="color: var(--gold-light);">08h30 às 18h00</p>
            </div>

            <div class="footer-col">
                <h4>Localização</h4>
                <p>
                    Rua Mangaratiba, 449 - Vila Santo Antônio<br>
                    São Paulo - SP
                </p>
                <p style="color: var(--gold-light);">
                    (11) 99107-5433<br>
                    cidapedroso@terra.com.br
                </p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> MB Studio. Todos os direitos reservados.</p>
            <p>Elegância e Beleza em Cada Detalhe.</p>
        </div>
    </footer>

    <!-- Modal Global de Login / Cadastro -->
    <?php include 'View/components/LoginModal.php'; ?>
</body>

</html>