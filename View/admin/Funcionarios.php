<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";
require_once "../../model/Employee.php";

Session::iniciar();

// Proteção Admin
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'A') {
    header("Location: ../../Index.php");
    exit;
}

$employeeModel = new Employee($pdo);
$funcionarios = $employeeModel->listarEquipeAdmin();

// Cálculos rápidos para métricas
$totalFuncionarios = count($funcionarios);
$totalAtivos = 0;
$totalInativos = 0;
$totalHorariosGeral = 0;
$totalComServicos = 0;

foreach ($funcionarios as $f) {
    if ($f['User_active'] == 1) $totalAtivos++;
    else $totalInativos++;

    $totalHorariosGeral += intval($f['total_horarios'] ?? 0);
    if (!empty($f['servicos_atribuidos'])) {
        $totalComServicos++;
    }
}

// Função auxiliar para gerar iniciais do nome para o avatar quando não houver foto
function getIniciais($nome) {
    $partes = preg_split('/\s+/', trim($nome));
    $iniciais = '';
    if (count($partes) >= 2) {
        $iniciais = mb_substr($partes[0], 0, 1) . mb_substr(end($partes), 0, 1);
    } elseif (count($partes) === 1 && !empty($partes[0])) {
        $iniciais = mb_substr($partes[0], 0, 2);
    }
    return strtoupper($iniciais ?: 'PR');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Equipe | MB Studio Admin</title>

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/admin/funcionarios.css">
</head>
<body>

    <!-- Cabeçalho Oficial MB Studio -->
    <?php include '../components/Header.php'; ?>

    <main class="admin-team-wrapper">

        <!-- Barra Superior de Navegação -->
        <div class="top-nav-bar">
            <a href="Dashboard.php" class="btn-back-dashboard" title="Retornar ao painel principal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Voltar ao Dashboard</span>
            </a>

            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <a href="Servicos.php" class="btn-manage-services-top" title="Gerenciar e vincular serviços">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    <span>Atribuir Serviços</span>
                </a>
                <div class="team-count-tag" id="summary-counter">
                    Total: <strong><?= $totalFuncionarios ?></strong> profissional(is)
                </div>
            </div>
        </div>

        <!-- Banner de Título e Boas-Vindas -->
        <div class="team-header-card">
            <div>
                <div class="header-tag-pill">
                    <span>✦ Gestão de Especialistas & Staff ✦</span>
                </div>
                <h1 class="team-main-title">Gestão de Equipe (Funcionários)</h1>
                <p class="team-subtitle">
                    Acompanhe o quadro de profissionais do <strong>MB Studio</strong>, verifique especialidades, disponibilidade de horários na agenda e gerencie atribuições de serviços em tempo real.
                </p>
            </div>
        </div>

        <!-- Cards de Resumo (KPIs) -->
        <div class="kpi-summary-grid">
            <!-- 1. Total de Especialistas -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val"><?= $totalFuncionarios ?></div>
                    <div class="kpi-info-lbl">Profissionais</div>
                </div>
            </div>

            <!-- 2. Status Ativo / Inativo -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap" style="background: rgba(46, 204, 113, 0.1); border-color: rgba(46, 204, 113, 0.3); color: #27ae60;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val" style="font-size: 20px;">
                        <span style="color: #27ae60;"><?= $totalAtivos ?></span> <span style="font-size: 15px; color: #999;">/</span> <span style="color: #c0392b;"><?= $totalInativos ?></span>
                    </div>
                    <div class="kpi-info-lbl">Ativos / Inativos</div>
                </div>
            </div>

            <!-- 3. Com Serviços Vinculados -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val"><?= $totalComServicos ?></div>
                    <div class="kpi-info-lbl">Com Serviços</div>
                </div>
            </div>

            <!-- 4. Total de Horários na Agenda -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val"><?= $totalHorariosGeral ?></div>
                    <div class="kpi-info-lbl">Horários Totais</div>
                </div>
            </div>
        </div>

        <!-- Barra Interativa de Busca e Filtros -->
        <div class="controls-filter-bar">
            <div class="search-box-wrap">
                <svg class="search-icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input 
                    type="text" 
                    id="searchTeamInput" 
                    class="search-input-field" 
                    placeholder="Pesquisar por nome, especialidade ou serviço..."
                    onkeyup="filterTeamGrid()"
                >
            </div>

            <div class="filters-group">
                <!-- Filtro por Status -->
                <select id="statusTeamFilter" class="filter-select" onchange="filterTeamGrid()">
                    <option value="ALL">Todos os Status</option>
                    <option value="1">🟢 Apenas Ativos</option>
                    <option value="0">🔴 Apenas Inativos</option>
                </select>

                <!-- Filtro por Serviços Atribuídos -->
                <select id="servicesTeamFilter" class="filter-select" onchange="filterTeamGrid()">
                    <option value="ALL">Todos os Serviços</option>
                    <option value="COM">Com Serviços Atribuídos</option>
                    <option value="SEM">Sem Serviços Atribuídos</option>
                </select>
            </div>
        </div>

        <!-- Grid de Cards da Equipe -->
        <?php if(count($funcionarios) > 0): ?>
            <div class="team-luxury-grid" id="teamCardsGrid">
                <?php foreach($funcionarios as $f): 
                    $hasPhoto = !empty($f['Emp_photo']);
                    $iniciais = getIniciais($f['User_name']);
                    $temServicos = !empty($f['servicos_atribuidos']);
                ?>
                    <div 
                        class="team-card-luxury team-card-item"
                        data-name="<?= htmlspecialchars(mb_strtolower($f['User_name'])) ?>"
                        data-specialty="<?= htmlspecialchars(mb_strtolower($f['Emp_specialty'] ?: '')) ?>"
                        data-services="<?= htmlspecialchars(mb_strtolower($f['servicos_atribuidos'] ?: '')) ?>"
                        data-active="<?= $f['User_active'] ?>"
                        data-has-services="<?= $temServicos ? 'COM' : 'SEM' ?>"
                    >
                        
                        <!-- Topo do Card: Foto, Nome e Especialidade -->
                        <div class="team-card-top">
                            <div class="team-avatar-frame">
                                <?php if($hasPhoto): ?>
                                    <img src="../../<?= htmlspecialchars($f['Emp_photo']) ?>" alt="Foto de <?= htmlspecialchars($f['User_name']) ?>" class="team-avatar-img">
                                <?php else: ?>
                                    <div class="team-avatar-initials">
                                        <?= $iniciais ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="team-info-header">
                                <h3 class="team-name-title"><?= htmlspecialchars($f['User_name']) ?></h3>
                                <span class="team-specialty-badge">
                                    <?= htmlspecialchars($f['Emp_specialty'] ?: 'Profissional da Beleza') ?>
                                </span>
                            </div>
                        </div>

                        <!-- Corpo do Card: Informações Detalhadas -->
                        <div class="team-card-body">
                            
                            <!-- Status -->
                            <div class="card-info-item">
                                <span class="card-info-label">Status da Conta</span>
                                <?php if($f['User_active'] == 1): ?>
                                    <span class="status-pill active">
                                        <span class="status-dot"></span> Ativo no Sistema
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill inactive">
                                        <span class="status-dot"></span> Inativo
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Agenda -->
                            <div class="card-info-item">
                                <span class="card-info-label">Agenda do Profissional</span>
                                <span class="badge-agenda">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <?= $f['total_horarios'] ?> horários cadastrados
                                </span>
                            </div>

                            <!-- Serviços Atribuídos -->
                            <div class="card-info-item">
                                <span class="card-info-label">Serviços Atribuídos</span>
                                <div class="services-tag-list">
                                    <?= htmlspecialchars($f['servicos_atribuidos'] ?: 'Nenhum serviço atribuído no momento.') ?>
                                </div>
                            </div>

                            <!-- Bio / Descrição Curta -->
                            <?php if(!empty($f['Emp_bio'])): ?>
                                <div class="card-info-item" style="margin-top: 4px;">
                                    <div class="bio-quote-box">
                                        "<?= htmlspecialchars(substr($f['Emp_bio'], 0, 110)) ?><?= strlen($f['Emp_bio']) > 110 ? '...' : '' ?>"
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- Rodapé do Card: Ações -->
                        <div class="team-card-actions">
                            <a href="Servicos.php" class="btn-card-manage" title="Gerenciar catálogo de serviços">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                                Atribuir Serviços
                            </a>

                            <!-- Formulário para remover da equipe (alterar permissão para 'C') -->
                            <form action="../../controller/AlterarPermissao.php" method="POST" onsubmit="return confirm('Tem certeza que deseja rebaixar este funcionário para Cliente? Ele perderá acesso ao painel de equipe.');" style="flex: 1; margin: 0;">
                                <input type="hidden" name="User_id" value="<?= $f['User_id'] ?>">
                                <input type="hidden" name="User_perm" value="C">
                                <button type="submit" class="btn-card-remove" style="width: 100%;" title="Rebaixar permissão para Cliente">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="15" y1="9" x2="9" y2="15"></line>
                                        <line x1="9" y1="9" x2="15" y2="15"></line>
                                    </svg>
                                    Remover Equipe
                                </button>
                            </form>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Estado quando a pesquisa em tempo real não encontra resultados -->
            <div id="noTeamResultsMessage" class="empty-state-wrap" style="display: none; margin-top: 20px;">
                <div class="empty-icon-circle">🔍</div>
                <h3 class="empty-title">Nenhum profissional encontrado</h3>
                <p style="margin: 0; font-size: 14px;">Nenhum membro da equipe corresponde aos filtros ou termo pesquisado.</p>
            </div>

        <?php else: ?>
            <!-- Estado quando não há funcionários cadastrados no banco -->
            <div class="empty-state-wrap">
                <div class="empty-icon-circle">✂️</div>
                <h3 class="empty-title">Nenhum membro na equipe</h3>
                <p style="margin: 0; font-size: 14px;">No momento, não há usuários cadastrados com a permissão 'F' (Funcionário).</p>
            </div>
        <?php endif; ?>

        <!-- Rodapé do Painel -->
        <div class="team-page-footer">
            <div>
                <strong>MB Studio Admin</strong> — Gestão de Especialistas & Staff
            </div>
            <div>
                © <?= date('Y') ?> Todos os direitos reservados.
            </div>
        </div>

    </main>

    <!-- Script de Filtro Instantâneo em Tempo Real (Vanilla JS) -->
    <script>
        function filterTeamGrid() {
            const searchInput = document.getElementById('searchTeamInput').value.toLowerCase().trim();
            const statusFilter = document.getElementById('statusTeamFilter').value;
            const servicesFilter = document.getElementById('servicesTeamFilter').value;
            const cards = document.querySelectorAll('.team-card-item');
            const noResultsMsg = document.getElementById('noTeamResultsMessage');
            
            let visibleCount = 0;

            cards.forEach(card => {
                const name = card.getAttribute('data-name') || '';
                const specialty = card.getAttribute('data-specialty') || '';
                const services = card.getAttribute('data-services') || '';
                const active = card.getAttribute('data-active') || '';
                const hasServices = card.getAttribute('data-has-services') || '';

                const matchesSearch = (!searchInput) || 
                                      name.includes(searchInput) || 
                                      specialty.includes(searchInput) || 
                                      services.includes(searchInput);

                const matchesStatus = (statusFilter === 'ALL') || (active === statusFilter);
                const matchesServices = (servicesFilter === 'ALL') || (hasServices === servicesFilter);

                if (matchesSearch && matchesStatus && matchesServices) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Atualizar o contador no topo
            const counterElement = document.getElementById('summary-counter');
            if (counterElement) {
                counterElement.innerHTML = `Exibindo: <strong>${visibleCount}</strong> de <strong>${cards.length}</strong> profissional(is)`;
            }

            // Exibir mensagem de nenhum resultado se visível for zero
            if (noResultsMsg) {
                noResultsMsg.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
            }
        }
    </script>
</body>
</html>
