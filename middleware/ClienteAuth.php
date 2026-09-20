<?php
require_once __DIR__ . "/../core/Session.php";
Session::iniciar();

if (
    !isset($_SESSION['User_id'])
    ||
    !isset($_SESSION['User_perm'])
    ||
    $_SESSION['User_perm'] != 'C'
) {
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/../'));
    $basePath = str_ireplace($docRoot, '', $projectRoot);
    header("Location: " . $basePath . "/Index.php");
    exit;
}
?>
