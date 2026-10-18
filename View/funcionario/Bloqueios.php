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

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pausas e Bloqueios | MB Studio</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/funcionario/bloqueios.css">
</head>
<body>

    <?php include '../components/Header.php'; ?>

    <main class="bloqueios-page-wrapper">
        <div class="bloqueios-container">
            
            <header class="bloqueios-header-box">
                <h1 class="bloqueios-title">Minhas Pausas e Exceções</h1>
                <p class="bloqueios-desc">Adicione intervalos pontuais onde você não poderá atender clientes (ex: almoço, consulta médica, compromissos pessoais).</p>
            </header>
            
            <?php if (isset($_GET['sucesso'])): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>Operação realizada com sucesso!</span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['erro'])): ?>
                <div class="alert alert-error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
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
                    <h3>Nova Pausa / Bloqueio</h3>
                    <form action="../../controller/SalvarBloqueio.php" method="POST">
                        <div class="form-group">
                            <label for="block-data">Data:</label>
                            <input type="date" id="block-data" name="data" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label for="block-inicio">Hora Inicial:</label>
                            <input type="time" id="block-inicio" name="hora_inicio" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="block-fim">Hora Final:</label>
                            <input type="time" id="block-fim" name="hora_fim" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="block-motivo">Motivo (Opcional):</label>
                            <input type="text" id="block-motivo" name="motivo" class="form-control" placeholder="Ex: Almoço, Médico...">
                        </div>
                        <button type="submit" class="btn-submit-bloqueio">Adicionar Bloqueio</button>
                    </form>
                </section>

                <!-- Listagem de Bloqueios Futuros -->
                <section class="list-section">
                    <h3>Bloqueios Cadastrados</h3>
                    
                    <?php if (count($bloqueios) > 0): ?>
                        <div class="table-container">
                            <table class="table-mini">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Período</th>
                                        <th>Motivo</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bloqueios as $b): ?>
                                    <tr>
                                        <td><strong><?= date('d/m/Y', strtotime($b['Block_date'])) ?></strong></td>
                                        <td><?= date('H:i', strtotime($b['Block_start'])) ?> às <?= date('H:i', strtotime($b['Block_end'])) ?></td>
                                        <td><?= htmlspecialchars($b['Block_reason'] ?: '—') ?></td>
                                        <td>
                                            <a href="../../controller/ExcluirBloqueio.php?id=<?= $b['Block_id'] ?>" 
                                               class="btn-danger" 
                                               onclick="return confirm('Tem certeza que deseja remover esta pausa?')">
                                               Remover
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="padding: 36px 20px; text-align: center; color: var(--text-muted, #71717a); background: var(--bg-surface-elevated, #fafafa); border: 1px dashed var(--border-subtle, #e4e4e7); border-radius: var(--radius-md, 12px);">
                            <p style="margin: 0; font-size: 14px;">Nenhuma pausa programada para os próximos dias.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

        </div>
    </main>

</body>
</html>
