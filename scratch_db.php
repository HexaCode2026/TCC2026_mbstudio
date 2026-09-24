<?php
require 'config/conexao.php';
$stmt = $pdo->query("SHOW CREATE TABLE appointments");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
$stmt2 = $pdo->query("SHOW CREATE VIEW vw_relatorio_admin");
print_r($stmt2->fetch(PDO::FETCH_ASSOC));
?>
