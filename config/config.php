<?php

// Configurações do sistema

define("DB_HOST", "localhost");

define("DB_NAME", "tcc_agendamento");

define("DB_USER", "root");

define("DB_PASS", "");


$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/../'));
$basePath = str_ireplace($docRoot, '', $projectRoot);

define("BASE_URL", $protocol . "://" . $host . $basePath . "/");

?>