<?php
date_default_timezone_set('America/Sao_Paulo');

require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

// Proteção para Funcionário
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'F') {
    header("Location: ../../Index.php");
    exit;
}

require_once "../../model/Employee.php";
require_once "../../model/Service.php";
require_once "../../model/Availability.php";

$employeeModel = new Employee($pdo);
$serviceModel = new Service($pdo);
$availabilityModel = new Availability($pdo);

// Buscar ou garantir o Emp_id do funcionário logado
$userId = $_SESSION['User_id'];
$empId = $employeeModel->getEmpIdByUserId($userId);

if (!$empId) {
    $empId = $employeeModel->inserirEmpregado($userId);
}

// ======================================================
// SERVIÇOS DO FUNCIONÁRIO
// ======================================================
$servicos = $serviceModel->listarPorFuncionario($empId);


// ======================================================
// DISPONIBILIDADES
// ======================================================

$minhasDisponibilidades =
    $availabilityModel->listarPorFuncionario($empId);


// ======================================================
// DATAS CADASTRADAS
// ======================================================

$datasCadastradas = [];

if (!empty($minhasDisponibilidades)) {

    foreach ($minhasDisponibilidades as $disp) {

        $data = $disp['Ava_date'];

        if (!in_array($data, $datasCadastradas)) {
            $datasCadastradas[] = $data;
        }
    }

    usort($datasCadastradas, function ($a, $b) {
        return strtotime($a) - strtotime($b);
    });
}

?>

<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Disponibilidade</title>

    <link
        rel="stylesheet"
        href="../../assets/css/disponibilidade.css"
    >

</head>

<body>

<?php include '../components/AuthButton.php'; ?>

<?php include '../components/LoginModal.php'; ?>


<main class="pagina-disponibilidade">


    <!-- ==================================================
         TÍTULO
    =================================================== -->

    <h1 class="tituloPagina">
        Meus <span>Horários</span>
    </h1>

    <p class="subtitulo">
        Cadastre e Gerencie seus Horários, serão gerados após salvar e descer a página
    </p>


    <!-- ==================================================
         FORMULÁRIO
    =================================================== -->

    <form
        action="../../controller/SalvarDisponibilidade.php"
        method="POST"
        id="formDisponibilidade"
    >

        <input
            type="hidden"
            name="Fun_id"
            value="<?= htmlspecialchars($_SESSION['User_id']) ?>"
        >

        <input
            type="hidden"
            name="Emp_id"
            value="<?= htmlspecialchars($empId) ?>"
        >


        <table class="TabelaCadastro">

            <tbody>

                <tr>


                    <!-- ==================================
                         CALENDÁRIO
                    =================================== -->

                    <td class="cards">

                        <div class="campo-calendario">

                            <div id="calendario-customizado"></div>

                            <input
                                type="hidden"
                                name="Dis_date"
                                id="Dis_date"
                                min="<?= date('Y-m-d') ?>"
                                required
                            >

                        </div>

                    </td>


                    <!-- ==================================
                         ÁREA DIREITA
                    =================================== -->

                    <td class="coluna-direita">


                        <!-- SERVIÇOS -->

                        <div class="area-servico">

                            <h2 class="titulo-servicos">
                                Serviços
                            </h2>

                            <select
                                name="Dis_ser"
                                id="Dis_ser"
                                required
                            >

                                <option value="">
                                    Selecione um serviço
                                </option>


                                <?php if (!empty($servicos)): ?>

                                    <?php foreach ($servicos as $ser): ?>

                                        <option
                                            value="<?= htmlspecialchars($ser['Ser_id']) ?>"
                                            data-duration="<?= htmlspecialchars($ser['Ser_duration']) ?>"
                                            data-empser="<?= htmlspecialchars($ser['EmpSer_id']) ?>"
                                        >

                                            <?= htmlspecialchars($ser['Ser_name']) ?>

                                            (<?= htmlspecialchars($ser['Ser_duration']) ?> min)

                                        </option>

                                    <?php endforeach; ?>


                                <?php else: ?>

                                    <option value="" disabled>
                                        Nenhum serviço vinculado ao perfil
                                    </option>

                                <?php endif; ?>

                            </select>

                        </div>


                        <!-- ==================================
                             HORÁRIOS
                        =================================== -->

                        <h2 class="titulo-horarios">
                            Horários
                        </h2>

                        <h2 class="titulo-horarios2">
                            Cadastre os Horários de Atendimento
                            (entre 08:00 e 19:00)
                        </h2>


                        <div id="msg-erro-horario"></div>


                        <div class="area-horarios">


                            <div class="campo-horario">

                                <label for="Dis_start">
                                    Início
                                </label>

                                <input
                                    type="time"
                                    name="Dis_start"
                                    id="Dis_start"
                                    min="08:00"
                                    max="19:00"
                                    required
                                >

                            </div>


                            <div class="campo-horario">

                                <label for="Dis_end">
                                    Fim
                                </label>

                                <input
                                    type="time"
                                    name="Dis_end"
                                    id="Dis_end"
                                    min="08:00"
                                    max="19:00"
                                    required
                                >

                            </div>


                        </div>

                    </td>

                </tr>

            </tbody>

        </table>


        <!-- BOTÃO SALVAR -->

        <div>

            <button
                type="submit"
                id="btnSalvar"
                disabled
            >
                Salvar Disponibilidade
            </button>

        </div>


        <!-- ==================================================
             HORÁRIOS GERADOS
        =================================================== -->

        <label
            for="Dis_scheduled-times"
            id="titulo-horarios-atendimento"
        >

            <strong>
                Horários de Atendimento
            </strong>

        </label>


        <br><br>


        <div id="Dis_scheduled-times">

            <div id="lista-horarios-vazia"></div>

            <!--
                IMPORTANTE:
                os cards serão colocados aqui pelo JS
            -->

            <ul id="lista-horarios"></ul>

        </div>


        <br><br>

    </form>


    <hr>


    <!-- ==================================================
         DISPONIBILIDADES CADASTRADAS
    =================================================== -->
                                    <br> <br>
    <h2 id="titulo-disponibilidades">
        Disponibilidades Cadastradas
    </h2>

    <br><br>
 

    <?php if (!empty($minhasDisponibilidades)): ?>


        <!-- FILTRO -->

        <div class="card-filtro-data">

            <label
                for="filtroData"
                class="card-filtro-titulo"
            >
                Selecionar Data
            </label>


            <div class="card-filtro-conteudo">

                <span>
                    Escolha uma data:
                </span>


                <select
                    id="filtroData"
                    onchange="filtrarTabelaPorData()"
                >

                    <option value="nenhuma">
                        Nenhuma
                    </option>


                    <option value="todas">
                        Todas as datas
                    </option>


                    <?php foreach ($datasCadastradas as $data): ?>

                        <option
                            value="<?= htmlspecialchars($data) ?>"
                        >

                            <?= date('d/m/Y', strtotime($data)) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

        </div>


        <br><br>


        <!-- AÇÕES EM LOTE -->

        <div
            id="acoes-lote-data"
            style="display:none; margin-bottom:20px;"
        >

            <label>
                <strong>
                    Definir todos os horários desta data como:
                </strong>
            </label>


            <div style="margin-top:5px;">

                <button
                    type="button"
                    onclick="salvarLote('Disponivel')"
                >
                    Disponível
                </button>


                <button
                    type="button"
                    onclick="salvarLote('Folga')"
                >
                    Folga
                </button>


                <button
                    type="button"
                    onclick="salvarLote('Ferias')"
                >
                    Férias
                </button>


                <button
                    type="button"
                    onclick="salvarLote('Bloqueado')"
                >
                    Bloqueado
                </button>

            </div>


            <form
                action="../../controller/EditarDisponibilidade.php"
                method="POST"
                id="formLoteData"
                style="display:none;"
            >
            </form>

        </div>


        <br><br>


        <!-- TABELA -->

        <table id="tabelaDisponibilidades">

            <thead>

                <tr>

                    <th>Data</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Status Atual</th>
                    <th>Editar Status</th>
                    <th>Excluir</th>

                </tr>

            </thead>


            <tbody>

                <?php foreach ($minhasDisponibilidades as $disp): ?>

                    <tr
                        data-data="<?= htmlspecialchars($disp['Ava_date']) ?>"
                    >

                        <td>
                            <?= date(
                                'd/m/Y',
                                strtotime($disp['Ava_date'])
                            ) ?>
                        </td>


                        <td>
                            <?= htmlspecialchars(
                                substr($disp['Ava_start'], 0, 5)
                            ) ?>
                        </td>


                        <td>
                            <?= htmlspecialchars(
                                substr($disp['Ava_end'], 0, 5)
                            ) ?>
                        </td>


                        <td>

                            <strong>
                                <?= htmlspecialchars(
                                    $disp['Ava_status']
                                ) ?>
                            </strong>

                        </td>


                        <td>

                            <form
                                action="../../controller/EditarDisponibilidade.php"
                                method="POST"
                            >

                                <input
                                    type="hidden"
                                    name="Ava_id"
                                    value="<?= htmlspecialchars($disp['Ava_id']) ?>"
                                >


                                <select
                                    name="Ava_status"
                                    required
                                >

                                    <option
                                        value="Disponivel"
                                        <?= $disp['Ava_status'] === 'Disponivel' ? 'selected' : '' ?>
                                    >
                                        Disponível
                                    </option>


                                    <option
                                        value="Folga"
                                        <?= $disp['Ava_status'] === 'Folga' ? 'selected' : '' ?>
                                    >
                                        Folga
                                    </option>


                                    <option
                                        value="Ferias"
                                        <?= $disp['Ava_status'] === 'Ferias' ? 'selected' : '' ?>
                                    >
                                        Férias
                                    </option>


                                    <option
                                        value="Bloqueado"
                                        <?= $disp['Ava_status'] === 'Bloqueado' ? 'selected' : '' ?>
                                    >
                                        Bloqueado
                                    </option>

                                </select>


                                <button type="submit">
                                    Editar
                                </button>

                            </form>

                        </td>


                        <td>

                            <form
                                action="../../controller/ExcluirDisponibilidade.php"
                                method="POST"
                                onsubmit="return confirm('Tem certeza que deseja excluir a disponibilidade?');"
                            >

                                <input
                                    type="hidden"
                                    name="Ava_id"
                                    value="<?= htmlspecialchars($disp['Ava_id']) ?>"
                                >


                                <button type="submit">
                                    Excluir
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>


    <?php else: ?>

        <p>
            Nenhuma disponibilidade cadastrada.
        </p>

    <?php endif; ?>


</main>


<script>

/* ======================================================
   FILTRO DA TABELA
====================================================== */

function filtrarTabelaPorData() {

    const filtroData =
        document.getElementById('filtroData');

    if (!filtroData) return;

    const filtro = filtroData.value;

    const tabela =
        document.getElementById('tabelaDisponibilidades');

    const linhas =
        document.querySelectorAll(
            '#tabelaDisponibilidades tbody tr'
        );

    const divLote =
        document.getElementById('acoes-lote-data');


    if (filtro === 'nenhuma') {

        tabela.style.display = 'none';

        if (divLote) {
            divLote.style.display = 'none';
        }

        return;
    }


    tabela.style.display = '';


    linhas.forEach(linha => {

        if (
            filtro === 'todas' ||
            linha.getAttribute('data-data') === filtro
        ) {

            linha.style.display = '';

        } else {

            linha.style.display = 'none';

        }

    });


    if (
        filtro === 'todas' ||
        filtro === 'nenhuma'
    ) {

        if (divLote) {
            divLote.style.display = 'none';
        }

    } else {

        if (divLote) {
            divLote.style.display = 'block';
        }

    }

}


/* ======================================================
   SALVAR EM LOTE
====================================================== */

function salvarLote(status) {

    const formLote =
        document.getElementById('formLoteData');

    formLote.innerHTML = '';


    const inputStatus =
        document.createElement('input');

    inputStatus.type = 'hidden';
    inputStatus.name = 'Ava_status';
    inputStatus.value = status;

    formLote.appendChild(inputStatus);


    const linhas =
        document.querySelectorAll(
            '#tabelaDisponibilidades tbody tr'
        );


    let adicionou = false;


    linhas.forEach(linha => {

        if (linha.style.display !== 'none') {

            const idInput =
                linha.querySelector(
                    'input[name="Ava_id"]'
                );


            if (idInput) {

                const inputId =
                    document.createElement('input');

                inputId.type = 'hidden';
                inputId.name = 'Ava_id[]';
                inputId.value = idInput.value;

                formLote.appendChild(inputId);

                adicionou = true;
            }

        }

    });


    if (adicionou) {

        formLote.submit();

    } else {

        alert(
            'Nenhum horário disponível para edição nesta data.'
        );

    }

}


/* ======================================================
   CONFIGURAÇÕES DE HORÁRIO
====================================================== */

const MIN_TIME = 8 * 60;
const MAX_TIME = 19 * 60;


/* ======================================================
   DATA
====================================================== */

function getHojeFormatado() {

    const hoje = new Date();

    const ano =
        hoje.getFullYear();

    const mes =
        String(
            hoje.getMonth() + 1
        ).padStart(2, '0');

    const dia =
        String(
            hoje.getDate()
        ).padStart(2, '0');

    return `${ano}-${mes}-${dia}`;
}


function atualizarDataMinima() {

    const inputDate =
        document.getElementById('Dis_date');

    const hoje =
        getHojeFormatado();

    inputDate.min = hoje;


    if (
        inputDate.value &&
        inputDate.value < hoje
    ) {

        inputDate.value = '';

    }

}


/* ======================================================
   CONVERSÃO DE HORÁRIO
====================================================== */

function timeToMinutes(timeStr) {

    if (!timeStr) return null;

    const parts =
        timeStr.split(':').map(Number);

    return (
        parts[0] * 60 +
        parts[1]
    );

}


function minutesToTime(totalMinutes) {

    const h =
        String(
            Math.floor(totalMinutes / 60)
        ).padStart(2, '0');

    const m =
        String(
            totalMinutes % 60
        ).padStart(2, '0');

    return `${h}:${m}`;

}


/* ======================================================
   FERIADOS
====================================================== */

let feriadosCache = {};


async function getFeriados(ano) {

    if (feriadosCache[ano]) {
        return feriadosCache[ano];
    }


    try {

        const response =
            await fetch(
                `https://brasilapi.com.br/api/feriados/v1/${ano}`
            );


        if (response.ok) {

            const data =
                await response.json();


            feriadosCache[ano] =
                data.map(
                    f => f.date
                );


            return feriadosCache[ano];

        }

    } catch (e) {

        console.error(
            'Erro ao buscar feriados',
            e
        );

    }


    return [];

}


async function validarDiaBloqueado(dateString) {

    if (!dateString) return false;


    const dataObj =
        new Date(
            dateString + 'T12:00:00'
        );


    // Segunda-feira
    if (dataObj.getDay() === 1) {

        return 'Segundas-feiras o estabelecimento está fechado.';

    }


    const ano =
        dateString.substring(0, 4);


    const feriados =
        await getFeriados(ano);


    if (feriados.includes(dateString)) {

        return 'Este dia é feriado e o estabelecimento está fechado.';

    }


    return false;

}


/* ======================================================
   VALIDAÇÃO DO FORMULÁRIO
====================================================== */

async function validarFormulario() {

    atualizarDataMinima();


    const inputDate =
        document.getElementById('Dis_date');

    const selectSer =
        document.getElementById('Dis_ser');

    const inputStart =
        document.getElementById('Dis_start');

    const inputEnd =
        document.getElementById('Dis_end');

    const btnSalvar =
        document.getElementById('btnSalvar');


    let msgErroData =
        document.getElementById(
            'msg-erro-data'
        );


    if (!msgErroData) {

        msgErroData =
            document.createElement('div');

        msgErroData.id =
            'msg-erro-data';

        msgErroData.style.color =
            'red';

        msgErroData.style.fontSize =
            '14px';

        msgErroData.style.marginTop =
            '5px';

        inputDate.parentNode.appendChild(
            msgErroData
        );

    }


    msgErroData.innerText = '';


    const hoje =
        getHojeFormatado();


    let hasDate =
        inputDate.value.trim() !== '' &&
        inputDate.value >= hoje;


    if (hasDate) {

        const motivoBloqueio =
            await validarDiaBloqueado(
                inputDate.value
            );


        if (motivoBloqueio) {

            msgErroData.innerText =
                motivoBloqueio;

            hasDate = false;

        }

    }


    const selectedOption =
        selectSer.options[
            selectSer.selectedIndex
        ];


    const duration =
        selectedOption
            ? parseInt(
                selectedOption.getAttribute(
                    'data-duration'
                )
              )
            : 0;


    const hasSer =
        selectSer.value.trim() !== '' &&
        duration > 0;


    const startMin =
        timeToMinutes(
            inputStart.value
        );


    const endMin =
        timeToMinutes(
            inputEnd.value
        );


    const hasStart =
        startMin !== null;


    const hasEnd =
        endMin !== null;


    const isWithinAllowedRange =
        hasStart &&
        hasEnd &&
        startMin >= MIN_TIME &&
        startMin <= MAX_TIME &&
        endMin >= MIN_TIME &&
        endMin <= MAX_TIME;


    const isValidTime =
        isWithinAllowedRange &&
        startMin < endMin &&
        startMin + duration <= endMin;


    const podeSalvar =
        hasDate &&
        hasSer &&
        isValidTime;


    if (btnSalvar) {

        btnSalvar.disabled =
            !podeSalvar;

    }

}


/* ======================================================
   GERAR HORÁRIOS DE ATENDIMENTO
====================================================== */

function mostrarErroHorario(mensagem) {

    const msgErroHorario = document.getElementById('msg-erro-horario');

    if (!msgErroHorario) {
        return;
    }

    msgErroHorario.innerText = mensagem;
    msgErroHorario.style.display = 'block';
}

function gerarHorariosAtendimento() {

    const selectSer = document.getElementById('Dis_ser');
    const inputStart = document.getElementById('Dis_start');
    const inputEnd = document.getElementById('Dis_end');

    const containerLista = document.getElementById('lista-horarios');
    const msgErroHorario = document.getElementById('msg-erro-horario');
    const tituloHorarios = document.getElementById('titulo-horarios-atendimento');

    if (!selectSer || !inputStart || !inputEnd || !containerLista) {
        return;
    }

    // Limpa os horários anteriores
    containerLista.innerHTML = '';

    // Esconde o título inicialmente
    if (tituloHorarios) {
        tituloHorarios.style.display = 'none';
    }

    // Limpa mensagem de erro
    if (msgErroHorario) {
        msgErroHorario.innerText = '';
        msgErroHorario.style.display = 'none';
    }

    // Serviço selecionado
    const selectedOption = selectSer.options[selectSer.selectedIndex];

    const duration = selectedOption
        ? parseInt(selectedOption.getAttribute('data-duration'), 10)
        : 0;

    const startMin = timeToMinutes(inputStart.value);
    const endMin = timeToMinutes(inputEnd.value);

    // Campos ainda não preenchidos
    if (!duration || startMin === null || endMin === null) {
        validarFormulario();
        return;
    }

    // Horário fora do permitido
    if (
        startMin < MIN_TIME ||
        startMin > MAX_TIME ||
        endMin < MIN_TIME ||
        endMin > MAX_TIME
    ) {
        mostrarErroHorario(
            'Os horários de início e término devem estar entre 08:00 e 19:00.'
        );

        validarFormulario();
        return;
    }

    // Início maior ou igual ao fim
    if (startMin >= endMin) {
        mostrarErroHorario(
            'O horário de início deve ser menor que o horário de término.'
        );

        validarFormulario();
        return;
    }

    // Intervalo menor que o serviço
    if (startMin + duration > endMin) {

        const intervalo = endMin - startMin;

        mostrarErroHorario(
            `O intervalo selecionado (${intervalo} min) é menor que a duração do serviço (${duration} min).`
        );

        validarFormulario();
        return;
    }

    // Gera os horários
    let count = 0;

    for (
        let time = startMin;
        time + duration <= endMin;
        time += duration
    ) {

        const startTimeStr = minutesToTime(time);
        const endTimeStr = minutesToTime(time + duration);

        const li = document.createElement('li');

        li.className = 'card-horario';

        li.innerHTML = `
            <div class="card-horario-topo">
                <strong>${startTimeStr} às ${endTimeStr}</strong>
            </div>

            <div class="card-horario-status">

                <label for="status_${count}">
                    Status:
                </label>

                <select
                    name="horarios[${count}][status]"
                    id="status_${count}"
                    class="status-select">

                    <option value="Disponivel" selected>
                        Disponível
                    </option>

                    <option value="Folga">
                        Folga
                    </option>

                    <option value="Ferias">
                        Férias
                    </option>

                    <option value="Bloqueado">
                        Bloqueado
                    </option>

                </select>

                <input
                    type="hidden"
                    name="horarios[${count}][start]"
                    value="${startTimeStr}">

                <input
                    type="hidden"
                    name="horarios[${count}][end]"
                    value="${endTimeStr}">
            </div>
        `;

        containerLista.appendChild(li);

        count++;
    }

    // Se conseguiu gerar pelo menos um horário
    if (count > 0) {

        containerLista.style.display = 'grid';

        if (tituloHorarios) {
            tituloHorarios.style.display = 'block';
        }
    }

    validarFormulario();
}

/* ======================================================
   EVENTOS
====================================================== */

document
    .getElementById('Dis_date')
    .addEventListener(
        'focus',
        atualizarDataMinima
    );


document
    .getElementById('Dis_date')
    .addEventListener(
        'input',
        validarFormulario
    );


document
    .getElementById('Dis_date')
    .addEventListener(
        'change',
        validarFormulario
    );


document
    .getElementById('Dis_ser')
    .addEventListener(
        'change',
        gerarHorariosAtendimento
    );


document
    .getElementById('Dis_start')
    .addEventListener(
        'input',
        gerarHorariosAtendimento
    );


document
    .getElementById('Dis_start')
    .addEventListener(
        'change',
        gerarHorariosAtendimento
    );


document
    .getElementById('Dis_end')
    .addEventListener(
        'input',
        gerarHorariosAtendimento
    );


document
    .getElementById('Dis_end')
    .addEventListener(
        'change',
        gerarHorariosAtendimento
    );


setInterval(
    validarFormulario,
    60000
);


validarFormulario();

filtrarTabelaPorData();


/* ======================================================
   CALENDÁRIO
====================================================== */

const calendario =
    document.getElementById(
        'calendario-customizado'
    );


if (calendario) {

    calendario.innerHTML = `

        <div class="calendario-header">

            <button
                type="button"
                class="calendario-btn"
            >
                ‹
            </button>


            <span class="calendario-mes"></span>


            <button
                type="button"
                class="calendario-btn"
            >
                ›
            </button>

        </div>


        <div class="calendario-semana">

            <span>Dom</span>
            <span>Seg</span>
            <span>Ter</span>
            <span>Qua</span>
            <span>Qui</span>
            <span>Sex</span>
            <span>Sáb</span>

        </div>


        <div class="calendario-dias"></div>

    `;

}


let dataCalendario =
    new Date();


/* ======================================================
   GERAR CALENDÁRIO
====================================================== */

function gerarCalendario() {

    const mesElemento =
        document.querySelector(
            '.calendario-mes'
        );


    const diasElemento =
        document.querySelector(
            '.calendario-dias'
        );


    if (
        !mesElemento ||
        !diasElemento
    ) {
        return;
    }


    const ano =
        dataCalendario.getFullYear();


    const mes =
        dataCalendario.getMonth();


    const nomeMeses = [

        'Janeiro',
        'Fevereiro',
        'Março',
        'Abril',
        'Maio',
        'Junho',
        'Julho',
        'Agosto',
        'Setembro',
        'Outubro',
        'Novembro',
        'Dezembro'

    ];


    mesElemento.textContent =
        `${nomeMeses[mes]} ${ano}`;


    diasElemento.innerHTML = '';


    const primeiroDia =
        new Date(
            ano,
            mes,
            1
        ).getDay();


    const ultimoDia =
        new Date(
            ano,
            mes + 1,
            0
        ).getDate();


    /* ESPAÇOS ANTES DO MÊS */

    for (
        let i = 0;
        i < primeiroDia;
        i++
    ) {

        const vazio =
            document.createElement(
                'span'
            );

        diasElemento.appendChild(
            vazio
        );

    }


    /* DIAS */

    for (
        let dia = 1;
        dia <= ultimoDia;
        dia++
    ) {


        const elementoDia =
            document.createElement(
                'button'
            );


        elementoDia.type =
            'button';


        elementoDia.textContent =
            dia;


        const dataDia =
            new Date(
                ano,
                mes,
                dia
            );


        const hoje =
            new Date();


        dataDia.setHours(
            0, 0, 0, 0
        );


        hoje.setHours(
            0, 0, 0, 0
        );


        if (dataDia < hoje) {

            elementoDia.disabled =
                true;

        }


        elementoDia.addEventListener(
            'click',
            function () {


                const campoData =
                    document.getElementById(
                        'Dis_date'
                    );


                const mesFormatado =
                    String(
                        mes + 1
                    ).padStart(2, '0');


                const diaFormatado =
                    String(
                        dia
                    ).padStart(2, '0');


                const dataSelecionada =
                    `${ano}-${mesFormatado}-${diaFormatado}`;


                if (
                    elementoDia.classList.contains(
                        'dia-selecionado'
                    )
                ) {

                    elementoDia.classList.remove(
                        'dia-selecionado'
                    );


                    campoData.value =
                        '';

                } else {


                    document
                        .querySelectorAll(
                            '.calendario-dias button'
                        )
                        .forEach(
                            botao => {

                                botao.classList.remove(
                                    'dia-selecionado'
                                );

                            }
                        );


                    elementoDia.classList.add(
                        'dia-selecionado'
                    );


                    campoData.value =
                        dataSelecionada;

                }


                validarFormulario();

            }
        );


        diasElemento.appendChild(
            elementoDia
        );

    }

}


/* PRIMEIRA GERAÇÃO */

gerarCalendario();


/* ======================================================
   BOTÕES DO CALENDÁRIO
====================================================== */

const botoesCalendario =
    document.querySelectorAll(
        '.calendario-btn'
    );


/* MÊS ANTERIOR */

botoesCalendario[0]
    .addEventListener(
        'click',
        function () {

            const hoje =
                new Date();


            const mesAtual =
                hoje.getMonth();


            const anoAtual =
                hoje.getFullYear();


            const mesCalendario =
                dataCalendario.getMonth();


            const anoCalendario =
                dataCalendario.getFullYear();


            if (
                anoCalendario > anoAtual ||
                (
                    anoCalendario === anoAtual &&
                    mesCalendario > mesAtual
                )
            ) {

                dataCalendario.setMonth(
                    dataCalendario.getMonth() - 1
                );


                gerarCalendario();

            }

        }
    );


/* PRÓXIMO MÊS */

botoesCalendario[1]
    .addEventListener(
        'click',
        function () {

            dataCalendario.setMonth(
                dataCalendario.getMonth() + 1
            );


            gerarCalendario();

        }
    );

</script>

</body>

</html>