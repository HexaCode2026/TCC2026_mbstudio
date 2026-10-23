<?php
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../model/User.php";

Session::iniciar();

$basePath = Session::getBasePath();

if (empty($_SESSION['User_id'])) {
    header("Location: " . $basePath . "/Index.php");
    exit;
}

if (empty($_SESSION['User_must_change_pass'])) {
    $perm = $_SESSION['User_perm'] ?? 'C';
    if ($perm === 'A') {
        header("Location: " . $basePath . "/View/admin/Dashboard.php");
    } elseif ($perm === 'F') {
        header("Location: " . $basePath . "/View/funcionario/Home.php");
    } else {
        header("Location: " . $basePath . "/Index.php");
    }
    exit;
}

$erroFlash    = $erroFlash ?? Session::getFlash('error');
$sucessoFlash = $sucessoFlash ?? Session::getFlash('success');
$userName     = $_SESSION['User_name'] ?? 'Administrador';
$userEmail    = $_SESSION['User_email'] ?? '';
$globalCssVer = @filemtime(__DIR__ . '/../assets/css/global.css') ?: '3.0';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Primeiro Acesso — Troca Obrigatória de Senha | MB Studio</title>

    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/global.css?v=<?= $globalCssVer ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;1,400&family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <style>
        body.first-login-isolated-page {
            min-height: 100vh;
            margin: 0;
            background: radial-gradient(circle at top, #1E293B 0%, #0F172A 70%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            font-family: 'Inter', sans-serif;
            color: #1E293B;
        }
        .first-login-card {
            width: 100%;
            max-width: 480px;
            background: #FFFFFF;
            border-radius: 18px;
            border: 1px solid rgba(212, 175, 55, 0.35);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
            overflow: hidden;
        }
        .first-login-header {
            background: #0F172A;
            padding: 30px 32px 26px;
            text-align: center;
            border-bottom: 3px solid #D4AF37;
        }
        .first-login-tag {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: #D4AF37;
            background: rgba(212, 175, 55, 0.12);
            border: 1px solid rgba(212, 175, 55, 0.3);
            padding: 5px 14px;
            border-radius: 999px;
            margin-bottom: 12px;
        }
        .first-login-title {
            margin: 0 0 8px 0;
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 24px;
            font-weight: 700;
            color: #FFFFFF;
        }
        .first-login-subtitle {
            margin: 0;
            font-size: 13.5px;
            color: #CBD5E1;
            line-height: 1.5;
        }
        .first-login-body {
            padding: 30px 32px 32px;
        }
        .first-login-user-pill {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .first-login-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #D4AF37, #9A7B1C);
            color: #0F172A;
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .first-login-user-meta {
            overflow: hidden;
        }
        .first-login-user-name {
            font-size: 14px;
            font-weight: 700;
            color: #0F172A;
        }
        .first-login-user-email {
            font-size: 12.5px;
            color: #64748B;
            text-overflow: ellipsis;
            overflow: hidden;
            white-space: nowrap;
        }
        .alert-box {
            padding: 13px 16px;
            border-radius: 10px;
            font-size: 13.5px;
            line-height: 1.45;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-error {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #991B1B;
        }
        .alert-success {
            background: #ECFDF5;
            border: 1px solid #A7F3D0;
            color: #065F46;
        }
        .security-rules-box {
            background: #FAF6EC;
            border-left: 4px solid #D4AF37;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 12.5px;
            color: #475569;
            margin-bottom: 22px;
            line-height: 1.5;
        }
        .security-rules-box strong {
            color: #0F172A;
        }
        .form-group-isolated {
            margin-bottom: 18px;
        }
        .form-group-isolated label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 7px;
        }
        .form-group-isolated input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #CBD5E1;
            border-radius: 10px;
            font-size: 14.5px;
            color: #0F172A;
            background: #FFFFFF;
            box-sizing: border-box;
            transition: all 0.2s ease;
            outline: none;
        }
        .form-group-isolated input:focus {
            border-color: #D4AF37;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.18);
        }
        .btn-confirm-first {
            width: 100%;
            padding: 13px 20px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #D4AF37 0%, #9A7B1C 100%);
            color: #0F172A;
            font-size: 14.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 6px 16px rgba(184, 134, 11, 0.28);
            margin-top: 6px;
        }
        .btn-confirm-first:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(184, 134, 11, 0.38);
        }
        .btn-logout-first {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            margin-top: 12px;
            padding: 11px 16px;
            border-radius: 10px;
            border: 1px solid #E2E8F0;
            background: #F8FAFC;
            color: #64748B;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }
        .btn-logout-first:hover {
            background: #FEF2F2;
            border-color: #FECACA;
            color: #DC2626;
        }
    </style>
</head>
<body class="first-login-isolated-page">

    <main class="first-login-card">
        <div class="first-login-header">
            <span class="first-login-tag">🔒 Segurança Obrigatória</span>
            <h1 class="first-login-title">Primeiro Acesso</h1>
            <p class="first-login-subtitle">
                Por segurança, defina uma nova senha pessoal antes de acessar o Painel Administrativo do <strong>MB Studio</strong>.
            </p>
        </div>

        <div class="first-login-body">
            <div class="first-login-user-pill">
                <div class="first-login-avatar"><?= strtoupper(mb_substr(trim($userName), 0, 1)) ?></div>
                <div class="first-login-user-meta">
                    <div class="first-login-user-name"><?= htmlspecialchars($userName) ?></div>
                    <div class="first-login-user-email"><?= htmlspecialchars($userEmail) ?></div>
                </div>
            </div>

            <?php if (!empty($erroFlash)): ?>
                <div class="alert-box alert-error" role="alert">
                    ⚠️ <?= htmlspecialchars($erroFlash) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($sucessoFlash)): ?>
                <div class="alert-box alert-success" role="status">
                    ✅ <?= htmlspecialchars($sucessoFlash) ?>
                </div>
            <?php endif; ?>

            <div class="security-rules-box">
                <strong>Requisitos de Segurança:</strong><br>
                • Mínimo de <strong>8 caracteres</strong>.<br>
                • Não é permitido reutilizar a senha temporária padrão (<code>admin123</code>).
            </div>

            <form action="<?= $basePath ?>/controller/PrimeiroAcessoController.php?action=salvar" method="POST" autocomplete="off">
                <?= Session::csrfField() ?>

                <div class="form-group-isolated">
                    <label for="nova_senha">Nova Senha</label>
                    <input
                        type="password"
                        id="nova_senha"
                        name="nova_senha"
                        required
                        minlength="8"
                        placeholder="Digite sua nova senha (mín. 8 caracteres)"
                    >
                </div>

                <div class="form-group-isolated">
                    <label for="confirmar_senha">Confirmar Nova Senha</label>
                    <input
                        type="password"
                        id="confirmar_senha"
                        name="confirmar_senha"
                        required
                        minlength="8"
                        placeholder="Repita a nova senha para confirmar"
                    >
                </div>

                <button type="submit" class="btn-confirm-first">
                    Confirmar Nova Senha e Acessar Painel
                </button>

                <a href="<?= $basePath ?>/controller/Logout.php" class="btn-logout-first">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Sair / Deslogar
                </a>
            </form>
        </div>
    </main>

</body>
</html>

