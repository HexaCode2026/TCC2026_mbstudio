<?php
require_once __DIR__ . "/../core/Session.php";
Session::sair();
header("Location: ../Index.php");
exit;
