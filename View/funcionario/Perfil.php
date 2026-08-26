<?php


require_once __DIR__ . "/../../middleware/FuncionarioAuth.php";

require_once __DIR__ . "/../../config/conexao.php";

require_once __DIR__ . "/../../model/Employee.php";



$userId = $_SESSION['User_id'];

$employeeModel = new Employee($pdo);

$dadosFuncionario = $employeeModel->buscarPorUserId($userId);


?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Meu Perfil | MB Studio</title>

    <link rel="stylesheet" href="../../assets/css/global.css">

    <link rel="stylesheet" href="../../assets/css/funcionario.css">

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


            <!-- INTRODUÇÃO DA PÁGINA -->

            <section class="employee-profile-intro">

                <span class="golden-accent-detail"></span>

                <h1 class="employee-profile-title">Meu Perfil</h1>

                <p class="employee-profile-subtitle">
                    Atualize suas informações profissionais e seus dados de acesso.
                </p>

            </section>




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
                >


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


</body>

</html>
