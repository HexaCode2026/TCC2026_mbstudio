<?php


require_once "../../config/conexao.php";

require_once "../../core/Session.php";

require_once "../../model/Dashboard.php";



Session::iniciar();



// proteção admin

if(

!isset($_SESSION['User_perm'])

||

$_SESSION['User_perm'] != 'A'

){


header(
"Location: ../../Index.php"
);


exit;


}




$dashboard = new Dashboard($pdo);



$usuarios =
$dashboard->totalUsuarios();


$clientes =
$dashboard->totalClientes();


$funcionarios =
$dashboard->totalFuncionarios();


$administradores =
$dashboard->totalAdministradores();


$servicos =
$dashboard->totalServicos();


$agendamentos =
$dashboard->totalAgendamentos();



?>



<!DOCTYPE html>

<html>

<head>

<title>
Dashboard Admin
</title>


<link rel="stylesheet" href="../../assets/css/admin.css">


</head>



<body>
    <?php include '../components/AuthButton.php'; ?><h1>
Dashboard Administrativo
</h1>



<h3>

Olá,
<?= $_SESSION['User_name']; ?>

</h3>



<hr>




<div>


<h2>
Usuários
</h2>

<p>

<?= $usuarios ?>

</p>


</div>





<div>


<h2>
Clientes
</h2>

<p>

<?= $clientes ?>

</p>


</div>






<div>


<h2>
Funcionários
</h2>

<p>

<?= $funcionarios ?>

</p>


</div>






<div>


<h2>
Administradores
</h2>

<p>

<?= $administradores ?>

</p>


</div>







<div>


<h2>
Serviços
</h2>

<p>

<?= $servicos ?>

</p>


</div>






<div>


<h2>
Agendamentos
</h2>

<p>

<?= $agendamentos ?>

</p>


</div>







<hr>


<h2>
Gerenciamento
</h2>



<a href="Usuarios.php">

Usuários

</a>


<br>


<a href="Funcionarios.php">

Funcionários

</a>


<br>


<a href="Servicos.php">

Serviços

</a>


<br>


<a href="Relatorios.php">

Relatórios

</a>


<br><br>


<a href="../../controller/Logout.php">

Sair

</a>




</body>

</html>