<?php
if(session_status() == PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['verificar_user_id']) && !isset($_SESSION['pending_registration'])) {
    header("Location: ../Index.php");
    exit;
}
$tipo = $_SESSION['verificar_tipo'] ?? 'Cadastro';
$titulo = $tipo == 'Cadastro' ? 'Ativar Conta' : 'Verificação de Duas Etapas (2FA)';
$descricao = $tipo == 'Cadastro' 
    ? 'Enviamos um código de 6 dígitos para o seu e-mail. Digite-o abaixo para ativar sua conta.' 
    : 'Para sua segurança, enviamos um código de verificação para o seu e-mail.';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?> | MB Studio</title>
    <link rel="stylesheet" href="../assets/css/global.css">
    <style>
        body.verification-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 50% 30%, rgba(212, 175, 55, 0.08) 0%, transparent 60%), var(--bg-body, #fbfaf7);
            padding: 24px 16px;
            overflow-x: clip;
        }

        .verify-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border: 1px solid var(--border-subtle, #e4e4e7);
            border-radius: var(--radius-xl, 24px);
            padding: clamp(32px, 5vw, 44px) clamp(24px, 5vw, 36px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.03);
            text-align: center;
            position: relative;
        }

        .verify-icon-wrap {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(184, 134, 11, 0.25));
            border: 1px solid rgba(212, 175, 55, 0.4);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--gold-dark, #b8860b);
            margin-bottom: 20px;
        }

        .verify-title {
            font-family: var(--font-heading, 'Playfair Display', serif);
            font-size: clamp(22px, 3.5vw, 26px);
            font-weight: 700;
            color: var(--text-primary, #18181b);
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .verify-desc {
            font-size: 14px;
            color: var(--text-secondary, #52525b);
            line-height: 1.55;
            margin-bottom: 28px;
        }

        .verify-input-group {
            margin-bottom: 24px;
        }

        .codigo-input {
            width: 100%;
            min-height: 54px;
            letter-spacing: 12px;
            font-size: 26px;
            text-align: center;
            font-weight: 700;
            color: #18181b !important;
            background: #ffffff !important;
            border: 1.5px solid var(--border-medium, #d4d4d8) !important;
            border-radius: var(--radius-md, 12px);
            outline: none !important;
            color-scheme: light !important;
            transition: all 0.2s ease;
            font-family: monospace, inherit;
        }

        .codigo-input:focus {
            border-color: var(--gold-primary, #d4af37) !important;
            background: #ffffff !important;
            color: #18181b !important;
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.2) !important;
        }

        .btn-verify-submit {
            width: 100%;
            min-height: 48px;
            background: var(--gold-gradient, linear-gradient(135deg, #d4af37, #b8860b));
            color: #0f1115;
            border: none;
            border-radius: var(--radius-sm, 8px);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(212, 175, 55, 0.35);
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .btn-verify-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(212, 175, 55, 0.45);
        }

        .verify-cancel-link {
            display: inline-block;
            margin-top: 20px;
            font-size: 13.5px;
            color: var(--text-muted, #71717a);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.15s;
        }

        .verify-cancel-link:hover {
            color: var(--text-primary, #18181b);
            text-decoration: underline;
        }
    </style>
</head>
<body class="verification-page">

    <div class="verify-card">
        <div class="verify-icon-wrap">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>

        <h1 class="verify-title"><?= htmlspecialchars($titulo) ?></h1>
        <p class="verify-desc"><?= htmlspecialchars($descricao) ?></p>
        <?php include __DIR__ . '/components/FlashMessage.php'; ?>

        <form action="../controller/ValidarCodigo.php" method="POST">
            <?= Session::csrfField() ?>
            <div class="verify-input-group">
                <input 
                    type="text" 
                    name="codigo" 
                    class="codigo-input" 
                    placeholder="000000" 
                    maxlength="6" 
                    required 
                    autocomplete="one-time-code"
                    inputmode="numeric"
                    autofocus
                >
            </div>
            <button type="submit" class="btn-verify-submit">Confirmar e Validar</button>
        </form>

        <div>
            <a href="../Index.php" class="verify-cancel-link">Cancelar e Voltar ao Início</a>
        </div>
    </div>

</body>
</html>
