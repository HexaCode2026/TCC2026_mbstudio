<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";
require_once "../../model/EmployeeBlock.php";

Session::iniciar();

// Apenas Funcionários
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'F') {
    header("Location: ../../Index.php");
    exit;
}

$userId = $_SESSION['User_id'];

// Obter Emp_id
$stmtEmp = $pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
$stmtEmp->execute([$userId]);
$empId = $stmtEmp->fetchColumn();

if (!$empId) {
    echo "Erro: Conta de funcionário não configurada corretamente.";
    exit;
}

// Buscar bloqueios
$blockModel = new EmployeeBlock($pdo);
$bloqueios = $blockModel->listarPorFuncionario($empId);

$globalCssVer = @filemtime(__DIR__ . '/../../assets/css/global.css') ?: '3.0';
$bloqCssVer   = @filemtime(__DIR__ . '/../../assets/css/funcionario/bloqueios.css') ?: '3.0';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pausas e Bloqueios | MB Studio</title>

    <link rel="stylesheet" href="../../assets/css/global.css?v=<?= $globalCssVer ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/funcionario/bloqueios.css?v=<?= $bloqCssVer ?>">
</head>
<body>

    <?php include '../components/Header.php'; ?>
    <?php include '../components/LoginModal.php'; ?>

    <main class="bloqueios-page-wrapper">
        <div class="bloqueios-container">

            <!-- Navegação Superior Operacional -->
            <div class="bloq-top-nav">
                <a href="Home.php" class="btn-back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Voltar ao Início
                </a>

                <div class="bloq-nav-right">
                    <a href="Disponibilidade.php" class="btn-back-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Meus Horários
                    </a>
                    <a href="Agenda.php" class="btn-back-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Minha Agenda
                    </a>
                </div>
            </div>

            <!-- Cabeçalho da Página -->
            <header class="bloqueios-header-box">
                <div class="bloq-badge">
                    <span>✦ Controle de Expediente ✦</span>
                </div>
                <h1 class="bloqueios-title">
                    Minhas Pausas e <span class="cursiva-gold">Exceções</span>
                </h1>
                <p class="bloqueios-desc">
                    Adicione intervalos pontuais onde você não poderá atender clientes (ex: horário de almoço, consulta médica ou compromissos externos).
                </p>
            </header>
            
            <?php if (isset($_GET['sucesso'])): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>Operação realizada com sucesso!</span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['erro'])): ?>
                <div class="alert alert-error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>
                    <?php if ($_GET['erro'] == 'conflito_agendamento'): ?>
                        Você já possui um agendamento marcado neste horário. Cancele-o primeiro antes de adicionar a pausa.
                    <?php elseif ($_GET['erro'] == 'conflito_bloqueio'): ?>
                        Você já cadastrou uma pausa que entra em conflito com este horário.
                    <?php else: ?>
                        Ocorreu um erro na operação. Verifique os dados informados.
                    <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>

            <div class="bloqueios-grid">
                <!-- Formulário de Cadastro de Bloqueio -->
                <section class="form-section">
                    <div class="section-card-head">
                        <div class="section-head-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="16"></line>
                                <line x1="8" y1="12" x2="16" y2="12"></line>
                            </svg>
                        </div>
                        <div>
                            <h3>Nova Pausa / Bloqueio</h3>
                            <p class="section-card-sub">Bloqueie um intervalo específico na sua agenda</p>
                        </div>
                    </div>

                    <form action="../../controller/SalvarBloqueio.php" method="POST">
                        <?= Session::csrfField() ?>
                        <div class="form-group">
                            <label for="block-data">Data da Pausa</label>
                            <input type="date" id="block-data" name="data" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="form-row-times">
                            <div class="form-group">
                                <label for="block-inicio">Hora Inicial</label>
                                <input type="time" id="block-inicio" name="hora_inicio" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="block-fim">Hora Final</label>
                                <input type="time" id="block-fim" name="hora_fim" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="block-motivo">Motivo / Observação (Opcional)</label>
                            <input type="text" id="block-motivo" name="motivo" class="form-control" placeholder="Ex: Almoço, Consulta médica, Compromisso...">
                        </div>

                        <button type="submit" class="btn-submit-bloqueio">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <span>Adicionar Bloqueio</span>
                        </button>
                    </form>
                </section>

                <!-- Listagem de Bloqueios Futuros -->
                <section class="list-section">
                    <div class="list-section-header">
                        <div class="section-card-head" style="margin-bottom: 0; padding-bottom: 0; border-bottom: none;">
                            <div class="section-head-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </div>
                            <div>
                                <h3>Bloqueios Cadastrados</h3>
                                <p class="section-card-sub">Intervalos ativos que impedem novos agendamentos</p>
                            </div>
                        </div>

                        <span class="bloq-count-pill">
                            <?= count($bloqueios) ?> registro(s)
                        </span>
                    </div>
                    
                    <?php if (count($bloqueios) > 0): ?>
                        <div class="table-container">
                            <table class="table-mini">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Período Bloqueado</th>
                                        <th>Motivo</th>
                                        <th style="text-align: center;">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bloqueios as $b): ?>
                                    <tr>
                                        <td>
                                            <strong class="bloq-date-text"><?= date('d/m/Y', strtotime($b['Block_date'])) ?></strong>
                                        </td>
                                        <td>
                                            <span class="bloq-time-pill">
                                                <?= date('H:i', strtotime($b['Block_start'])) ?> às <?= date('H:i', strtotime($b['Block_end'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($b['Block_reason'])): ?>
                                                <span class="bloq-reason-badge"><?= htmlspecialchars($b['Block_reason']) ?></span>
                                            <?php else: ?>
                                                <span class="bloq-reason-empty">Sem motivo informado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <form action="../../controller/ExcluirBloqueio.php" method="POST" style="display: inline-block; margin: 0;" onsubmit="return confirm('Tem certeza que deseja remover esta pausa?');">
                                                <input type="hidden" name="id" value="<?= (int)$b['Block_id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::gerarTokenCsrf()) ?>">
                                                <button type="submit" class="btn-danger">
                                                    ✕ Remover
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-bloq-card">
                            <div class="empty-bloq-icon">☕</div>
                            <h4>Nenhuma pausa programada</h4>
                            <p>Você não possui pausas ou bloqueios cadastrados para os próximos dias. Utilize o formulário ao lado sempre que precisar reservar um intervalo.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

        </div>
    </main>

</body>
</html>
