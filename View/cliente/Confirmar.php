<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

// Proteção para Cliente
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'C') {
    header("Location: ../../Index.php");
    exit;
}

$ser_id = $_GET['Ser_id'] ?? null;
$emp_id = $_GET['Emp_id'] ?? null;
$data = $_GET['data'] ?? null;
$hora = $_GET['hora'] ?? null;

if (!$ser_id || !$emp_id || !$data || !$hora) {
    header("Location: Servicos.php");
    exit;
}

// Buscar detalhes do serviço
$stmtServico = $pdo->prepare("SELECT Ser_name, Ser_price, Ser_duration FROM services WHERE Ser_id = ?");
$stmtServico->execute([$ser_id]);
$servico = $stmtServico->fetch(PDO::FETCH_ASSOC);

// Buscar detalhes do profissional
$stmtFunc = $pdo->prepare("SELECT u.User_name FROM employees e JOIN users u ON e.User_id = u.User_id WHERE e.Emp_id = ?");
$stmtFunc->execute([$emp_id]);
$funcionario = $stmtFunc->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Agendamento | MB Studio</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/cliente/confirmar.css">
</head>
<body class="cliente-page">

    <!-- Cabeçalho Oficial do Projeto -->
    <?php include '../components/Header.php'; ?>

    <main class="confirm-page-wrapper">
        <div class="confirm-container">

            <!-- Stepper de Progresso -->
            <div class="steps-progress-bar">
                <div class="step-item completed">
                    <div class="step-badge">✓</div>
                    <span class="step-text">Serviço</span>
                </div>
                <div class="step-divider"></div>
                <div class="step-item completed">
                    <div class="step-badge">✓</div>
                    <span class="step-text">Data</span>
                </div>
                <div class="step-divider"></div>
                <div class="step-item completed">
                    <div class="step-badge">✓</div>
                    <span class="step-text">Horário</span>
                </div>
                <div class="step-divider"></div>
                <div class="step-item active">
                    <div class="step-badge">4</div>
                    <span class="step-text">Confirmação</span>
                </div>
            </div>

            <div class="confirm-header-box">
                <span class="tag-subtitulo" style="font-size: 12px; font-weight: 700; color: var(--gold-dark); letter-spacing: 2px; text-transform: uppercase;">✦ Quase Lá ✦</span>
                <h1 class="confirm-title">Confirmação do Agendamento</h1>
                <p class="confirm-subtitle">Revise os detalhes abaixo antes de confirmar o seu horário no salão.</p>
            </div>

            <!-- Card Resumo do Pedido -->
            <div class="confirm-card mini-card">
                <div class="confirm-card-header">
                    <h3>Resumo do Atendimento</h3>
                    <span style="font-size: 13px; color: var(--gold-dark); font-weight: 600;">MB Studio</span>
                </div>

                <div class="order-items-list">
                    <div class="order-item-row">
                        <span class="label">Procedimento / Serviço:</span>
                        <span class="val"><?= htmlspecialchars($servico['Ser_name'] ?? '') ?></span>
                    </div>

                    <div class="order-item-row price-highlight">
                        <span class="label">Valor Estimado:</span>
                        <span class="val">R$ <?= number_format($servico['Ser_price'] ?? 0, 2, ',', '.') ?></span>
                    </div>

                    <div class="order-item-row">
                        <span class="label">Profissional Selecionado:</span>
                        <span class="val"><?= htmlspecialchars($funcionario['User_name'] ?? '') ?></span>
                    </div>

                    <div class="order-item-row">
                        <span class="label">Data Marcada:</span>
                        <span class="val"><?= date('d/m/Y', strtotime($data)) ?></span>
                    </div>

                    <div class="order-item-row">
                        <span class="label">Horário de Início:</span>
                        <span class="val"><?= htmlspecialchars($hora) ?></span>
                    </div>

                    <?php if (!empty($servico['Ser_duration'])): ?>
                    <div class="order-item-row">
                        <span class="label">Duração Prevista:</span>
                        <span class="val"><?= htmlspecialchars($servico['Ser_duration']) ?> minutos</span>
                    </div>
                    <?php endif; ?>
                </div>

                <form action="../../controller/Agendar.php" method="POST">
                    <input type="hidden" name="ser_id" value="<?= htmlspecialchars($ser_id) ?>">
                    <input type="hidden" name="emp_id" value="<?= htmlspecialchars($emp_id) ?>">
                    <input type="hidden" name="data" value="<?= htmlspecialchars($data) ?>">
                    <input type="hidden" name="hora" value="<?= htmlspecialchars($hora) ?>">
                    
                    <div class="form-group-obs">
                        <label for="observacao">Observações Adicionais (Opcional):</label>
                        <textarea id="observacao" name="observacao" rows="3" placeholder="Ex: Preferência por produtos sem fragrância, histórico de alergia, etc."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-confirm-submit btn-gold">
                        <span>Confirmar e Solicitar Horário</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </button>
                </form>
            </div>

        </div>
    </main>

    <!-- Modal Global de Autenticação -->
    <?php include '../components/LoginModal.php'; ?>

</body>
</html>
