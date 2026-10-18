<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'C') {
    header("Location: ../../Index.php");
    exit;
}

$userId = $_SESSION['User_id'];

// Obter Cli_id
$stmtCli = $pdo->prepare("SELECT Cli_id FROM clients WHERE User_id = ?");
$stmtCli->execute([$userId]);
$cli_id = $stmtCli->fetchColumn();

$agendamentos = [];
if ($cli_id) {
    // Buscar agendamentos do cliente
    $sql = "SELECT a.Appo_id, a.Appo_date, a.Appo_start, a.Appo_status, 
                   s.Ser_name, u.User_name as Funcionario
            FROM appointments a
            JOIN services s ON a.Ser_id = s.Ser_id
            JOIN employees e ON a.Emp_id = e.Emp_id
            JOIN users u ON e.User_id = u.User_id
            WHERE a.Cli_id = ?
            ORDER BY a.Appo_date DESC, a.Appo_start DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cli_id]);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos | MB Studio</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/cliente/meusagendamentos.css">
</head>
<body class="cliente-page">

    <!-- Cabeçalho Oficial do Projeto -->
    <?php include '../components/Header.php'; ?>

    <main class="appointments-page-wrapper">
        <div class="appointments-container">
            <div class="appointments-header-box">
                <span class="tag-subtitulo" style="font-size: 12px; font-weight: 700; color: var(--gold-dark); letter-spacing: 2px; text-transform: uppercase;">✦ Minha Conta ✦</span>
                <h1 class="appointments-title">Meus Agendamentos</h1>
                <p class="appointments-desc">Acompanhe o status das suas solicitações de atendimento no MB Studio em tempo real.</p>
            </div>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="alert-feedback success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>
                        <?php
                        if ($_GET['sucesso'] == 'agendamento_cancelado') {
                            echo "Seu agendamento foi cancelado com sucesso.";
                        } else {
                            echo "Agendamento solicitado com sucesso! Aguarde a confirmação do profissional.";
                        }
                        ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['erro'])): ?>
                <div class="alert-feedback error">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span>Ocorreu um erro ao processar sua solicitação. Tente novamente.</span>
                </div>
            <?php endif; ?>

            <?php if (count($agendamentos) > 0): ?>
                <div class="table-container">
                    <table class="appointments-table table-mini">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Procedimento</th>
                                <th>Especialista</th>
                                <th>Status</th>
                                <th style="text-align: center;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agendamentos as $a): 
                                $statusClass = 'status-' . explode(' ', $a['Appo_status'])[0]; 
                            ?>
                            <tr>
                                <td><strong><?= date('d/m/Y', strtotime($a['Appo_date'])) ?></strong></td>
                                <td><?= date('H:i', strtotime($a['Appo_start'])) ?></td>
                                <td><?= htmlspecialchars($a['Ser_name']) ?></td>
                                <td><?= htmlspecialchars($a['Funcionario']) ?></td>
                                <td><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($a['Appo_status']) ?></span></td>
                                <td style="text-align: center;">
                                    <?php if ($a['Appo_status'] == 'Pendente' || $a['Appo_status'] == 'Confirmado'): ?>
                                        <form action="../../controller/CancelarAgendamento.php" method="POST" style="margin: 0; display: inline-block;" onsubmit="return confirm('Tem certeza que deseja cancelar este agendamento?');">
                                            <input type="hidden" name="appo_id" value="<?= $a['Appo_id'] ?>">
                                            <input type="hidden" name="cancel_reason" value="Cancelado pelo Cliente via painel">
                                            <button type="submit" class="btn-cancel-appo">
                                                Cancelar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #a1a1aa; font-size: 13px;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-appo-card">
                    <div class="empty-appo-icon">📅</div>
                    <h3 class="empty-appo-title">Nenhum agendamento encontrado</h3>
                    <p class="empty-appo-desc">Você ainda não possui horários marcados no estúdio. Escolha um serviço e agende seu horário com nossos especialistas.</p>
                    <a href="Servicos.php" class="btn-gold">
                        Agendar Primeiro Horário
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Global de Autenticação -->
    <?php include '../components/LoginModal.php'; ?>

</body>
</html>
