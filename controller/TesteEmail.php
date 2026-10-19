<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../helpers/BrevoEmailService.php';

Session::iniciar();

$destinatario = trim($_GET['email'] ?? $_POST['email'] ?? (getenv('BREVO_FROM_EMAIL') ?: 'hexa.code2026@gmail.com'));
$nomeDestino  = trim($_GET['nome'] ?? $_POST['nome'] ?? 'Teste MB Studio');
$autoDisparar = isset($_GET['disparar']) || isset($_POST['disparar']) || (PHP_SAPI === 'cli');

$brevo = new BrevoEmailService();
$resultados = [];

if ($autoDisparar && filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
    // 1. Teste Template Confirmação de Cadastro
    $ok1 = $brevo->enviarConfirmacaoCadastro($destinatario, $nomeDestino, '849201');
    $resultados['ConfirmacaoCadastro'] = [
        'titulo'    => '1. Confirmação de Cadastro (enviarConfirmacaoCadastro)',
        'sucesso'   => $ok1,
        'http_code' => $brevo->getLastHttpCode(),
        'resposta'  => $brevo->getLastResponse(),
        'erro'      => $brevo->getLastError(),
    ];

    // 2. Teste Template Código 2FA
    $ok2 = $brevo->enviarCodigo2FA($destinatario, $nomeDestino, '593017');
    $resultados['Codigo2FA'] = [
        'titulo'    => '2. Código de Verificação 2FA (enviarCodigo2FA)',
        'sucesso'   => $ok2,
        'http_code' => $brevo->getLastHttpCode(),
        'resposta'  => $brevo->getLastResponse(),
        'erro'      => $brevo->getLastError(),
    ];

    // 3. Teste Template Recuperação de Senha
    $ok3 = $brevo->enviarRecuperacaoSenha($destinatario, $nomeDestino, '718492');
    $resultados['RecuperacaoSenha'] = [
        'titulo'    => '3. Recuperação de Senha (enviarRecuperacaoSenha)',
        'sucesso'   => $ok3,
        'http_code' => $brevo->getLastHttpCode(),
        'resposta'  => $brevo->getLastResponse(),
        'erro'      => $brevo->getLastError(),
    ];
}

if (PHP_SAPI === 'cli' || (($_GET['format'] ?? '') === 'json')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'endpoint'   => 'https://api.brevo.com/v3/smtp/email',
        'from_email' => getenv('BREVO_FROM_EMAIL'),
        'from_name'  => getenv('BREVO_FROM_NAME'),
        'to_email'   => $destinatario,
        'results'    => $resultados,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Brevo API v3 | MB Studio</title>
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
        <h1>Diagnóstico Brevo Email Service (API v3 HTTPS)</h1>
        <p class="sub">Remetente configurado: <strong><?= htmlspecialchars((string)getenv('BREVO_FROM_EMAIL')) ?></strong> (<?= htmlspecialchars((string)getenv('BREVO_FROM_NAME')) ?>)</p>

        <form method="GET" class="form-row">
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
                            HTTP <?= (int)$res['http_code'] ?> — <?= $res['sucesso'] ? 'ENVIADO (201/200)' : 'FALHA' ?>
                        </span>
                    </div>
                    <?php if ($res['resposta']): ?>
                        <pre><?= htmlspecialchars($res['resposta']) ?></pre>
                    <?php endif; ?>
                    <?php if ($res['erro']): ?>
                        <pre><?= htmlspecialchars($res['erro']) ?></pre>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>

