<?php


$host = "localhost";
$banco = "tcc_agendamento";
$usuario = "root";
$senha = "";



try{


    $pdo = new PDO(

        "mysql:host=$host;dbname=$banco;charset=utf8",

        $usuario,

        $senha

    );


    $pdo->setAttribute(

        PDO::ATTR_ERRMODE,

        PDO::ERRMODE_EXCEPTION

    );



}catch(PDOException $erro){


    die(

        "Erro na conexão: ".$erro->getMessage()

    );


}


?>