<?php


require_once __DIR__ . "/../../middleware/FuncionarioAuth.php";

require_once __DIR__ . "/../../config/conexao.php";

require_once __DIR__ . "/../../model/Employee.php";



$userId = $_SESSION['User_id'];

$employeeModel = new Employee($pdo);

$dadosFuncionario = $employeeModel->buscarPorUserId($userId);

$globalCssVer = @filemtime(__DIR__ . '/../../assets/css/global.css') ?: '3.0';
$funcCssVer   = @filemtime(__DIR__ . '/../../assets/css/funcionario.css') ?: '3.0';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Meu Perfil | MB Studio</title>

    <link rel="stylesheet" href="../../assets/css/global.css?v=<?= $globalCssVer ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/funcionario.css?v=<?= $funcCssVer ?>">

</head>


<body class="employee-page">
    <!-- Cabeçalho Global MB Studio -->
    <?php include '../components/Header.php'; ?>
    <?php include '../components/LoginModal.php'; ?>
    <!-- ========================================== -->
    <!-- CONTEÚDO PRINCIPAL                         -->
    <!-- ========================================== -->

    <main class="employee-main">

        <div class="main-container">

            <!-- NAVEGAÇÃO SUPERIOR OPERACIONAL -->
            <div class="perfil-top-nav">
                <a href="Home.php" class="btn-back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Voltar ao Início
                </a>

                <div class="perfil-nav-right">
                    <a href="Agenda.php" class="btn-back-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Minha Agenda
                    </a>
                    <a href="Disponibilidade.php" class="btn-back-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Meus Horários
                    </a>
                </div>
            </div>

            <!-- INTRODUÇÃO DA PÁGINA -->

            <section class="employee-profile-intro">

                <div class="perfil-badge">
                    <span>✦ Conta Profissional ✦</span>
                </div>

                <h1 class="employee-profile-title">Meu <span class="cursiva-gold">Perfil</span></h1>

                <p class="employee-profile-subtitle">
                    Atualize suas informações profissionais, foto de apresentação e seus dados de acesso.
                </p>

            </section>

            <?php include __DIR__ . '/../components/FlashMessage.php'; ?>




            <?php if(!$dadosFuncionario): ?>

                <!-- TRATAMENTO: CADASTRO NÃO ENCONTRADO -->

                <div class="employee-error-card">

                    <div class="error-icon">✦</div>

                    <h2 class="error-title">Cadastro Profissional não Encontrado</h2>

                    <p class="error-description">
                        Não foi possível localizar o seu registro profissional na equipe. 
                        Por favor, entre em contato com a gerência ou com o administrador do sistema para regularizar o seu cadastro.
                    </p>

                </div>


            <?php else: ?>

                <!-- FORMULÁRIO DE PERFIL -->

                <form 
                    method="POST" 
                    action="../../controller/SalvarPerfilFuncionario.php" 
                    class="employee-profile-form"
                    enctype="multipart/form-data"
                >
                    <?= Session::csrfField() ?>


                    <!-- ========================================== -->
                    <!-- CARD 1: INFORMAÇÕES PROFISSIONAIS          -->
                    <!-- ========================================== -->

                    <div class="profile-section">

                        <div class="profile-section-header">

                            <h2 class="profile-section-title">Informações Profissionais</h2>

                            <p class="profile-section-description">
                                Mantenha seus dados atualizados para que seus clientes encontrem as informações corretas.
                            </p>

                        </div>


                        <!-- FOTO DE PERFIL -->

                        <div class="profile-photo-area">

                            <div class="profile-photo-wrapper">

                                <?php if (!empty($dadosFuncionario['Emp_photo'])): ?>

                                    <img
                                        id="fotoPreview"
                                        src="../../<?= htmlspecialchars($dadosFuncionario['Emp_photo'], ENT_QUOTES, 'UTF-8'); ?>"
                                        alt="Foto de perfil"
                                        class="profile-photo-img"
                                    >

                                <?php else: ?>

                                    <div id="fotoPlaceholder" class="profile-photo-placeholder">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" width="48" height="48">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                    </div>

                                    <img
                                        id="fotoPreview"
                                        src=""
                                        alt="Foto de perfil"
                                        class="profile-photo-img"
                                        style="display:none;"
                                    >

                                <?php endif; ?>

                            </div>

                            <div class="profile-photo-info">

                                <h3 class="profile-photo-title">Foto de Perfil</h3>

                                <p class="profile-photo-hint">
                                    Formatos aceitos: JPG, PNG ou WebP. Tamanho máximo: 2MB.
                                </p>

                                <label for="foto" class="btn-upload-photo">Escolher Nova Foto</label>

                                <input
                                    type="file"
                                    id="foto"
                                    name="foto"
                                    accept="image/jpeg,image/png,image/webp"
                                    hidden
                                >

                                <span id="fotoNome" class="foto-nome-selecionada"></span>

                            </div>

                        </div>


                        <div class="form-grid">

                            <!-- NOME -->

                            <div class="form-group">

                                <label for="nome" class="form-label">
                                    Nome Completo <span class="required-mark">*</span>
                                </label>

                                <input 
                                    type="text" 
                                    id="nome" 
                                    name="nome" 
                                    class="form-input" 
                                    autocomplete="name"
                                    required 
                                    maxlength="100" 
                                    value="<?= htmlspecialchars($dadosFuncionario['User_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                >

                            </div>


                            <!-- EMAIL -->

                            <div class="form-group">

                                <label for="email" class="form-label">
                                    Email de Acesso <span class="required-mark">*</span>
                                </label>

                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    class="form-input" 
                                    autocomplete="email"
                                    required 
                                    maxlength="150" 
                                    value="<?= htmlspecialchars($dadosFuncionario['User_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                >

                            </div>


                            <!-- ESPECIALIDADE -->

                            <div class="form-group form-group-full">

                                <label for="especialidade" class="form-label">
                                    Especialidade Principal
                                </label>

                                <input 
                                    type="text" 
                                    id="especialidade" 
                                    name="especialidade" 
                                    class="form-input" 
                                    maxlength="100" 
                                    placeholder="Ex.: Cabeleireiro, Manicure, Maquiador..."
                                    value="<?= htmlspecialchars($dadosFuncionario['Emp_specialty'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                >

                                <span class="form-helper">
                                    Sua principal área de atuação no salão.
                                </span>

                            </div>


                            <!-- BIO / SOBRE MIM -->

                            <div class="form-group form-group-full">

                                <label for="bio" class="form-label">
                                    Bio / Sobre Mim
                                </label>

                                <textarea id="bio" name="bio" class="form-textarea" rows="4" placeholder="Conte um pouco sobre sua trajetória, técnicas e atendimento..."><?= htmlspecialchars($dadosFuncionario['Emp_bio'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>

                                <span class="form-helper">
                                    Uma breve apresentação profissional exibida para seus clientes.
                                </span>

                            </div>

                        </div>

                    </div>




                    <!-- ========================================== -->
                    <!-- CARD 2: SEGURANÇA                          -->
                    <!-- ========================================== -->

                    <div class="profile-section password-section">

                        <div class="profile-section-header">

                            <h2 class="profile-section-title">Segurança</h2>

                            <p class="profile-section-description">
                                Preencha os campos abaixo somente se desejar alterar sua senha.
                            </p>

                        </div>


                        <div class="form-grid">

                            <!-- SENHA ATUAL -->

                            <div class="form-group form-group-full">

                                <label for="senha_atual" class="form-label">
                                    Senha Atual
                                </label>

                                <input 
                                    type="password" 
                                    id="senha_atual" 
                                    name="senha_atual" 
                                    class="form-input" 
                                    autocomplete="current-password"
                                    placeholder="Digite sua senha atual para autorizar a troca"
                                >

                                <span class="form-helper">
                                    Necessária apenas se for definir uma nova senha.
                                </span>

                            </div>


                            <!-- NOVA SENHA -->

                            <div class="form-group">

                                <label for="nova_senha" class="form-label">
                                    Nova Senha
                                </label>

                                <input 
                                    type="password" 
                                    id="nova_senha" 
                                    name="nova_senha" 
                                    class="form-input" 
                                    minlength="6" 
                                    autocomplete="new-password"
                                    placeholder="Mínimo de 6 caracteres"
                                >

                            </div>


                            <!-- CONFIRMAR NOVA SENHA -->

                            <div class="form-group">

                                <label for="confirmar_senha" class="form-label">
                                    Confirmar Nova Senha
                                </label>

                                <input 
                                    type="password" 
                                    id="confirmar_senha" 
                                    name="confirmar_senha" 
                                    class="form-input" 
                                    minlength="6" 
                                    autocomplete="new-password"
                                    placeholder="Repita a nova senha"
                                >

                            </div>

                        </div>

                    </div>




                    <!-- ========================================== -->
                    <!-- AÇÕES / SUBMISSÃO                          -->
                    <!-- ========================================== -->

                    <div class="profile-actions">

                        <button type="submit" class="btn-save-profile">
                            Salvar Alterações
                        </button>

                    </div>


                </form>

            <?php endif; ?>


        </div>

    </main>

    <footer class="panel-footer">
        <p>&copy; <?= date('Y') ?> MB Studio. Painel do Profissional.</p>
    </footer>


    <!-- Preview da Foto de Perfil -->
    <script>
    var inputFoto = document.getElementById('foto');

    if (inputFoto) {
        inputFoto.addEventListener('change', function(e) {

            var file = e.target.files[0];
            if (!file) return;

            // Mostrar nome do arquivo selecionado
            document.getElementById('fotoNome').textContent = file.name;

            // Preview visual (apenas UX — validação real é no PHP)
            var reader = new FileReader();

            reader.onload = function(event) {
                var img = document.getElementById('fotoPreview');
                img.src = event.target.result;
                img.style.display = 'block';

                // Ocultar placeholder se existir
                var placeholder = document.getElementById('fotoPlaceholder');
                if (placeholder) placeholder.style.display = 'none';
            };

            reader.readAsDataURL(file);

        });
    }
    </script>

</body>

</html>
