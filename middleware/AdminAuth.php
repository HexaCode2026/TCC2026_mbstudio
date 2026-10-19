<?php
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/FirstLoginFilter.php";

Session::iniciar();

if (
    !isset($_SESSION['User_id'])
    || !isset($_SESSION['User_perm'])
    || $_SESSION['User_perm'] !== 'A'
) {
    header("Location: " . Session::getBasePath() . "/Index.php");
    exit;
}

FirstLoginFilter::handle();
?>
