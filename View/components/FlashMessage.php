<?php
if (class_exists('Session')) {
    $flashSucesso = Session::getFlash('sucesso');
    $flashErro    = Session::getFlash('erro');
} else {
    $flashSucesso = null;
    $flashErro    = null;
}
?>
<?php if ($flashSucesso || $flashErro): ?>
    <div style="max-width: 1120px; margin: 16px auto; padding: 0 20px;">
        <?php if ($flashSucesso): ?>
            <div role="status" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;border-radius:10px;background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46;font-size:14px;font-weight:500;margin-bottom:10px;box-shadow:0 4px 12px rgba(6,95,70,0.06);">
                <span>✓ <?= htmlspecialchars($flashSucesso, ENT_QUOTES, 'UTF-8') ?></span>
                <button type="button" onclick="this.parentElement.remove()" aria-label="Fechar aviso" style="background:none;border:none;color:#065F46;font-size:18px;cursor:pointer;line-height:1;padding:0 4px;">&times;</button>
            </div>
        <?php endif; ?>
        <?php if ($flashErro): ?>
            <div role="alert" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;border-radius:10px;background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;font-size:14px;font-weight:500;margin-bottom:10px;box-shadow:0 4px 12px rgba(153,27,27,0.06);">
                <span>⚠ <?= htmlspecialchars($flashErro, ENT_QUOTES, 'UTF-8') ?></span>
                <button type="button" onclick="this.parentElement.remove()" aria-label="Fechar aviso" style="background:none;border:none;color:#991B1B;font-size:18px;cursor:pointer;line-height:1;padding:0 4px;">&times;</button>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
