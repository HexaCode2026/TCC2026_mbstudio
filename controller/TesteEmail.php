<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../helpers/EmailHelper.php';

Session::iniciar();

if (PHP_SAPI !== 'cli' && (($_SESSION['User_perm'] ?? '') !== 'A')) {
    if (($_GET['format'] ?? '') === 'json') {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Acesso restrito a administradores.']);
        exit;
    }
    header("Location: ../Index.php");
    exit;
}

$destinatario = trim($_POST['email'] ?? (defined('SMTP_FROM_EMAIL') && SMTP_FROM_EMAIL !== '' ? SMTP_FROM_EMAIL : ''));
$nomeDestino  = trim($_POST['nome'] ?? 'Teste MB Studio');
$autoDisparar = (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['disparar'])) || (PHP_SAPI === 'cli');

if (PHP_SAPI !== 'cli' && $autoDisparar) {
    Session::validarCsrf();
}

$mailer = new EmailHelper();
$resultados = [];

if ($autoDisparar && filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
    // 1. Teste Template Confirmação de Cadastro
    $ok1 = $mailer->enviarConfirmacaoCadastro($destinatario, $nomeDestino, '849201');
    $resultados['ConfirmacaoCadastro'] = [
        'titulo'  => '1. Confirmação de Cadastro (enviarConfirmacaoCadastro)',
        'sucesso' => $ok1,
        'erro'    => $mailer->getLastError(),
    ];

    // 2. Teste Template Código 2FA
    $ok2 = $mailer->enviarCodigo2FA($destinatario, $nomeDestino, '593017');
    $resultados['Codigo2FA'] = [
        'titulo'  => '2. Código de Verificação 2FA (enviarCodigo2FA)',
        'sucesso' => $ok2,
        'erro'    => $mailer->getLastError(),
    ];

    // 3. Teste Template Recuperação de Senha
    $ok3 = $mailer->enviarRecuperacaoSenha($destinatario, $nomeDestino, '718492');
    $resultados['RecuperacaoSenha'] = [
        'titulo'  => '3. Recuperação de Senha (enviarRecuperacaoSenha)',
        'sucesso' => $ok3,
        'erro'    => $mailer->getLastError(),
    ];
}

if (PHP_SAPI === 'cli' || (($_GET['format'] ?? '') === 'json')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'mailer'      => 'PHPMailer (SMTP)',
        'smtp_host'   => defined('SMTP_HOST') ? SMTP_HOST : '',
        'smtp_port'   => defined('SMTP_PORT') ? SMTP_PORT : 587,
        'smtp_secure' => defined('SMTP_SECURE') ? SMTP_SECURE : 'tls',
        'from_email'  => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : '',
        'from_name'   => defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : '',
        'to_email'    => $destinatario,
        'results'     => $resultados,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico PHPMailer SMTP | MB Studio</title>
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #F8FAFC; color: #0F172A; margin: 0; padding: 40px 20px; }
        .card { max-width: 780px; margin: 0 auto; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 32px; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05); }
        h1 { margin: 0 0 8px 0; font-size: 24px; color: #0F172A; }
        .sub { color: #64748B; font-size: 14.5px; margin-bottom: 24px; }
        .form-row { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
        input[type="email"] { flex: 1; min-width: 240px; padding: 11px 14px; border: 1.5px solid #CBD5E1; border-radius: 8px; font-size: 14.5px; }
        button { padding: 11px 22px; background: linear-gradient(135deg, #D4AF37, #B8860B); color: #0F172A; font-weight: 700; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; }
        .result-item { padding: 16px; border-radius: 10px; border: 1px solid #E2E8F0; margin-bottom: 12px; background: #FCFBF9; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .ok { background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0; }
        .err { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
        pre { margin: 10px 0 0 0; padding: 10px; background: #0F172A; color: #F8FAFC; border-radius: 6px; font-size: 12px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Diagnóstico PHPMailer Service (SMTP)</h1>
        <p class="sub">
            Servidor: <strong><?= htmlspecialchars((string)(defined('SMTP_HOST') ? SMTP_HOST : '')) ?>:<?= (int)(defined('SMTP_PORT') ? SMTP_PORT : 587) ?></strong>
            (<?= htmlspecialchars(strtoupper((string)(defined('SMTP_SECURE') ? SMTP_SECURE : 'tls'))) ?>) |
            Remetente: <strong><?= htmlspecialchars((string)(defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : '')) ?></strong>
            (<?= htmlspecialchars((string)(defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : '')) ?>)
        </p>

        <form method="POST" class="form-row">
            <?= Session::csrfField() ?>
            <input type="hidden" name="disparar" value="1">
            <input type="email" name="email" value="<?= htmlspecialchars($destinatario) ?>" placeholder="E-mail de destino para teste" required>
            <button type="submit">Disparar 3 Templates de Teste</button>
        </form>

        <?php if (!empty($resultados)): ?>
            <?php foreach ($resultados as $res): ?>
                <div class="result-item">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                        <strong><?= htmlspecialchars($res['titulo']) ?></strong>
                        <span class="badge <?= $res['sucesso'] ? 'ok' : 'err' ?>">
                            <?= $res['sucesso'] ? 'ENVIADO VIA SMTP' : 'FALHA SMTP' ?>
                        </span>
                    </div>
                    <?php if (!empty($res['erro'])): ?>
                        <pre><?= htmlspecialchars($res['erro']) ?></pre>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
