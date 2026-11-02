<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/../core/Database.php";

$pdo = Database::getInstance()->conectar();
?>
