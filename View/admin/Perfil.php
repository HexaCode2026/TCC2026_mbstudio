<?php
require_once __DIR__ . "/../../middleware/AdminAuth.php";
require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../model/User.php";

$basePath = Session::getBasePath();
$userId   = (int)($_SESSION['User_id'] ?? 0);

if (!isset($dadosAdmin)) {
    $userModel  = new User($pdo);
    $dadosAdmin = $userModel->findById($userId);
}

if (!$dadosAdmin || ($dadosAdmin['User_perm'] ?? '') !== 'A') {
    header("Location: " . $basePath . "/Index.php");
    exit;
}

$erroFlash    = $erroFlash ?? Session::getFlash('error');
$sucessoFlash = $sucessoFlash ?? Session::getFlash('success');

$globalCssVer = @filemtime(__DIR__ . '/../../assets/css/global.css') ?: '3.0';
$adminCssVer  = @filemtime(__DIR__ . '/../../assets/css/admin.css') ?: '3.0';
$perfilCssVer = @filemtime(__DIR__ . '/../../assets/css/admin/perfil.css') ?: '1.0';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil Administrativo | MB Studio</title>

    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/global.css?v=<?= $globalCssVer ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/admin.css?v=<?= $adminCssVer ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/admin/perfil.css?v=<?= $perfilCssVer ?>">
</head>
<body>

    <!-- Cabeçalho Oficial MB Studio -->
    <?php include __DIR__ . '/../components/Header.php'; ?>

    <main class="admin-profile-wrapper">

        <!-- Barra Superior de Navegação -->
        <div class="top-nav-bar">
            <a href="<?= $basePath ?>/View/admin/Dashboard.php" class="btn-back-dashboard" title="Retornar ao Painel Administrativo">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Voltar ao Dashboard</span>
            </a>

            <div class="admin-role-pill">
                <span>👑 Permissão: <strong>Administrador</strong></span>
            </div>
        </div>

        <!-- Banner de Cabeçalho do Perfil -->
        <div class="profile-header-card">
            <div class="profile-avatar-large">
                <?= strtoupper(mb_substr(trim($dadosAdmin['User_name']), 0, 1)) ?>
            </div>
            <div class="profile-header-info">
                <div class="header-tag-pill">
                    <span>✦ Credenciais & Segurança ✦</span>
                </div>
                <h1 class="profile-main-title">Meu Perfil Administrativo</h1>
                <p class="profile-subtitle">
                    Gerencie seu nome de exibição e atualize sua senha de acesso ao painel de controle do <strong>MB Studio</strong>.
                </p>
            </div>
        </div>

        <!-- Área de Alertas e Flash Messages -->
        <?php if (!empty($erroFlash)): ?>
            <div class="profile-alert profile-alert-error" role="alert">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span><?= htmlspecialchars($erroFlash) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($sucessoFlash)): ?>
            <div class="profile-alert profile-alert-success" role="status">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span><?= htmlspecialchars($sucessoFlash) ?></span>
            </div>
        <?php endif; ?>

        <!-- Card do Formulário de Perfil -->
        <div class="profile-form-card">
            <form action="<?= $basePath ?>/admin/perfil/atualizar" method="POST" autocomplete="off">
                <?= Session::csrfField() ?>

                <div class="profile-section-block">
                    <h2 class="profile-section-title">
                        <span>1.</span> Dados da Conta Administrativa
                    </h2>
                    <p class="profile-section-desc">
                        O e-mail e o nível de permissão são protegidos por segurança contra alterações diretas.
                    </p>

                    <div class="profile-grid-two">
                        <!-- Nome (Editável) -->
                        <div class="profile-form-group">
                            <label for="User_name">Nome Completo / Exibição *</label>
                            <input
                                type="text"
                                id="User_name"
                                name="User_name"
                                value="<?= htmlspecialchars($dadosAdmin['User_name']) ?>"
                                required
                                minlength="3"
                                maxlength="100"
                                class="profile-input"
                                placeholder="Digite seu nome"
                            >
                        </div>

                        <!-- E-mail (Somente Leitura / Desabilitado) -->
                        <div class="profile-form-group">
                            <label for="User_email_readonly">
                                E-mail de Login
                                <span class="locked-field-tag">🔒 Protegido (Somente Leitura)</span>
                            </label>
                            <input
                                type="email"
                                id="User_email_readonly"
                                value="<?= htmlspecialchars($dadosAdmin['User_email']) ?>"
                                readonly
                                disabled
                                class="profile-input profile-input-locked"
                                title="O e-mail do administrador é travado para prevenir sequestro de conta."
                            >
                        </div>
                    </div>

                    <!-- Badge de Permissão (Somente Leitura) -->
                    <div class="profile-perm-display">
                        <div>
                            <span class="perm-label">Nível de Acesso no Sistema</span>
                            <p class="perm-help">Conta com privilégios totais de gestão e auditoria.</p>
                        </div>
                        <span class="perm-badge-admin">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                            Administrador
                        </span>
                    </div>
                </div>

                <hr class="profile-divider">

                <div class="profile-section-block">
                    <h2 class="profile-section-title">
                        <span>2.</span> Alteração de Senha <small>(Opcional)</small>
                    </h2>
                    <p class="profile-section-desc">
                        Preencha os campos abaixo apenas se desejar alterar sua senha atual. Caso contrário, deixe-os em branco.
                    </p>

                    <div class="profile-form-group" style="max-width: 460px;">
                        <label for="senha_atual">Senha Atual <small>(Obrigatória para alterar a senha)</small></label>
                        <input
                            type="password"
                            id="senha_atual"
                            name="senha_atual"
                            class="profile-input"
                            placeholder="Digite sua senha atual"
                        >
                    </div>

                    <div class="profile-grid-two">
                        <div class="profile-form-group">
                            <label for="nova_senha">Nova Senha <small>(Mínimo 8 caracteres)</small></label>
                            <input
                                type="password"
                                id="nova_senha"
                                name="nova_senha"
                                minlength="8"
                                class="profile-input"
                                placeholder="Nova senha segura"
                            >
                        </div>

                        <div class="profile-form-group">
                            <label for="confirmar_senha">Confirmar Nova Senha</label>
                            <input
                                type="password"
                                id="confirmar_senha"
                                name="confirmar_senha"
                                minlength="8"
                                class="profile-input"
                                placeholder="Repita a nova senha"
                            >
                        </div>
                    </div>
                </div>

                <div class="profile-actions-footer">
                    <a href="<?= $basePath ?>/View/admin/Dashboard.php" class="btn-cancel-profile">Cancelar</a>
                    <button type="submit" class="btn-save-profile">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>

    </main>

</body>
</html>

