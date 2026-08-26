<?php
require_once 'core/Session.php';
Session::iniciar();

if (isset($_SESSION['User_perm'])) {
    if ($_SESSION['User_perm'] === 'A') {
        header("Location: View/admin/Home.php");
        exit;
    } elseif ($_SESSION['User_perm'] === 'F') {
        header("Location: View/funcionario/Home.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Menu principal </title>

    <link rel="stylesheet" href="assets/css/global.css">

</head>
<body>
    <?php include 'View/components/Header.php'; ?>
    
    <div class="hero-container" style="padding: 60px 5% 40px; display: flex; flex-direction: column; align-items: flex-start; justify-content: center; min-height: 70vh;">

    <h2  id="TxtMenor"> BELEZA | CONFIANÇA | ELEGÂNCIA </h2>
    <h1 id="TxtMaior"> Realçando sua <span class="Beleza"> Beleza </span> <br> com <span class="Beleza"> Elegância </span></h1>

    <div id="linha"> </div>

    <h2 id="descricao"> Serviços de Qualidade para Realçar <br> a sua Beleza no dia a dia </h2>

    <a href="#" onclick="checkAuthAndExecute(event, 'View/cliente/Servicos.php')" id="btn-servicos"> conheça nossos serviços! </a>

    </div>

    <div id="separadorRodape"> </div>  
    <div id="fundoRodape"> </div>

    <h2 id="textoRodape"> AGENDE SEU HORÁRIO </h2>

    <div id="linhaRodape"> </div>

    <a href="#" onclick="checkAuthAndExecute(event, 'View/cliente/Servicos.php')" id="btn-agendar"> AGENDAR AGORA </a>

    <div id="marcadorImagem" style="top: 200px;"> 
        <h1 id="texto" style="position: relative; margin-top: 0; right: 0; width: 100%; top: auto;"> Espaço Reservado pra Imagens do Local </h1>
    </div>

    <a href="View/funcionario/Disponibilidade.php"> disponibilidade </a>
    <a href="View/admin/Servicos.php"> atribuir serviços </a>

    <?php include 'View/components/LoginModal.php'; ?>
</body>
</html>