<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";
require_once "../../model/Dashboard.php";

Session::iniciar();

// Proteção Admin
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'A') {
    header("Location: ../../Index.php");
    exit;
}

$dashboard = new Dashboard($pdo);

$usuarios = $dashboard->totalUsuarios();
$clientes = $dashboard->totalClientes();
$funcionarios = $dashboard->totalFuncionarios();
$administradores = $dashboard->totalAdministradores();
$servicos = $dashboard->totalServicos();
$agendamentos = $dashboard->totalAgendamentos();

$adminName = $_SESSION['User_name'] ?? 'Administrador';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo | MB Studio</title>

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/admin/dashboard.css">
</head>

<body>
    <!-- Cabeçalho Global MB Studio -->
    <?php include '../components/Header.php'; ?>

    <main class="admin-dashboard-wrapper">

        <!-- Card Superior de Boas-Vindas -->
        <div class="admin-welcome-card">
            <div>
                <div class="welcome-badge">
                    <span>✦ Painel de Controle ✦</span>
                </div>
                <h1 class="welcome-title">
                    Olá, <span class="cursiva-gold"><?= htmlspecialchars($adminName) ?></span>!
                </h1>
                <p class="welcome-subtitle">
                    Bem-vindo(a) à central administrativa do <strong>MB Studio</strong>. Acompanhe os números do salão e gerencie usuários, profissionais, catálogo de serviços e relatórios de forma simples e intuitiva.
                </p>
            </div>

            <div>
                <a href="../../controller/Logout.php" class="btn-logout-top" title="Encerrar Sessão">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Sair do Sistema</span>
                </a>
            </div>
        </div>

        <!-- SEÇÃO 1: ESTATÍSTICAS E MÉTRICAS DO SALÃO -->
        <section>
            <div class="section-header-admin">
                <h2 class="section-title-admin">
                    <span>✦</span> Indicadores do Estúdio
                </h2>
                <p class="section-desc-admin">Estatísticas gerais atualizadas em tempo real sobre o movimento e cadastros do salão.</p>
            </div>

            <div class="metrics-grid-six">
                <!-- 1. Usuários -->
                <div class="metric-card-white">
                    <div class="metric-card-header">
                        <div class="metric-icon-wrap">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <span class="metric-tag-label">Geral</span>
                    </div>
                    <div>
                        <div class="metric-title">Total de Usuários</div>
                        <div class="metric-number"><?= htmlspecialchars($usuarios) ?></div>
                        <p class="metric-description">Contas cadastradas no sistema do MB Studio.</p>
                    </div>
                </div>

                <!-- 2. Clientes -->
                <div class="metric-card-white">
                    <div class="metric-card-header">
                        <div class="metric-icon-wrap">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <span class="metric-tag-label">Clientes</span>
                    </div>
                    <div>
                        <div class="metric-title">Clientes Registrados</div>
                        <div class="metric-number"><?= htmlspecialchars($clientes) ?></div>
                        <p class="metric-description">Clientes cadastrados e aptos para agendar horários.</p>
                    </div>
                </div>

                <!-- 3. Funcionários -->
                <div class="metric-card-white">
                    <div class="metric-card-header">
                        <div class="metric-icon-wrap">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="6" cy="6" r="3"></circle>
                                <circle cx="6" cy="18" r="3"></circle>
                                <line x1="20" y1="4" x2="8.12" y2="15.88"></line>
                                <line x1="14.47" y1="14.48" x2="20" y2="20"></line>
                                <line x1="8.12" y1="8.12" x2="12" y2="12"></line>
                            </svg>
                        </div>
                        <span class="metric-tag-label">Equipe</span>
                    </div>
                    <div>
                        <div class="metric-title">Funcionários / Especialistas</div>
                        <div class="metric-number"><?= htmlspecialchars($funcionarios) ?></div>
                        <p class="metric-description">Profissionais da beleza atuantes no estúdio.</p>
                    </div>
                </div>

                <!-- 4. Administradores -->
                <div class="metric-card-white">
                    <div class="metric-card-header">
                        <div class="metric-icon-wrap">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                        </div>
                        <span class="metric-tag-label">Gestão</span>
                    </div>
                    <div>
                        <div class="metric-title">Administradores</div>
                        <div class="metric-number"><?= htmlspecialchars($administradores) ?></div>
                        <p class="metric-description">Gestores com acesso total ao painel de controle.</p>
                    </div>
                </div>

                <!-- 5. Serviços -->
                <div class="metric-card-white">
                    <div class="metric-card-header">
                        <div class="metric-icon-wrap">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                        </div>
                        <span class="metric-tag-label">Catálogo</span>
                    </div>
                    <div>
                        <div class="metric-title">Serviços Oferecidos</div>
                        <div class="metric-number"><?= htmlspecialchars($servicos) ?></div>
                        <p class="metric-description">Procedimentos de beleza ativos no catálogo.</p>
                    </div>
                </div>

                <!-- 6. Agendamentos -->
                <div class="metric-card-white">
                    <div class="metric-card-header">
                        <div class="metric-icon-wrap">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <span class="metric-tag-label">Agenda</span>
                    </div>
                    <div>
                        <div class="metric-title">Agendamentos Totais</div>
                        <div class="metric-number"><?= htmlspecialchars($agendamentos) ?></div>
                        <p class="metric-description">Agendamentos registrados no histórico do salão.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- SEÇÃO 2: ÁREA DE GERENCIAMENTO (ATALHOS INTUITIVOS) -->
        <section>
            <div class="section-header-admin">
                <h2 class="section-title-admin">
                    <span>✦</span> Área de Gerenciamento
                </h2>
                <p class="section-desc-admin">Selecione uma área abaixo para cadastrar, editar ou gerenciar os dados do salão.</p>
            </div>

            <div class="management-grid-four" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                <!-- Card 0: Atendimentos/Agenda -->
                <a href="Agenda.php" class="action-card-link">
                    <div class="action-icon-circle">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <h3 class="action-card-title">
                        <span>Agenda Global</span>
                        <span class="action-arrow">→</span>
                    </h3>
                    <p class="action-card-desc">
                        Acompanhe o fluxo de todos os atendimentos do salão e gerencie finalizações de pagamento pendentes.
                    </p>
                    <span class="action-card-btn">Ver Atendimentos</span>
                </a>

                <!-- Card 1: Usuários -->
                <a href="Usuarios.php" class="action-card-link">
                    <div class="action-icon-circle">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3 class="action-card-title">
                        <span>Usuários</span>
                        <span class="action-arrow">→</span>
                    </h3>
                    <p class="action-card-desc">
                        Consulte todos os cadastros do sistema, altere permissões de acesso e visualize clientes e administradores.
                    </p>
                    <span class="action-card-btn">Gerenciar Usuários</span>
                </a>

                <!-- Card 2: Funcionários -->
                <a href="Funcionarios.php" class="action-card-link">
                    <div class="action-icon-circle">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="6" cy="6" r="3"></circle>
                            <circle cx="6" cy="18" r="3"></circle>
                            <line x1="20" y1="4" x2="8.12" y2="15.88"></line>
                            <line x1="14.47" y1="14.48" x2="20" y2="20"></line>
                            <line x1="8.12" y1="8.12" x2="12" y2="12"></line>
                        </svg>
                    </div>
                    <h3 class="action-card-title">
                        <span>Funcionários</span>
                        <span class="action-arrow">→</span>
                    </h3>
                    <p class="action-card-desc">
                        Cadastre novos profissionais, vincule especialidades, fotos de perfil e gerencie a equipe do MB Studio.
                    </p>
                    <span class="action-card-btn">Gerenciar Equipe</span>
                </a>

                <!-- Card 3: Serviços -->
                <a href="Servicos.php" class="action-card-link">
                    <div class="action-icon-circle">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                    </div>
                    <h3 class="action-card-title">
                        <span>Serviços</span>
                        <span class="action-arrow">→</span>
                    </h3>
                    <p class="action-card-desc">
                        Cadastre novos procedimentos de beleza, edite valores, duração dos atendimentos e fotos demonstrativas.
                    </p>
                    <span class="action-card-btn">Gerenciar Serviços</span>
                </a>

                <!-- Card 4: Relatórios -->
                <a href="Relatorios.php" class="action-card-link">
                    <div class="action-icon-circle">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                    </div>
                    <h3 class="action-card-title">
                        <span>Relatórios</span>
                        <span class="action-arrow">→</span>
                    </h3>
                    <p class="action-card-desc">
                        Acompanhe gráficos e relatórios detalhados sobre agendamentos, demanda por serviços e faturamento do salão.
                    </p>
                    <span class="action-card-btn">Ver Relatórios</span>
                </a>
            </div>
        </section>

    </main>

    <footer class="admin-panel-footer">
        <p>&copy; <?= date('Y') ?> MB Studio. Painel Administrativo de Gestão.</p>
    </footer>
</body>

</html>