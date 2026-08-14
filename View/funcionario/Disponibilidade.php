<?php
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

// Buscar serviços vinculados ao funcionário através de employee_services e services
// Chave estrangeira Ser_id em employee_services e chave primária Ser_id em services, trazendo Ser_duration
$sqlServicos = "SELECT s.Ser_id, s.Ser_name, s.Ser_duration 
                FROM services s
                INNER JOIN employee_services es ON s.Ser_id = es.Ser_id
                WHERE es.Emp_id = ? AND (s.Ser_active = 1 OR s.Ser_active IS NULL)
                ORDER BY s.Ser_name ASC";
$stmtServicos = $pdo->prepare($sqlServicos);
$stmtServicos->execute([$empId]);
$servicos = $stmtServicos->fetchAll(PDO::FETCH_ASSOC);

// Fallback: se o funcionário ainda não tiver serviços vinculados em employee_services, listar serviços ativos
if (empty($servicos)) {
    $sqlAll = "SELECT Ser_id, Ser_name, Ser_duration FROM services WHERE (Ser_active = 1 OR Ser_active IS NULL) ORDER BY Ser_name ASC";
    $stmtAll = $pdo->query($sqlAll);
    $servicos = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
}
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

        <label for="Dis_date">Data</label><br>
        <input type="date" name="Dis_date" id="Dis_date" required>
        <br><br>

        <label for="Dis_ser">Serviços</label><br>
        <select name="Dis_ser" id="Dis_ser" required>
            <option value="">Selecione um serviço</option>
            <?php foreach ($servicos as $ser): ?>
                <option value="<?= htmlspecialchars($ser['Ser_id']) ?>" data-duration="<?= htmlspecialchars($ser['Ser_duration']) ?>">
                    <?= htmlspecialchars($ser['Ser_name']) ?> (<?= htmlspecialchars($ser['Ser_duration']) ?> min)
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        <label for="Dis_start">Horário de Início</label><br>
        <input type="time" name="Dis_start" id="Dis_start" required>
        <br><br>

        <label for="Dis_end">Horário de Fim</label><br>
        <input type="time" name="Dis_end" id="Dis_end" required>
        <br><br>

        <label for="Dis_scheduled-times"><strong>Horários de Atendimento</strong></label>
        <br><br>

        <!-- CONTAINER DINÂMICO DOS HORÁRIOS DE ATENDIMENTO -->
        <div id="Dis_scheduled-times">
            <!-- Controle de definição para todos os horários -->
            <div id="horarios-actions" style="display: none; margin-bottom: 15px;">
                <label><strong>Definir todos como:</strong></label>
                <div style="margin-top: 5px;">
                    <button type="button" onclick="definirTodosStatus('Disponivel')">Disponível</button>
                    <button type="button" onclick="definirTodosStatus('Folga')">Folga</button>
                    <button type="button" onclick="definirTodosStatus('Ferias')">Férias</button>
                    <button type="button" onclick="definirTodosStatus('Bloqueado')">Bloqueado</button>
                </div>
            </div>

            <div id="lista-horarios-vazia" style="color: #666; font-style: italic;">
                Selecione um serviço, horário de início e término para gerar os horários de atendimento.
            </div>

            <ul id="lista-horarios" style="list-style: none; padding: 0; margin: 0; display: none; flex-direction: column; gap: 10px;">
                <!-- Horários gerados dinamicamente um abaixo do outro -->
            </ul>
        </div>
        <br><br>

        <button type="submit">Salvar Disponibilidade</button>
    </form>

    <script>
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
                msgVazia.innerText = 'Selecione um serviço, horário de início e término para gerar os horários de atendimento.';
                containerLista.style.display = 'none';
                acoesRapidas.style.display = 'none';
                return;
            }

            if (startMin >= endMin) {
                msgVazia.style.display = 'block';
                msgVazia.innerText = 'O horário de início deve ser menor que o horário de término.';
                containerLista.style.display = 'none';
                acoesRapidas.style.display = 'none';
                return;
            }

            if (startMin + duration > endMin) {
                msgVazia.style.display = 'block';
                msgVazia.innerText = `O intervalo selecionado (${endMin - startMin} min) é menor que a duração do serviço (${duration} min).`;
                containerLista.style.display = 'none';
                acoesRapidas.style.display = 'none';
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
                    <div style="margin-bottom: 4px;">
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
        }

        // Função que define o status em todos os selects individuais
        function definirTodosStatus(status) {
            if (!status) return;
            const selects = document.querySelectorAll('.status-select');
            selects.forEach(select => {
                select.value = status;
            });
        }

        document.getElementById('Dis_ser').addEventListener('change', gerarHorariosAtendimento);
        document.getElementById('Dis_start').addEventListener('input', gerarHorariosAtendimento);
        document.getElementById('Dis_end').addEventListener('input', gerarHorariosAtendimento);
    </script>
</body>

</html>