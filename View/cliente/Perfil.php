<?php
require_once __DIR__ . "/../../middleware/ClienteAuth.php";
require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../model/Client.php";

$userId = $_SESSION['User_id'];
$clientModel = new Client($pdo);
$dadosCliente = $clientModel->buscarPorUserId($userId);

// Se os dados não forem encontrados (usuário não existe em users por algum erro), exibe tela de erro
$usuarioNaoEncontrado = ($dadosCliente === false);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil | MB Studio</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/cliente.css">
</head>
<body class="client-page">

    <?php include __DIR__ . '/../components/Header.php'; ?>
    <?php include __DIR__ . '/../components/LoginModal.php'; ?>

    <main class="client-main">
        <div class="main-container">

            <?php if ($usuarioNaoEncontrado): ?>
                <div class="client-error-card">
                    <div class="error-icon">⚠️</div>
                    <h2 class="error-title">Usuário não encontrado</h2>
                    <p class="error-description">Não foi possível localizar os seus dados de cadastro no sistema. Por favor, faça login novamente ou contate o suporte.</p>
                </div>
            <?php else: ?>

                <div class="client-profile-intro">
                    <div class="golden-accent-detail"></div>
                    <h1 class="client-profile-title">Meu Perfil</h1>
                    <p class="client-profile-subtitle">Gerencie suas informações pessoais e credenciais de acesso.</p>
                </div>

                <form action="../../controller/SalvarPerfilCliente.php" method="POST" class="client-profile-form">
                    
                    <!-- DADOS PESSOAIS -->
                    <div class="profile-section">
                        <div class="profile-section-header">
                            <h2 class="profile-section-title">Dados Pessoais</h2>
                            <p class="profile-section-description">Informações para contato e identificação no salão.</p>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="nome">Nome Completo <span class="required-mark">*</span></label>
                                <input type="text" id="nome" name="nome" class="form-input" required 
                                       maxlength="100" placeholder="Seu nome completo" 
                                       value="<?= htmlspecialchars($dadosCliente['User_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email">E-mail <span class="required-mark">*</span></label>
                                <input type="email" id="email" name="email" class="form-input" required 
                                       maxlength="150" placeholder="seu.email@exemplo.com"
                                       value="<?= htmlspecialchars($dadosCliente['User_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="telefone">Telefone</label>
                                <input type="text" id="telefone" name="telefone" class="form-input" 
                                       maxlength="20" placeholder="(00) 00000-0000"
                                       value="<?= htmlspecialchars($dadosCliente['Cli_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <p class="form-helper">Apenas números ou formato com DDD.</p>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="nascimento">Data de Nascimento</label>
                                <input type="date" id="nascimento" name="nascimento" class="form-input"
                                    required
                                    max="<?= date('Y-m-d', strtotime('-16 years')) ?>"
                                    value="<?= htmlspecialchars($dadosCliente['Cli_birth'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- SEGURANÇA -->
                    <div class="profile-section password-section">
                        <div class="profile-section-header">
                            <h2 class="profile-section-title">Segurança</h2>
                            <p class="profile-section-description">Altere sua senha de acesso. Deixe em branco caso não queira alterar.</p>
                        </div>

                        <div class="form-grid">
                            <div class="form-group form-group-full">
                                <label class="form-label" for="senha_atual">Senha Atual</label>
                                <input type="password" id="senha_atual" name="senha_atual" class="form-input" 
                                       placeholder="Digite sua senha atual para autorizar mudanças">
                                <p class="form-helper">Obrigatória apenas se for alterar a senha.</p>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="nova_senha">Nova Senha</label>
                                <input type="password" id="nova_senha" name="nova_senha" class="form-input" 
                                       minlength="6" placeholder="Mínimo de 6 caracteres">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="confirmar_senha">Confirmar Nova Senha</label>
                                <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-input" 
                                       placeholder="Repita a nova senha">
                            </div>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <button type="submit" class="btn-save-profile">Salvar Alterações</button>
                    </div>

                </form>

            <?php endif; ?>

        </div>
    </main>

    <!-- Scripts da view -->
    <script>
        // Aplicar máscara simples de telefone (se tiver jQuery/plugin seria melhor, mas fazendo vanilla para manter consistência)
        document.getElementById('telefone')?.addEventListener('input', function (e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,5})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });
    </script>
</body>
</html>
