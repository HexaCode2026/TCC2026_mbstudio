<?php


require_once __DIR__ . "/../core/Session.php";


Session::iniciar();


if(

!isset($_SESSION['User_id'])

||

!isset($_SESSION['User_perm'])

||

$_SESSION['User_perm'] != 'F'

){


    header(
    "Location: ../Index.php"
    );


    exit;


}


?>
