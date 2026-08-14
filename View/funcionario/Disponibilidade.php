<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

// Proteção para Funcionário
if(!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'F'){
    header("Location: ../Login.php");
    exit;
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
    <h1>Disponibilidade</h1>

    <form action="../../Controller/DisponibilidadeController.php" method="POST">
        <input type="hidden" name="Fun_id" value="<?= $_SESSION['User_id'] ?>">

        <label for="Dis_date">Data</label>
        <input type="date" name="Dis_date" id="Dis_date" required>
        <br><br>

        <label for="Dis_ser">Serviços</label>
        <select name="Dis_ser" id="Dis_ser" required>
            <option value="">Selecione um serviço</option>
        </select>
        <br><br>

        <label for="Dis_start">Horário de Início</label>
        <input type="time" name="Dis_start" id="Dis_start" required>
        <br><br>

        <label for="Dis_end">Horário de Fim</label>
        <input type="time" name="Dis_end" id="Dis_end" required>
        <br><br>

        <label for="Dis_scheduled-times">Horários de Atendimento</label>
        <br><br>

        <button type="submit">Salvar</button>
    </form>
</body>
</html>