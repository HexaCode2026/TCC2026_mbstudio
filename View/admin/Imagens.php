<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";
require_once "../../model/Imagem.php";

Session::iniciar();

// Proteção: Apenas Administrador
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] !== 'A') {
    header("Location: ../../Index.php");
    exit;
}

$imagemModel = new Imagem($pdo);
$slidesHero = $imagemModel->listarTodas('hero');
$servicosGaleria = $imagemModel->listarServicosGaleria();

$totalSlides = count($slidesHero);
$slidesAtivos = 0;
foreach ($slidesHero as $sl) {
    if ((int)$sl['Img_active'] === 1) {
        $slidesAtivos++;
    }
}

$servicosComFoto = 0;
foreach ($servicosGaleria as $sg) {
    if (!empty($sg['Ser_image'])) {
        $servicosComFoto++;
    }
}

$msg = $_GET['msg'] ?? '';
$globalCssVer = @filemtime(__DIR__ . '/../../assets/css/global.css') ?: '2.3';
$adminCssVer  = @filemtime(__DIR__ . '/../../assets/css/admin.css') ?: '2.3';
$imgCssVer    = @filemtime(__DIR__ . '/../../assets/css/admin/imagens.css') ?: '2.3';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Imagens | Painel Admin MB Studio</title>

    <link rel="stylesheet" href="../../assets/css/global.css?v=<?= $globalCssVer ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?= $adminCssVer ?>">
    <link rel="stylesheet" href="../../assets/css/admin/imagens.css?v=<?= $imgCssVer ?>">
</head>

<body>
    <?php include '../components/Header.php'; ?>

    <main class="admin-images-wrapper">

        <!-- Banner Principal -->
        <div class="images-header-banner">
            <div>
                <div class="images-badge">
                    <span>✦ Identidade Visual & Vitrine ✦</span>
                </div>
                <h1 class="images-main-title">Gerenciamento de Imagens</h1>
                <p class="images-subtitle">
                    Controle as fotos do <strong>Quadrado de Destaque (Carrossel da Tela Inicial)</strong> e atualize rapidamente as imagens exibidas na <strong>Galeria de Cortes, Serviços e Novidades</strong>.
                </p>
            </div>
            <div>
                <a href="../../Index.php?preview=1" target="_blank" class="btn-outline-sm" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px; padding: 10px 16px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                    Visualizar Tela Inicial
                </a>
            </div>
        </div>

        <!-- Feedback de Ações -->
        <?php if ($msg === 'adicionada'): ?>
            <div class="alert-toast-banner success">✓ Nova imagem adicionada ao carrossel da tela inicial com sucesso!</div>
        <?php elseif ($msg === 'atualizada'): ?>
            <div class="alert-toast-banner success">✓ Imagem do carrossel atualizada com sucesso!</div>
        <?php elseif ($msg === 'status'): ?>
            <div class="alert-toast-banner success">✓ Visibilidade da imagem alterada com sucesso!</div>
        <?php elseif ($msg === 'excluida'): ?>
            <div class="alert-toast-banner success">✓ Imagem removida do carrossel com sucesso!</div>
        <?php elseif ($msg === 'servico_atualizado'): ?>
            <div class="alert-toast-banner success">✓ Foto do serviço atualizada na Galeria e Novidades com sucesso!</div>
        <?php elseif ($msg === 'formato_invalido'): ?>
            <div class="alert-toast-banner error">⚠ Formato de arquivo inválido. Envie imagens JPG, PNG, WEBP ou AVIF.</div>
        <?php elseif ($msg === 'sem_arquivo'): ?>
            <div class="alert-toast-banner error">⚠ Selecione um arquivo de imagem para enviar.</div>
        <?php endif; ?>

        <!-- KPIs Rápidos -->
        <div class="images-kpi-grid">
            <div class="images-kpi-card">
                <div class="images-kpi-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                </div>
                <div>
                    <div class="images-kpi-val"><?= $totalSlides ?></div>
                    <div class="images-kpi-lbl">Total no Carrossel Principal</div>
                </div>
            </div>

            <div class="images-kpi-card">
                <div class="images-kpi-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
                <div>
                    <div class="images-kpi-val"><?= $slidesAtivos ?></div>
                    <div class="images-kpi-lbl">Imagens Ativas no Quadrado Inicial</div>
                </div>
            </div>

            <div class="images-kpi-card">
                <div class="images-kpi-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                </div>
                <div>
                    <div class="images-kpi-val"><?= $servicosComFoto ?> / <?= count($servicosGaleria) ?></div>
                    <div class="images-kpi-lbl">Serviços com Foto na Galeria</div>
                </div>
            </div>
        </div>

        <!-- 1. ADICIONAR NOVA IMAGEM AO QUADRADO PRINCIPAL (CARROSSEL) -->
        <section class="upload-panel-card">
            <h2 class="panel-section-title">
                <span style="color: var(--gold-primary);">✦</span> Adicionar Nova Imagem ao Quadrado Principal
            </h2>
            <p class="panel-section-desc">
                As imagens cadastradas aqui aparecem no quadrado principal da tela inicial, alternando automaticamente a cada poucos segundos e permitindo passar para o lado com as setas.
            </p>

            <form action="../../controller/ImagemController.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="acao" value="adicionar">
                <input type="hidden" name="ativo" value="1">

                <div class="upload-form-grid">
                    <div class="form-field-box">
                        <label>Arquivo da Foto *</label>
                        <input type="file" name="imagem" accept="image/*" required class="input-admin-clean">
                    </div>

                    <div class="form-field-box">
                        <label>Título na Legenda (Ex: Corte Iluminado)</label>
                        <input type="text" name="titulo" placeholder="Ex: Morena Iluminada" required class="input-admin-clean">
                    </div>

                    <div class="form-field-box">
                        <label>Subtítulo / Preço</label>
                        <input type="text" name="subtitulo" placeholder="Ex: R$ 150,00" class="input-admin-clean">
                    </div>

                    <div class="form-field-box" style="max-width: 110px;">
                        <label>Ordem</label>
                        <input type="number" name="ordem" value="<?= $totalSlides + 1 ?>" min="1" class="input-admin-clean">
                    </div>
                </div>

                <div style="margin-top: 18px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn-gold-submit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Adicionar Imagem ao Carrossel
                    </button>
                </div>
            </form>
        </section>

        <!-- 2. LISTA DE IMAGENS DO QUADRADO PRINCIPAL (EDITAR / TROCAR / EXCLUIR) -->
        <section>
            <h2 class="panel-section-title" style="margin-bottom: 6px;">
                <span style="color: var(--gold-primary);">✦</span> Imagens do Quadrado Principal (Carrossel Rotativo)
            </h2>
            <p class="panel-section-desc">
                Modifique a foto, o texto da legenda, a ordem de exibição ou oculte/exclua qualquer slide do quadrado de destaque.
            </p>

            <?php if (!empty($slidesHero)): ?>
                <div class="slides-cards-grid">
                    <?php foreach ($slidesHero as $slide): ?>
                        <?php
                        $isAtivo = ((int)$slide['Img_active'] === 1);
                        $imgRelPath = htmlspecialchars($slide['Img_path']);
                        ?>
                        <div class="slide-manage-card">
                            <div class="slide-preview-box">
                                <span class="slide-status-pill <?= $isAtivo ? 'active' : 'inactive' ?>">
                                    <?= $isAtivo ? 'Ativa' : 'Oculta' ?>
                                </span>
                                <span class="slide-order-badge">Ordem #<?= (int)$slide['Img_order'] ?></span>
                                <img src="../../<?= $imgRelPath ?>" alt="<?= htmlspecialchars($slide['Img_title']) ?>">
                            </div>

                            <div class="slide-manage-body">
                                <form action="../../controller/ImagemController.php" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 10px;">
                                    <input type="hidden" name="acao" value="editar">
                                    <input type="hidden" name="Img_id" value="<?= (int)$slide['Img_id'] ?>">

                                    <div class="form-field-box">
                                        <label>Título da Imagem</label>
                                        <input type="text" name="titulo" value="<?= htmlspecialchars($slide['Img_title']) ?>" required class="input-admin-clean">
                                    </div>

                                    <div class="slide-edit-row">
                                        <div class="form-field-box">
                                            <label>Subtítulo / Preço</label>
                                            <input type="text" name="subtitulo" value="<?= htmlspecialchars($slide['Img_subtitle'] ?? '') ?>" class="input-admin-clean">
                                        </div>
                                        <div class="form-field-box">
                                            <label>Ordem</label>
                                            <input type="number" name="ordem" value="<?= (int)$slide['Img_order'] ?>" min="1" class="input-admin-clean">
                                        </div>
                                    </div>

                                    <div class="form-field-box">
                                        <label>Substituir Foto (Opcional)</label>
                                        <input type="file" name="imagem" accept="image/*" class="input-admin-clean" style="font-size: 12.5px; padding: 7px 10px;">
                                    </div>

                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: var(--text-secondary); cursor: pointer;">
                                        <input type="checkbox" name="ativo" value="1" <?= $isAtivo ? 'checked' : '' ?> style="width: 16px; height: 16px; accent-color: var(--color-primary);">
                                        Visível no Carrossel Inicial
                                    </label>

                                    <button type="submit" class="btn-gold-submit" style="width: 100%; min-height: 40px; font-size: 13px; margin-top: 4px;">
                                        Salvar Alterações
                                    </button>
                                </form>

                                <div class="slide-actions-bar">
                                    <form action="../../controller/ImagemController.php" method="POST" style="margin: 0;">
                                        <input type="hidden" name="acao" value="status">
                                        <input type="hidden" name="Img_id" value="<?= (int)$slide['Img_id'] ?>">
                                        <input type="hidden" name="novo_status" value="<?= $isAtivo ? 0 : 1 ?>">
                                        <button type="submit" class="btn-outline-sm">
                                            <?= $isAtivo ? 'Ocultar Slide' : 'Ativar Slide' ?>
                                        </button>
                                    </form>

                                    <form action="../../controller/ImagemController.php" method="POST" style="margin: 0;" onsubmit="return confirm('Deseja realmente remover esta imagem do carrossel?');">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="Img_id" value="<?= (int)$slide['Img_id'] ?>">
                                        <button type="submit" class="btn-danger-sm">Excluir</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="upload-panel-card" style="text-align: center; padding: 40px;">
                    <p style="color: var(--text-muted); margin: 0;">Nenhuma imagem cadastrada no carrossel principal. Adicione uma foto acima!</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- 3. MODIFICAR IMAGENS DOS SERVIÇOS (GALERIA DE CORTES & NOVIDADES) -->
        <section id="galeria-servicos">
            <h2 class="panel-section-title" style="margin-bottom: 6px;">
                <span style="color: var(--gold-primary);">✦</span> Imagens dos Serviços (Galeria de Cortes & Novidades)
            </h2>
            <p class="panel-section-desc">
                Troque rapidamente a foto de qualquer serviço exibido nos cards da Galeria de Cortes, Novidades e Catálogo de Serviços.
            </p>

            <?php if (!empty($servicosGaleria)): ?>
                <div class="service-images-grid">
                    <?php foreach ($servicosGaleria as $srv): ?>
                        <?php
                        $sImg = $srv['Ser_image'] ?? '';
                        $hasSImg = !empty($sImg) && file_exists(__DIR__ . '/../../' . $sImg);
                        ?>
                        <div class="service-img-card">
                            <div class="service-img-thumb">
                                <?php if ($hasSImg): ?>
                                    <img src="../../<?= htmlspecialchars($sImg) ?>" alt="<?= htmlspecialchars($srv['Ser_name']) ?>">
                                <?php else: ?>
                                    <span style="font-size: 42px; color: var(--color-primary);">✂</span>
                                <?php endif; ?>
                            </div>

                            <div class="service-img-info">
                                <div>
                                    <h3 class="service-img-title"><?= htmlspecialchars($srv['Ser_name']) ?></h3>
                                    <span class="service-img-price">R$ <?= number_format((float)$srv['Ser_price'], 2, ',', '.') ?></span>
                                </div>

                                <form action="../../controller/ImagemController.php" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 8px; margin-top: 4px;">
                                    <input type="hidden" name="acao" value="atualizar_servico">
                                    <input type="hidden" name="Ser_id" value="<?= (int)$srv['Ser_id'] ?>">
                                    <input type="file" name="imagem_servico" accept="image/*" required class="input-admin-clean" style="font-size: 12px; padding: 7px 10px;">
                                    <button type="submit" class="btn-outline-sm" style="width: 100%;">
                                        Atualizar Foto do Serviço
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>
</body>

</html>
