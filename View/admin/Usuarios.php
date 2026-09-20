<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";
require_once "../../model/User.php";

Session::iniciar();

// Proteção Admin
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'A') {
    header("Location: ../../Index.php");
    exit;
}

$userModel = new User($pdo);
$usuarios = $userModel->listarTodos();

// Cálculos para os indicadores de resumo
$totalUsuarios = count($usuarios);
$totalClientes = 0;
$totalFuncionarios = 0;
$totalAdmins = 0;
$totalAtivos = 0;
$totalInativos = 0;

foreach ($usuarios as $u) {
    if ($u['User_perm'] == 'C') $totalClientes++;
    elseif ($u['User_perm'] == 'F') $totalFuncionarios++;
    elseif ($u['User_perm'] == 'A') $totalAdmins++;
    
    if ($u['User_active'] == 1) $totalAtivos++;
    else $totalInativos++;
}

// Função auxiliar para gerar iniciais do nome para o avatar
function getIniciais($nome) {
    $partes = preg_split('/\s+/', trim($nome));
    $iniciais = '';
    if (count($partes) >= 2) {
        $iniciais = mb_substr($partes[0], 0, 1) . mb_substr(end($partes), 0, 1);
    } elseif (count($partes) === 1 && !empty($partes[0])) {
        $iniciais = mb_substr($partes[0], 0, 2);
    }
    return strtoupper($iniciais ?: 'US');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Usuários | MB Studio</title>

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/admin/usuarios.css">
</head>
<body>

    <!-- Cabeçalho Oficial MB Studio -->
    <?php include '../components/Header.php'; ?>

    <main class="admin-users-wrapper">

        <!-- Barra Superior de Navegação -->
        <div class="top-nav-bar">
            <a href="Dashboard.php" class="btn-back-dashboard" title="Retornar ao painel principal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Voltar ao Dashboard</span>
            </a>

            <div class="users-count-tag" id="summary-counter">
                Total: <strong><?= $totalUsuarios ?></strong> usuário(s)
            </div>
        </div>

        <!-- Banner de Boas-Vindas e Título -->
        <div class="users-header-card">
            <div>
                <div class="header-tag-pill">
                    <span>✦ Controle de Acessos & Segurança ✦</span>
                </div>
                <h1 class="users-main-title">Gerenciamento de Usuários</h1>
                <p class="users-subtitle">
                    Visualize e gerencie todos os clientes, colaboradores da equipe e administradores do <strong>MB Studio</strong>. Altere permissões ou ative/desative contas em tempo real.
                </p>
            </div>
        </div>

        <!-- Cards de Resumo Rápido (KPIs) -->
        <div class="kpi-summary-grid">
            <!-- 1. Total Geral -->
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
                    <div class="kpi-info-val"><?= $totalUsuarios ?></div>
                    <div class="kpi-info-lbl">Total Geral</div>
                </div>
            </div>

            <!-- 2. Clientes -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val"><?= $totalClientes ?></div>
                    <div class="kpi-info-lbl">Clientes</div>
                </div>
            </div>

            <!-- 3. Funcionários -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="6" cy="6" r="3"></circle>
                        <circle cx="6" cy="18" r="3"></circle>
                        <line x1="20" y1="4" x2="8.12" y2="15.88"></line>
                        <line x1="14.47" y1="14.48" x2="20" y2="20"></line>
                        <line x1="8.12" y1="8.12" x2="12" y2="12"></line>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val"><?= $totalFuncionarios ?></div>
                    <div class="kpi-info-lbl">Equipe / Staff</div>
                </div>
            </div>

            <!-- 4. Administradores -->
            <div class="kpi-card">
                <div class="kpi-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <div>
                    <div class="kpi-info-val"><?= $totalAdmins ?></div>
                    <div class="kpi-info-lbl">Administradores</div>
                </div>
            </div>

            <!-- 5. Status Ativo / Inativo -->
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
                    id="searchUserInput" 
                    class="search-input-field" 
                    placeholder="Pesquisar por nome, e-mail ou código ID..."
                    onkeyup="filterUsersTable()"
                >
            </div>

            <div class="filters-group">
                <!-- Filtro por Permissão -->
                <select id="permFilter" class="filter-select" onchange="filterUsersTable()">
                    <option value="ALL">Todas as Permissões</option>
                    <option value="C">👤 Apenas Clientes (C)</option>
                    <option value="F">✂️ Apenas Funcionários (F)</option>
                    <option value="A">👑 Apenas Administradores (A)</option>
                </select>

                <!-- Filtro por Status -->
                <select id="statusFilter" class="filter-select" onchange="filterUsersTable()">
                    <option value="ALL">Todos os Status</option>
                    <option value="1">🟢 Apenas Ativos</option>
                    <option value="0">🔴 Apenas Inativos</option>
                </select>
            </div>
        </div>

        <!-- Card da Tabela de Usuários -->
        <div class="table-card-container">
            <?php if(count($usuarios) > 0): ?>
                <div class="table-responsive-scroll">
                    <table class="luxury-users-table" id="usersTable">
                        <thead>
                            <tr>
                                <th style="width: 70px;">ID</th>
                                <th>Usuário & E-mail</th>
                                <th style="width: 130px;">Status</th>
                                <th style="width: 250px;">Permissão de Acesso</th>
                                <th style="width: 230px;">Ações de Controle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($usuarios as $u): 
                                $isSelf = ($u['User_id'] == $_SESSION['User_id']);
                                $iniciais = getIniciais($u['User_name']);
                            ?>
                                <tr 
                                    class="user-table-row <?= $isSelf ? 'row-self-account' : '' ?>"
                                    data-id="<?= $u['User_id'] ?>"
                                    data-name="<?= htmlspecialchars(mb_strtolower($u['User_name'])) ?>"
                                    data-email="<?= htmlspecialchars(mb_strtolower($u['User_email'])) ?>"
                                    data-perm="<?= $u['User_perm'] ?>"
                                    data-active="<?= $u['User_active'] ?>"
                                >
                                    <!-- ID -->
                                    <td>
                                        <span class="id-badge">#<?= $u['User_id'] ?></span>
                                    </td>

                                    <!-- Nome & E-mail com Avatar -->
                                    <td>
                                        <div class="user-identity-cell">
                                            <div class="user-avatar-circle" title="<?= htmlspecialchars($u['User_name']) ?>">
                                                <?= $iniciais ?>
                                            </div>
                                            <div>
                                                <div class="user-name-text">
                                                    <?= htmlspecialchars($u['User_name']) ?>
                                                    <?php if($isSelf): ?>
                                                        <span style="font-size: 11px; color: var(--gold-dark); background: rgba(184, 134, 11, 0.12); padding: 2px 6px; border-radius: 4px; margin-left: 5px; font-weight: 700;">(Você)</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="user-email-text">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                                        <polyline points="22,6 12,13 2,6"></polyline>
                                                    </svg>
                                                    <?= htmlspecialchars($u['User_email']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td>
                                        <?php if($u['User_active'] == 1): ?>
                                            <span class="status-pill active">
                                                <span class="status-dot"></span> Ativo
                                            </span>
                                        <?php else: ?>
                                            <span class="status-pill inactive">
                                                <span class="status-dot"></span> Inativo
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Permissão (Formulário Original Preservado) -->
                                    <td>
                                        <form action="../../controller/AlterarPermissao.php" method="POST" class="perm-form-container">
                                            <input type="hidden" name="User_id" value="<?= $u['User_id'] ?>">
                                            <select name="User_perm" class="select-luxury-perm" aria-label="Permissão do Usuário">
                                                <option value="C" <?= $u['User_perm'] == 'C' ? 'selected' : '' ?>>👤 Cliente (C)</option>
                                                <option value="F" <?= $u['User_perm'] == 'F' ? 'selected' : '' ?>>✂️ Funcionário (F)</option>
                                                <option value="A" <?= $u['User_perm'] == 'A' ? 'selected' : '' ?>>👑 Admin (A)</option>
                                            </select>
                                            <button type="submit" class="btn-luxury-save" title="Salvar alteração de permissão">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                                Salvar
                                            </button>
                                        </form>
                                    </td>

                                    <!-- Ações (Alternar Status / Excluir) -->
                                    <td>
                                        <div class="actions-cell-wrap">
                                            <?php if(!$isSelf): ?>
                                                <!-- Alternar Status (Ativar / Desativar) -->
                                                <form action="../../controller/AlternarStatusUsuario.php" method="POST" style="margin: 0;">
                                                    <input type="hidden" name="User_id" value="<?= $u['User_id'] ?>">
                                                    <input type="hidden" name="User_active" value="<?= $u['User_active'] == 1 ? 0 : 1 ?>">
                                                    <?php if($u['User_active'] == 1): ?>
                                                        <button type="submit" class="btn-action-luxury btn-toggle-off" title="Desativar este usuário">
                                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <circle cx="12" cy="12" r="10"></circle>
                                                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                                                <line x1="9" y1="9" x2="15" y2="15"></line>
                                                            </svg>
                                                            Desativar
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" class="btn-action-luxury btn-toggle-on" title="Reativar este usuário">
                                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                                            </svg>
                                                            Ativar
                                                        </button>
                                                    <?php endif; ?>
                                                </form>

                                                <!-- Excluir Usuário Fisicamente -->
                                                <form action="../../controller/ExcluirUsuario.php" method="POST" onsubmit="return confirm('Atenção: Tem certeza que deseja excluir fisicamente o usuário \'<?= htmlspecialchars(addslashes($u['User_name'])) ?>\'? Esta ação é irreversível.');" style="margin: 0;">
                                                    <input type="hidden" name="User_id" value="<?= $u['User_id'] ?>">
                                                    <button type="submit" class="btn-action-luxury btn-delete-luxury" title="Excluir usuário permanentemente">
                                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <polyline points="3 6 5 6 21 6"></polyline>
                                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                                        </svg>
                                                        Excluir
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="self-admin-badge" title="Você não pode desativar ou excluir a própria conta logada por segurança.">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                                    </svg>
                                                    Sua Conta (Protegida)
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Estado quando a pesquisa em tempo real não encontra resultados -->
                <div id="noSearchResultsMessage" class="empty-state-wrap" style="display: none;">
                    <div class="empty-icon-circle">🔍</div>
                    <h3 class="empty-title">Nenhum usuário encontrado</h3>
                    <p style="margin: 0; font-size: 14px;">Nenhum registro corresponde aos filtros ou ao termo pesquisado.</p>
                </div>

            <?php else: ?>
                <!-- Estado quando a tabela no banco está completamente vazia -->
                <div class="empty-state-wrap">
                    <div class="empty-icon-circle">👤</div>
                    <h3 class="empty-title">Nenhum usuário encontrado</h3>
                    <p style="margin: 0; font-size: 14px;">Ainda não existem usuários cadastrados no banco de dados.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Rodapé do Painel de Usuários -->
        <div class="users-page-footer">
            <div>
                <strong>MB Studio Admin</strong> — Gestão de Usuários & Acessos
            </div>
            <div>
                © <?= date('Y') ?> Todos os direitos reservados.
            </div>
        </div>

    </main>

    <!-- Script de Filtro Instantâneo em Tempo Real (Vanilla JS) -->
    <script>
        function filterUsersTable() {
            const searchInput = document.getElementById('searchUserInput').value.toLowerCase().trim();
            const permFilter = document.getElementById('permFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('.user-table-row');
            const noResultsMsg = document.getElementById('noSearchResultsMessage');
            
            let visibleCount = 0;

            rows.forEach(row => {
                const id = row.getAttribute('data-id') || '';
                const name = row.getAttribute('data-name') || '';
                const email = row.getAttribute('data-email') || '';
                const perm = row.getAttribute('data-perm') || '';
                const active = row.getAttribute('data-active') || '';

                const matchesSearch = (!searchInput) || 
                                      name.includes(searchInput) || 
                                      email.includes(searchInput) || 
                                      id.includes(searchInput);

                const matchesPerm = (permFilter === 'ALL') || (perm === permFilter);
                const matchesStatus = (statusFilter === 'ALL') || (active === statusFilter);

                if (matchesSearch && matchesPerm && matchesStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Atualizar o contador no topo
            const counterElement = document.getElementById('summary-counter');
            if (counterElement) {
                counterElement.innerHTML = `Exibindo: <strong>${visibleCount}</strong> de <strong>${rows.length}</strong> usuário(s)`;
            }

            // Exibir mensagem de nenhum resultado se visível for zero
            if (noResultsMsg) {
                noResultsMsg.style.display = (visibleCount === 0 && rows.length > 0) ? 'block' : 'none';
            }
        }
    </script>
</body>
</html>
