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

if (!$ser_id) {
    header("Location: Servicos.php");
    exit;
}

// Buscar detalhes completos do Serviço
$sqlServico = "SELECT Ser_name, Ser_price, Ser_duration, Ser_image, Ser_description FROM services WHERE Ser_id = ?";
$stmtServico = $pdo->prepare($sqlServico);
$stmtServico->execute([$ser_id]);
$servico = $stmtServico->fetch(PDO::FETCH_ASSOC);

if (!$servico) {
    header("Location: Servicos.php");
    exit;
}

// Para UX: Restrição mínima para o dia atual
$hoje = date("Y-m-d");
$amanha = date("Y-m-d", strtotime("+1 day"));
$depoisAmanha = date("Y-m-d", strtotime("+2 days"));
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolha a Data | Agendamento MB Studio</title>

    <link rel="stylesheet" href="../../assets/css/global.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/cliente/data.css">
</head>

<body>

    <!-- Cabeçalho Oficial do Projeto -->
    <?php include '../components/Header.php'; ?>

    <main class="booking-page-wrapper">

        <!-- Elementos Decorativos Flutuantes -->
        <div class="decoracao decoracao-esquerda"></div>
        <div class="decoracao decoracao-direita"></div>

        <div class="booking-container">

            <!-- Navegação Superior -->
            <div class="top-nav">
                <a href="Servicos.php" class="btn-voltar" title="Voltar para a lista de serviços">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    <span>Escolher outro serviço</span>
                </a>
            </div>

            <!-- Progresso das Etapas -->
            <div class="steps-progress-bar">
                <div class="step-item completed">
                    <div class="step-badge">✓</div>
                    <span class="step-text">Serviço</span>
                </div>
                <div class="step-divider"></div>
                <div class="step-item active">
                    <div class="step-badge">2</div>
                    <span class="step-text">Escolha da Data</span>
                </div>
                <div class="step-divider"></div>
                <div class="step-item">
                    <div class="step-badge">3</div>
                    <span class="step-text">Horário</span>
                </div>
                <div class="step-divider"></div>
                <div class="step-item">
                    <div class="step-badge">4</div>
                    <span class="step-text">Confirmação</span>
                </div>
            </div>

            <!-- Título e Introdução -->
            <div class="page-header-box">
                <span class="tag-subtitulo">✦ Agendamento Online ✦</span>
                <h1 class="page-title">Qual o melhor dia para seu atendimento?</h1>
                <p class="page-desc">Selecione uma data no calendário abaixo para consultar os horários e especialistas disponíveis no MB Studio.</p>
            </div>

            <!-- Resumo do Serviço Selecionado -->
            <div class="service-summary-card">
                <div class="service-summary-left">
                    <?php if (!empty($servico['Ser_image'])): ?>
                        <img src="../../<?= htmlspecialchars($servico['Ser_image']) ?>" alt="<?= htmlspecialchars($servico['Ser_name']) ?>" class="service-summary-img">
                    <?php else: ?>
                        <div class="service-summary-img">✂</div>
                    <?php endif; ?>

                    <div class="service-summary-info">
                        <h3><?= htmlspecialchars($servico['Ser_name']) ?></h3>
                        <div class="service-summary-meta">
                            <span>Preço: <strong>R$ <?= number_format($servico['Ser_price'] ?? 0, 2, ',', '.') ?></strong></span>
                            <span>•</span>
                            <span>Duração: <strong><?= htmlspecialchars($servico['Ser_duration'] ?? 0) ?> min</strong></span>
                        </div>
                    </div>
                </div>

                <a href="Servicos.php" class="btn-change-service" title="Alterar serviço">
                    Trocar Serviço
                </a>
            </div>

            <!-- Card de Escolha de Data -->
            <div class="date-picker-card">
                <h2>Selecione a Data Desejada</h2>
                <p class="picker-subtitle">Escolha uma data rápida ou use o seletor completo do calendário.</p>

                <!-- Botões de Atalho Rápido de Data -->
                <div class="quick-dates-grid">
                    <button type="button" class="quick-date-btn active" onclick="setBookingDate('<?= $hoje ?>', this)">
                        Hoje (<?= date("d/m", strtotime($hoje)) ?>)
                    </button>
                    <button type="button" class="quick-date-btn" onclick="setBookingDate('<?= $amanha ?>', this)">
                        Amanhã (<?= date("d/m", strtotime($amanha)) ?>)
                    </button>
                    <button type="button" class="quick-date-btn" onclick="setBookingDate('<?= $depoisAmanha ?>', this)">
                        Depois de amanhã (<?= date("d/m", strtotime($depoisAmanha)) ?>)
                    </button>
                </div>

                <!-- Formulário Original com Destino para Horarios.php -->
                <form action="Horarios.php" method="GET">
                    <input type="hidden" name="Ser_id" value="<?= htmlspecialchars($ser_id) ?>">

                    <div class="date-input-wrap">
                        <label for="data">Data do Agendamento:</label>
                        <input 
                            type="date" 
                            id="data" 
                            name="data" 
                            min="<?= $hoje ?>" 
                            value="<?= $hoje ?>" 
                            class="input-date-luxury" 
                            required
                        >
                    </div>

                    <button type="submit" class="btn-submit-date">
                        <span>Avançar para Ver Horários</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </form>
            </div>

        </div>

    </main>

    <!-- Modal Global de Autenticação -->
    <?php include '../components/LoginModal.php'; ?>

    <script>
        function setBookingDate(dateStr, btnElement) {
            const dateInput = document.getElementById('data');
            if (dateInput) {
                dateInput.value = dateStr;
            }
            document.querySelectorAll('.quick-date-btn').forEach(btn => btn.classList.remove('active'));
            if (btnElement) {
                btnElement.classList.add('active');
            }
        }

        // Sincronizar botões rápidos caso o usuário use o calendário manual
        document.getElementById('data').addEventListener('change', function() {
            const val = this.value;
            const quickButtons = document.querySelectorAll('.quick-date-btn');
            quickButtons.forEach(btn => btn.classList.remove('active'));

            if (val === '<?= $hoje ?>') {
                quickButtons[0]?.classList.add('active');
            } else if (val === '<?= $amanha ?>') {
                quickButtons[1]?.classList.add('active');
            } else if (val === '<?= $depoisAmanha ?>') {
                quickButtons[2]?.classList.add('active');
            }
        });
    </script>

</body>

</html>