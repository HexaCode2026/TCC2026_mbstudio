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
$data = $_GET['data'] ?? null;

if (!$ser_id || !$data) {
    header("Location: Servicos.php");
    exit;
}

// Buscar detalhes completos do Serviço
$sqlServico = "SELECT Ser_name, Ser_duration, Ser_price, Ser_image FROM services WHERE Ser_id = ?";
$stmtServico = $pdo->prepare($sqlServico);
$stmtServico->execute([$ser_id]);
$servico = $stmtServico->fetch(PDO::FETCH_ASSOC);

if (!$servico) {
    header("Location: Servicos.php");
    exit;
}

// Buscar as disponibilidades de todos os funcionários que fazem esse serviço naquela data
$sql = "SELECT a.Ava_start, a.Ava_end, e.Emp_id, u.User_name, e.Emp_photo, e.Emp_specialty
        FROM availabilities a
        JOIN employees e ON a.Emp_id = e.Emp_id
        JOIN users u ON e.User_id = u.User_id
        JOIN employee_services es ON e.Emp_id = es.Emp_id
        WHERE a.Ava_date = ? 
          AND a.Ava_status = 'Disponivel' 
          AND es.Ser_id = ? 
          AND u.User_perm = 'F'
        ORDER BY a.Ava_start ASC, u.User_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$data, $ser_id]);
$disponibilidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar agendamentos do dia (não cancelados) para controle de conflito
$sqlAppo = "SELECT Emp_id, Appo_start, Appo_end FROM appointments WHERE Appo_date = ? AND Appo_status NOT LIKE 'Cancelado%'";
$stmtAppo = $pdo->prepare($sqlAppo);
$stmtAppo->execute([$data]);
$agendamentosDoDia = $stmtAppo->fetchAll(PDO::FETCH_ASSOC);

// Buscar bloqueios/pausas do dia para controle de conflito
$sqlBlock = "SELECT Emp_id, Block_start, Block_end FROM employee_blocks WHERE Block_date = ?";
$stmtBlock = $pdo->prepare($sqlBlock);
$stmtBlock->execute([$data]);
$bloqueiosDoDia = $stmtBlock->fetchAll(PDO::FETCH_ASSOC);

// Função para gerar blocos de horários com base na duração do serviço, evitando conflitos
function gerarHorarios($inicio, $fim, $duracao_minutos, $emp_id, $agendamentos, $bloqueios)
{
    $horarios = [];
    $atual = strtotime($inicio);
    $final = strtotime($fim);

    while ($atual + ($duracao_minutos * 60) <= $final) {
        $slot_start_time = $atual;
        $slot_end_time = $atual + ($duracao_minutos * 60);
        $conflito = false;
        $max_end = 0;

        // Verifica conflito com agendamentos existentes
        foreach ($agendamentos as $ag) {
            if ($ag['Emp_id'] == $emp_id) {
                $ag_start = strtotime($ag['Appo_start']);
                $ag_end = strtotime($ag['Appo_end']);
                if ($slot_start_time < $ag_end && $slot_end_time > $ag_start) {
                    $conflito = true;
                    if ($ag_end > $max_end) {
                        $max_end = $ag_end;
                    }
                }
            }
        }

        // Verifica conflito com bloqueios/pausas do funcionário
        foreach ($bloqueios as $bl) {
            if ($bl['Emp_id'] == $emp_id) {
                $bl_start = strtotime($bl['Block_start']);
                $bl_end = strtotime($bl['Block_end']);
                if ($slot_start_time < $bl_end && $slot_end_time > $bl_start) {
                    $conflito = true;
                    if ($bl_end > $max_end) {
                        $max_end = $bl_end;
                    }
                }
            }
        }

        // Se não houver conflito, o horário está livre
        if (!$conflito) {
            $horarios[] = date('H:i', $atual);
            $atual += ($duracao_minutos * 60); // Avança normalmente
        } else {
            // Se houver conflito, pula exatamente para o final do bloqueio mais longo que conflitou
            if ($max_end > $atual) {
                $atual = $max_end;
            } else {
                // Fallback de segurança para evitar loops infinitos
                $atual += (10 * 60);
            }
        }
    }
    return $horarios;
}

// Função auxiliar para gerar iniciais quando não houver foto
function getIniciais($nome) {
    $partes = preg_split('/\s+/', trim($nome));
    $iniciais = '';
    if (count($partes) >= 2) {
        $iniciais = mb_substr($partes[0], 0, 1) . mb_substr(end($partes), 0, 1);
    } elseif (count($partes) === 1 && !empty($partes[0])) {
        $iniciais = mb_substr($partes[0], 0, 2);
    }
    return strtoupper($iniciais ?: 'MB');
}

// Formatar data em português
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
$dataTimestamp = strtotime($data);
$dataFormatada = date("d/m/Y", $dataTimestamp);
$diasSemana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
$diaSemanaNome = $diasSemana[date('w', $dataTimestamp)];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolha o Horário | MB Studio</title>

    <link rel="stylesheet" href="../../assets/css/global.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/cliente/horarios.css">
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
                <a href="Data.php?Ser_id=<?= htmlspecialchars($ser_id) ?>" class="btn-voltar" title="Retornar à seleção de data">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    <span>Alterar data</span>
                </a>
            </div>

            <!-- Progresso das Etapas -->
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
                <div class="step-item active">
                    <div class="step-badge">3</div>
                    <span class="step-text">Horário</span>
                </div>
                <div class="step-divider"></div>
                <div class="step-item">
                    <div class="step-badge">4</div>
                    <span class="step-text">Confirmação</span>
                </div>
            </div>

            <!-- Resumo das Escolhas Anteriores -->
            <div class="selection-summary-card">
                <div class="summary-block">
                    <div class="summary-icon-wrap">✂</div>
                    <div>
                        <div class="summary-label">Serviço Selecionado</div>
                        <div class="summary-val"><?= htmlspecialchars($servico['Ser_name']) ?></div>
                        <div class="summary-subval">
                            Duração: <strong><?= $servico['Ser_duration'] ?> min</strong>
                            <?php if(!empty($servico['Ser_price'])): ?>
                                • R$ <strong><?= number_format($servico['Ser_price'], 2, ',', '.') ?></strong>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="summary-block">
                    <div class="summary-icon-wrap">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div>
                        <div class="summary-label">Data Escolhida</div>
                        <div class="summary-val"><?= $dataFormatada ?></div>
                        <div class="summary-subval"><?= $diaSemanaNome ?></div>
                    </div>
                </div>

                <div class="summary-actions">
                    <a href="Data.php?Ser_id=<?= htmlspecialchars($ser_id) ?>" class="btn-action-small">Alterar Data</a>
                    <a href="Servicos.php" class="btn-action-small">Trocar Serviço</a>
                </div>
            </div>

            <!-- Título e Introdução da Seção -->
            <div class="section-header-box">
                <span class="tag-subtitulo">✦ Profissionais & Disponibilidade ✦</span>
                <h1 class="section-title">Escolha o Profissional e Horário</h1>
                <p class="section-desc">Clique no horário mais conveniente abaixo para avançar para a confirmação do seu agendamento.</p>
            </div>

            <!-- Lista de Profissionais Disponíveis -->
            <?php if (count($disponibilidades) > 0): ?>
                <div class="professionals-grid">
                    <?php foreach ($disponibilidades as $disp): ?>
                        <div class="professional-card">
                            
                            <!-- Cabeçalho do Especialista -->
                            <div class="professional-header">
                                <div class="prof-info-left">
                                    <div class="prof-photo-frame">
                                        <?php if (!empty($disp['Emp_photo'])): ?>
                                            <img src="../../<?= htmlspecialchars($disp['Emp_photo']) ?>" class="prof-photo-img" alt="<?= htmlspecialchars($disp['User_name']) ?>">
                                        <?php else: ?>
                                            <span><?= getIniciais($disp['User_name']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h2 class="prof-name-text"><?= htmlspecialchars($disp['User_name']) ?></h2>
                                        <?php if (!empty($disp['Emp_specialty'])): ?>
                                            <span class="prof-specialty-badge"><?= htmlspecialchars($disp['Emp_specialty']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="shift-badge">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    <span>Expediente: <?= date('H:i', strtotime($disp['Ava_start'])) ?> às <?= date('H:i', strtotime($disp['Ava_end'])) ?></span>
                                </div>
                            </div>

                            <!-- Grade de Horários Disponíveis -->
                            <div class="time-slots-container">
                                <div class="time-slots-title">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    Horários Livres para Atendimento:
                                </div>

                                <?php
                                $horarios_gerados = gerarHorarios(
                                    $disp['Ava_start'], 
                                    $disp['Ava_end'], 
                                    $servico['Ser_duration'], 
                                    $disp['Emp_id'], 
                                    $agendamentosDoDia, 
                                    $bloqueiosDoDia
                                );
                                
                                if (count($horarios_gerados) > 0):
                                ?>
                                    <div class="time-slots-grid">
                                        <?php foreach ($horarios_gerados as $h): ?>
                                            <!-- Link que envia os dados para Confirmar.php -->
                                            <a 
                                                href="Confirmar.php?Ser_id=<?= $ser_id ?>&data=<?= $data ?>&Emp_id=<?= $disp['Emp_id'] ?>&hora=<?= $h ?>" 
                                                class="time-slot-btn"
                                                title="Agendar com <?= htmlspecialchars($disp['User_name']) ?> às <?= $h ?>"
                                            >
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <polyline points="12 6 12 12 16 14"></polyline>
                                                </svg>
                                                <span><?= $h ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="warning-duration-notice">
                                        <em>O tempo deste procedimento (<?= $servico['Ser_duration'] ?> min) ultrapassa a janela disponível deste profissional.</em>
                                    </p>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <!-- Estado Vazio -->
                <div class="empty-schedule-card">
                    <div class="empty-icon-circle">📅</div>
                    <h2 class="empty-title">Nenhum horário disponível para esta data</h2>
                    <p class="empty-desc">
                        Não encontramos nenhum especialista com horários livres para o procedimento <strong>"<?= htmlspecialchars($servico['Ser_name']) ?>"</strong> no dia <strong><?= $dataFormatada ?></strong>.
                    </p>
                    <a href="Data.php?Ser_id=<?= htmlspecialchars($ser_id) ?>" class="btn-choose-other-date">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        <span>Escolher Outra Data</span>
                    </a>
                </div>
            <?php endif; ?>

        </div>

    </main>

    <!-- Modal Global de Autenticação -->
    <?php include '../components/LoginModal.php'; ?>

</body>

</html>