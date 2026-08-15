<?php
date_default_timezone_set('America/Sao_Paulo');
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

// Proteção para Funcionário
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'F') {
    header("Location: ../Login.php");
    exit;
}

// Buscar ou garantir o Emp_id do funcionário logado
$userId = $_SESSION['User_id'];
$stmtEmp = $pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
$stmtEmp->execute([$userId]);
$empId = $stmtEmp->fetchColumn();

if (!$empId) {
    $insertEmp = $pdo->prepare("INSERT INTO employees (User_id) VALUES (?)");
    $insertEmp->execute([$userId]);
    $empId = $pdo->lastInsertId();
}

// Buscar serviços vinculados ao funcionário através da tabela employee_services
// Seleciona EmpSer_id, a chave estrangeira Ser_id e dados do serviço para o Emp_id do funcionário logado
$sqlServicos = "SELECT es.EmpSer_id, es.Emp_id, es.Ser_id, s.Ser_name, s.Ser_duration 
                FROM employee_services es
                INNER JOIN services s ON es.Ser_id = s.Ser_id
                WHERE es.Emp_id = ? AND (s.Ser_active = 1 OR s.Ser_active IS NULL)
                ORDER BY s.Ser_name ASC";
$stmtServicos = $pdo->prepare($sqlServicos);
$stmtServicos->execute([$empId]);
$servicos = $stmtServicos->fetchAll(PDO::FETCH_ASSOC);

// Buscar todas as disponibilidades já cadastradas para o funcionário (incluindo datas passadas)
require_once "../../model/Availability.php";
$availabilityModel = new Availability($pdo);
$minhasDisponibilidades = $availabilityModel->listarPorFuncionario($empId);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disponibilidade</title>
</head>

<body>
    <h1>Definir Disponibilidade</h1>

    <form action="../../controller/SalvarDisponibilidade.php" method="POST" id="formDisponibilidade">
        <input type="hidden" name="Fun_id" value="<?= htmlspecialchars($_SESSION['User_id']) ?>">
        <input type="hidden" name="Emp_id" value="<?= htmlspecialchars($empId) ?>">

        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Serviço</th>
                    <th>Início</th>
                    <th>Fim</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <input type="date" name="Dis_date" id="Dis_date" min="<?= date('Y-m-d') ?>" required>
                    </td>
                    <td>
                        <select name="Dis_ser" id="Dis_ser" required>
                            <option value="">Selecione um serviço</option>
                            <?php if (!empty($servicos)): ?>
                                <?php foreach ($servicos as $ser): ?>
                                    <option value="<?= htmlspecialchars($ser['Ser_id']) ?>" 
                                            data-duration="<?= htmlspecialchars($ser['Ser_duration']) ?>"
                                            data-empser="<?= htmlspecialchars($ser['EmpSer_id']) ?>">
                                        <?= htmlspecialchars($ser['Ser_name']) ?> (<?= htmlspecialchars($ser['Ser_duration']) ?> min)
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Nenhum serviço vinculado ao perfil</option>
                            <?php endif; ?>
                        </select>
                    </td>
                    <td>
                        <input type="time" name="Dis_start" id="Dis_start" min="08:00" max="19:00" required>
                    </td>
                    <td>
                        <input type="time" name="Dis_end" id="Dis_end" min="08:00" max="19:00" required>
                    </td>
                </tr>
            </tbody>
        </table>

        <label for="Dis_scheduled-times"><strong>Horários de Atendimento</strong></label>
        <br><br>

        <!-- CONTAINER DINÂMICO DOS HORÁRIOS DE ATENDIMENTO -->
        <div id="Dis_scheduled-times">
            <!-- Controle de definição para todos os horários -->
            <div id="horarios-actions">
                <label><strong>Definir todos como:</strong></label>
                <div>
                    <button type="button" onclick="definirTodosStatus('Disponivel')">Disponível</button>
                    <button type="button" onclick="definirTodosStatus('Folga')">Folga</button>
                    <button type="button" onclick="definirTodosStatus('Ferias')">Férias</button>
                    <button type="button" onclick="definirTodosStatus('Bloqueado')">Bloqueado</button>
                </div>
            </div>

            <div id="lista-horarios-vazia">
                Selecione um serviço, horário de início e término para gerar os horários de atendimento (entre 08:00 e 19:00).
            </div>

            <ul id="lista-horarios">
                <!-- Horários gerados dinamicamente um abaixo do outro -->
            </ul>
        </div>
        <br><br>

        <div>
            <button type="submit" id="btnSalvar" disabled>Salvar Disponibilidade</button>
        </div>
    </form>

    <hr>

    <!-- LISTAGEM DAS DISPONIBILIDADES COM BOTÕES DE EDITAR E EXCLUIR POR AVA_ID -->
    <h2>Disponibilidades Cadastradas</h2>

    <?php if (!empty($minhasDisponibilidades)): ?>
        <table>
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
                    <tr>
                        <td><?= date('d/m/Y', strtotime($disp['Ava_date'])) ?></td>
                        <td><?= htmlspecialchars(substr($disp['Ava_start'], 0, 5)) ?></td>
                        <td><?= htmlspecialchars(substr($disp['Ava_end'], 0, 5)) ?></td>
                        <td><strong><?= htmlspecialchars($disp['Ava_status']) ?></strong></td>
                        <td>
                            <!-- FORMULÁRIO DE EDIÇÃO POR AVA_ID -->
                            <form action="../../controller/EditarDisponibilidade.php" method="POST">
                                <input type="hidden" name="Ava_id" value="<?= htmlspecialchars($disp['Ava_id']) ?>">
                                <select name="Ava_status" required>
                                    <option value="Disponivel" <?= $disp['Ava_status'] === 'Disponivel' ? 'selected' : '' ?>>Disponível</option>
                                    <option value="Folga" <?= $disp['Ava_status'] === 'Folga' ? 'selected' : '' ?>>Folga</option>
                                    <option value="Ferias" <?= $disp['Ava_status'] === 'Ferias' ? 'selected' : '' ?>>Férias</option>
                                    <option value="Bloqueado" <?= $disp['Ava_status'] === 'Bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                                </select>
                                <button type="submit">Editar</button>
                            </form>
                        </td>
                        <td>
                            <!-- FORMULÁRIO DE EXCLUSÃO POR AVA_ID -->
                            <form action="../../controller/ExcluirDisponibilidade.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir a disponibilidade?');">
                                <input type="hidden" name="Ava_id" value="<?= htmlspecialchars($disp['Ava_id']) ?>">
                                <button type="submit">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nenhuma disponibilidade cadastrada.</p>
    <?php endif; ?>

    <script>
        const MIN_TIME = 8 * 60;   // 08:00 (480 minutos)
        const MAX_TIME = 19 * 60;  // 19:00 (1140 minutos)

        function getHojeFormatado() {
            const hoje = new Date();
            const ano = hoje.getFullYear();
            const mes = String(hoje.getMonth() + 1).padStart(2, '0');
            const dia = String(hoje.getDate()).padStart(2, '0');
            return `${ano}-${mes}-${dia}`;
        }

        function atualizarDataMinima() {
            const inputDate = document.getElementById('Dis_date');
            const hoje = getHojeFormatado();
            inputDate.min = hoje;

            // Se o usuário tinha selecionado uma data anterior à data atual (ex: virada de dia), limpa o campo
            if (inputDate.value && inputDate.value < hoje) {
                inputDate.value = '';
            }
        }

        function timeToMinutes(timeStr) {
            if (!timeStr) return null;
            const parts = timeStr.split(':').map(Number);
            return parts[0] * 60 + parts[1];
        }

        function minutesToTime(totalMinutes) {
            const h = String(Math.floor(totalMinutes / 60)).padStart(2, '0');
            const m = String(totalMinutes % 60).padStart(2, '0');
            return `${h}:${m}`;
        }

        function validarFormulario() {
            atualizarDataMinima();
            const inputDate = document.getElementById('Dis_date');
            const selectSer = document.getElementById('Dis_ser');
            const inputStart = document.getElementById('Dis_start');
            const inputEnd = document.getElementById('Dis_end');
            const btnSalvar = document.getElementById('btnSalvar');
            const btnEditar = document.getElementById('btnEditar');
            const btnExcluir = document.getElementById('btnExcluir');

            const hoje = getHojeFormatado();
            const hasDate = inputDate.value.trim() !== '' && inputDate.value >= hoje;
            const selectedOption = selectSer.options[selectSer.selectedIndex];
            const duration = selectedOption ? parseInt(selectedOption.getAttribute('data-duration')) : 0;
            const hasSer = selectSer.value.trim() !== '' && duration > 0;
            const startMin = timeToMinutes(inputStart.value);
            const endMin = timeToMinutes(inputEnd.value);
            const hasStart = startMin !== null;
            const hasEnd = endMin !== null;

            const isWithinAllowedRange = hasStart && hasEnd &&
                startMin >= MIN_TIME && startMin <= MAX_TIME &&
                endMin >= MIN_TIME && endMin <= MAX_TIME;

            const isValidTime = isWithinAllowedRange && (startMin < endMin) && (startMin + duration <= endMin);

            const podeSalvarEditar = (hasDate && hasSer && isValidTime);

            if (btnSalvar) btnSalvar.disabled = !podeSalvarEditar;
            if (btnEditar) btnEditar.disabled = !podeSalvarEditar;
            if (btnExcluir) btnExcluir.disabled = !hasDate;
        }

        function gerarHorariosAtendimento() {
            const selectSer = document.getElementById('Dis_ser');
            const inputStart = document.getElementById('Dis_start');
            const inputEnd = document.getElementById('Dis_end');
            const containerLista = document.getElementById('lista-horarios');
            const msgVazia = document.getElementById('lista-horarios-vazia');
            const acoesRapidas = document.getElementById('horarios-actions');

            const selectedOption = selectSer.options[selectSer.selectedIndex];
            const duration = selectedOption ? parseInt(selectedOption.getAttribute('data-duration')) : 0;
            const startMin = timeToMinutes(inputStart.value);
            const endMin = timeToMinutes(inputEnd.value);

            containerLista.innerHTML = '';

            if (!duration || startMin === null || endMin === null) {
                msgVazia.style.display = 'block';
                msgVazia.innerText = 'Selecione um serviço, horário de início e término para gerar os horários de atendimento (entre 08:00 e 19:00).';
                containerLista.style.display = 'none';
                acoesRapidas.style.display = 'none';
                validarFormulario();
                return;
            }

            if (startMin < MIN_TIME || startMin > MAX_TIME || endMin < MIN_TIME || endMin > MAX_TIME) {
                msgVazia.style.display = 'block';
                msgVazia.innerText = 'Os horários de início e término devem estar entre 08:00 (8h) e 19:00 (19h).';
                containerLista.style.display = 'none';
                acoesRapidas.style.display = 'none';
                validarFormulario();
                return;
            }

            if (startMin >= endMin) {
                msgVazia.style.display = 'block';
                msgVazia.innerText = 'O horário de início não pode ser maior ou igual ao horário de término.';
                containerLista.style.display = 'none';
                acoesRapidas.style.display = 'none';
                validarFormulario();
                return;
            }

            if (startMin + duration > endMin) {
                msgVazia.style.display = 'block';
                msgVazia.innerText = `O intervalo selecionado (${endMin - startMin} min) é menor que a duração do serviço (${duration} min).`;
                containerLista.style.display = 'none';
                acoesRapidas.style.display = 'none';
                validarFormulario();
                return;
            }

            let count = 0;
            for (let time = startMin; time + duration <= endMin; time += duration) {
                const startTimeStr = minutesToTime(time);
                const endTimeStr = minutesToTime(time + duration);

                const li = document.createElement('li');
                li.style.display = 'block';
                li.style.marginBottom = '10px';
                li.style.padding = '8px 0';
                li.style.borderBottom = '1px solid #eee';
                li.innerHTML = `
                    <div>
                        <strong>${startTimeStr} às ${endTimeStr}</strong>
                    </div>
                    <div>
                        <label for="status_${count}">Status: </label>
                        <select name="horarios[${count}][status]" id="status_${count}" class="status-select">
                            <option value="Disponivel" selected>Disponível</option>
                            <option value="Folga">Folga</option>
                            <option value="Ferias">Férias</option>
                            <option value="Bloqueado">Bloqueado</option>
                        </select>
                        <input type="hidden" name="horarios[${count}][start]" value="${startTimeStr}">
                        <input type="hidden" name="horarios[${count}][end]" value="${endTimeStr}">
                    </div>
                `;
                containerLista.appendChild(li);
                count++;
            }

            msgVazia.style.display = 'none';
            containerLista.style.display = 'block';
            acoesRapidas.style.display = 'block';

            validarFormulario();
        }

        // Função que define o status em todos os selects individuais
        function definirTodosStatus(status) {
            if (!status) return;
            const selects = document.querySelectorAll('.status-select');
            selects.forEach(select => {
                select.value = status;
            });
        }

        document.getElementById('Dis_date').addEventListener('focus', atualizarDataMinima);
        document.getElementById('Dis_date').addEventListener('input', validarFormulario);
        document.getElementById('Dis_date').addEventListener('change', validarFormulario);
        document.getElementById('Dis_ser').addEventListener('change', gerarHorariosAtendimento);
        document.getElementById('Dis_start').addEventListener('input', gerarHorariosAtendimento);
        document.getElementById('Dis_start').addEventListener('change', gerarHorariosAtendimento);
        document.getElementById('Dis_end').addEventListener('input', gerarHorariosAtendimento);
        document.getElementById('Dis_end').addEventListener('change', gerarHorariosAtendimento);

        // Atualiza a data mínima periodicamente (a cada 60s) para garantir o bloqueio assim que o dia virar
        setInterval(validarFormulario, 60000);

        // Validação e configuração inicial ao carregar a página
        validarFormulario();
    </script>
</body>

</html>