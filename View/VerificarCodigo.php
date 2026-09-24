<?php
if(session_status() == PHP_SESSION_NONE) { session_start(); }
if(!isset($_SESSION['verificar_user_id'])) {
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
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?> - MB Studio</title>
    <!-- Adicionando um estilo simples inspirado no Index.php -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .card { width: 100%; max-width: 400px; padding: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
        .codigo-input { letter-spacing: 5px; font-size: 24px; text-align: center; font-weight: bold; }
    </style>
</head>
<body>

    <div class="card text-center">
        <h3 class="mb-3"><?= $titulo ?></h3>
        <p class="text-muted mb-4"><?= $descricao ?></p>

        <form action="../controller/ValidarCodigo.php" method="POST">
            <div class="mb-3">
                <input type="text" name="codigo" class="form-control codigo-input" placeholder="000000" maxlength="6" required autocomplete="off">
            </div>
            <button type="submit" class="btn btn-primary w-100">Verificar Código</button>
        </form>

        <div class="mt-3">
            <a href="../Index.php" class="text-decoration-none">Cancelar e Voltar</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
