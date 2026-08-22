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

require_once "../../model/Service.php";
require_once "../../model/Availability.php";
require_once "../../model/Appointment.php";

$serviceModel = new Service($pdo);
$availabilityModel = new Availability($pdo);
$appointmentModel = new Appointment($pdo);

// Buscar detalhes do Serviço
$servico = $serviceModel->buscarPorId($ser_id);

if (!$servico) {
    header("Location: Servicos.php");
    exit;
}

// Buscar as disponibilidades de todos os funcionários que fazem esse serviço naquela data
$disponibilidades = $availabilityModel->listarHorariosDisponiveis($data, $ser_id);

// Buscar todos os agendamentos ativos para a data selecionada
$agendamentosDoDia = $appointmentModel->buscarTodosAgendamentosAtivosDoDia($data);

// Função para gerar blocos de horários e filtrar os já ocupados
function gerarHorariosDisponiveis($inicio, $fim, $duracao_minutos, $emp_id, $agendamentosDoDia)
{
    $horarios = [];
    $atual = strtotime($inicio);
    $final = strtotime($fim);
    $duracao_segundos = $duracao_minutos * 60;

    // Subtrai a duração para garantir que o último horário tenha tempo de terminar antes do "fim" do expediente
    while ($atual + $duracao_segundos <= $final) {
        $hora_inicio_str = date('H:i:s', $atual);
        $hora_fim_str = date('H:i:s', $atual + $duracao_segundos);
        
        $conflito = false;
        foreach ($agendamentosDoDia as $ag) {
            if ($ag['Emp_id'] == $emp_id) {
                // Se houver sobreposição, há conflito
                if (
                    ($hora_inicio_str < $ag['Appo_end']) && 
                    ($hora_fim_str > $ag['Appo_start'])
                ) {
                    $conflito = true;
                    break;
                }
            }
        }
        
        if (!$conflito) {
            $horarios[] = date('H:i', $atual);
        }
        
        $atual += $duracao_segundos;
    }
    return $horarios;
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>Escolha o Horário</title>
    <style>
        .func-box {
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .time-btn {
            display: inline-block;
            margin: 5px;
            padding: 10px 15px;
            border: 1px solid #007bff;
            background-color: #f8f9fa;
            color: #007bff;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .time-btn:hover {
            background-color: #007bff;
            color: #fff;
        }

        .emp-info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .emp-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
    </style>
    <link rel="stylesheet" href="../../assets/css/global.css">
</head>

<body>
    <?php include '../components/Header.php'; ?>
    <?php include '../components/LoginModal.php'; ?>

    <div style="padding: 20px;">
        <h1>Agendamento - Passo 3: Horários Disponíveis</h1>
    <a href="Data.php?Ser_id=<?= htmlspecialchars($ser_id) ?>">Voltar para a escolha de data</a>
    <hr>

    <h2>Serviço: <?= htmlspecialchars($servico['Ser_name']) ?> | Data: <?= date("d/m/Y", strtotime($data)) ?></h2>

    <?php if (count($disponibilidades) > 0): ?>

        <?php foreach ($disponibilidades as $disp): ?>
            <div class="func-box">
                <div class="emp-info">
                    <?php if (!empty($disp['Emp_photo'])): ?>
                        <img src="../../<?= htmlspecialchars($disp['Emp_photo']) ?>" class="emp-photo" alt="Foto">
                    <?php else: ?>
                        <div class="emp-photo" style="background:#ddd; display:flex; align-items:center; justify-content:center;">
                            Sem</div>
                    <?php endif; ?>
                    <h3>Profissional: <?= htmlspecialchars($disp['User_name']) ?></h3>
                </div>

                <p><strong>Expediente cadastrado:</strong> <?= date('H:i', strtotime($disp['Ava_start'])) ?> às
                    <?= date('H:i', strtotime($disp['Ava_end'])) ?>
                </p>
                <p>Selecione um horário:</p>

                <?php
                $horarios_gerados = gerarHorariosDisponiveis($disp['Ava_start'], $disp['Ava_end'], $servico['Ser_duration'], $disp['Emp_id'], $agendamentosDoDia);
                if (count($horarios_gerados) > 0):
                    foreach ($horarios_gerados as $h):
                        ?>
                        <!-- Este link mandará os dados para Confirmar.php que salvará o agendamento real na tabela appointments -->
                        <a href="Confirmar.php?Ser_id=<?= $ser_id ?>&data=<?= $data ?>&Emp_id=<?= $disp['Emp_id'] ?>&hora=<?= $h ?>"
                            class="time-btn">
                                <?= $h ?>
                        </a>
                    <?php
                    endforeach;
                else:
                    ?>
                    <p><em>Nenhum horário disponível para este profissional no momento (ocupado ou tempo insuficiente).</em></p>
                <?php endif; ?>

                    </div>
            <?php endforeach; ?>

    <?php else: ?>
            <p>Infelizmente não há profissionais com agenda disponível ('Disponivel') para este serviço na data solicitada.</p>
    <?php endif; ?>
    </div>

</body>

</html>