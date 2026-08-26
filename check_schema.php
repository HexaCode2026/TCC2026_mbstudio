<?php
require_once "config/conexao.php";
$stmt = $pdo->query("DESCRIBE appointments");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
