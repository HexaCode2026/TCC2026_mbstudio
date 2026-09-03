<?php
// ============================================================================
// CONTROLLER: Inicialização da Sessão e Controle de Acesso
// ============================================================================
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

// ============================================================================
// MODEL / CONTROLLER: Consulta e Preparação de Dados para a View
// ============================================================================
$servicos = [];
$funcionarios = [];
$novidades = [];

try {
    if (file_exists('config/conexao.php')) {
        require_once 'config/conexao.php';

        if (isset($pdo)) {
            // Buscar serviços ativos
            $stmtServicos = $pdo->query("SELECT * FROM services 
                                         WHERE Ser_active = 1 
                                         ORDER BY Ser_id ASC 
                                         LIMIT 6");
            $servicos = $stmtServicos ? $stmtServicos->fetchAll(PDO::FETCH_ASSOC) : [];

            // Buscar funcionários cadastrados
            $stmtEmp = $pdo->query("SELECT e.Emp_id, u.User_name, e.Emp_photo, e.Emp_specialty, e.Emp_bio 
                                    FROM employees e
                                    JOIN users u ON e.User_id = u.User_id
                                    WHERE u.User_perm = 'F'
                                    ORDER BY u.User_name ASC
                                    LIMIT 4");
            $funcionarios = $stmtEmp ? $stmtEmp->fetchAll(PDO::FETCH_ASSOC) : [];

            // Buscar serviços recentes ativos como novidades
            $stmtServ = $pdo->query("SELECT * FROM services 
                                     WHERE Ser_active = 1 
                                     ORDER BY Ser_id DESC 
                                     LIMIT 3");
            $novidades = $stmtServ ? $stmtServ->fetchAll(PDO::FETCH_ASSOC) : [];
        }
    }
} catch (Exception $e) {
    $servicos = [];
    $funcionarios = [];
    $novidades = [];
}
?>
<!-- =========================================================================
     VIEW: Interface do Usuário (MB Studio Home)
     ========================================================================= -->
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MB Studio | Beleza, Confiança & Elegância</title>

    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <style>
        /* Ajuste do layout editorial da seção Sobre */
        .about-salon-wrapper {
            display: block !important;
            max-width: 900px !important;
            margin: 0 auto !important;
            text-align: center !important;
        }

        /* Formato e dimensionamento dos cards baseado em funcionarios.php mantendo as cores originais */
        .cuts-grid,
        .team-home-grid,
        .news-grid {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 25px !important;
            justify-content: center !important;
            margin-top: 35px !important;
            margin-bottom: 40px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        .cut-item-card,
        .specialist-card,
        .news-card {
            width: 340px !important;
            max-width: 100% !important;
            flex: 0 0 340px !important;
            box-sizing: border-box !important;
        }

        @media (max-width: 400px) {

            .cut-item-card,
            .specialist-card,
            .news-card {
                width: 100% !important;
                flex: 0 0 100% !important;
            }
        }

        .single-card {
            margin-left: auto !important;
            margin-right: auto !important;
        }

        /* Moldura de Foto do Serviço idêntica a servicos.php */
        .servico-icone {
            width: 140px !important;
            height: 140px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: #b98527 !important;
            margin: 0 auto 16px auto !important;
            border-radius: 50% !important;
            overflow: hidden !important;
            border: 2px solid rgba(214, 181, 110, 0.4) !important;
            box-shadow: 0 6px 18px rgba(185, 133, 39, 0.15) !important;
            background: linear-gradient(135deg, #fdfcf9, #f7f1e6) !important;
            flex-shrink: 0 !important;
            transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease !important;
        }

        .cut-item-card:hover .servico-icone,
        .news-card:hover .servico-icone {
            transform: scale(1.05) !important;
            border-color: #d4af37 !important;
            box-shadow: 0 8px 24px rgba(185, 133, 39, 0.25) !important;
        }

        .servico-icone img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            object-position: center !important;
            border-radius: 50% !important;
            display: block !important;
        }

        .icone-tesoura {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 65px;
            line-height: 1;
            transform: rotate(-10deg);
            display: inline-block;
            color: #b98527;
        }

        .news-card {
            position: relative !important;
            padding-top: 28px !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            text-align: center !important;
            box-sizing: border-box !important;
        }

        .news-card .news-badge {
            position: absolute !important;
            top: 14px !important;
            right: 14px !important;
            background: linear-gradient(135deg, #d4af37, #b8860b) !important;
            color: #111 !important;
            font-size: 11px !important;
            font-weight: 800 !important;
            letter-spacing: 1px !important;
            text-transform: uppercase !important;
            padding: 5px 12px !important;
            border-radius: 20px !important;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.35) !important;
            z-index: 10 !important;
        }
    </style>
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
         2. SOBRE O SALÃO & O ESPAÇO (TEXTO EDITÁVEL)
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
                            <h4><?= htmlspecialchars($s['Ser_name']) ?></h4>
                            <p class="cut-desc">
                                <?= htmlspecialchars($s['Ser_description'] ?: 'Serviço exclusivo MB Studio com produtos de alta performance.') ?>
                            </p>
                            <div style="margin-bottom: 12px; font-weight: 700; color: var(--gold-dark); font-size: 14px;">
                                R$ <?= number_format($s['Ser_price'], 2, ',', '.') ?>
                                <?= !empty($s['Ser_duration']) ? ' <span style="font-weight: normal; color: #888; font-size: 12px;">(' . $s['Ser_duration'] . ' min)</span>' : '' ?>
                            </div>
                            <a href="#" onclick="checkAuthAndExecute(event, 'View/cliente/Data.php?Ser_id=<?= $s['Ser_id'] ?>')"
                                class="btn-outline-gold">Agendar</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color:#888; width: 100%; text-align: center; margin: 30px 0; font-style: italic;">No momento, não
                    temos serviços cadastrados.</p>
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
                </div>
            <?php else: ?>
                <p style="color:#aaa; width: 100%; text-align: center; margin: 30px 0; font-style: italic;">No momento, não
                    temos profissionais cadastrados.</p>
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
                                        class="btn-gold" style="padding: 8px 18px; font-size: 12px;">
                                        Agendar
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color:#666; width: 100%; text-align: center; margin: 30px 0; font-style: italic;">No momento, não
                    há novidades cadastradas.</p>
            <?php endif; ?>
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