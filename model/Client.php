<?php

class Client {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getOrCreateClientId($userId) {
        $stmtCli = $this->pdo->prepare("SELECT Cli_id FROM clients WHERE User_id = ?");
        $stmtCli->execute([$userId]);
        $cliente = $stmtCli->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            $stmtInsertCli = $this->pdo->prepare("INSERT INTO clients (User_id) VALUES (?)");
            $stmtInsertCli->execute([$userId]);
            return $this->pdo->lastInsertId();
        }

        return $cliente['Cli_id'];
    }

    public function buscarPorUserId($userId) {
        $stmtCli = $this->pdo->prepare("SELECT * FROM clients WHERE User_id = ?");
        $stmtCli->execute([$userId]);
        return $stmtCli->fetch(PDO::FETCH_ASSOC);
    }
}
?>
