<?php

class Client {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Busca dados do cliente usando LEFT JOIN.
     * Retorna os dados do usuario mesmo se ainda não existir na tabela clients.
     * Retorna false se o usuário não existir na tabela users.
     */
    public function buscarPorUserId($userId) {
        $sql = "SELECT u.User_name, u.User_email, c.Cli_id, c.Cli_phone, c.Cli_birth
                FROM users u
                LEFT JOIN clients c ON u.User_id = c.User_id
                WHERE u.User_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca apenas a senha do usuário na tabela users para verificação.
     */
    public function buscarSenha($userId) {
        $sql = "SELECT User_pass FROM users WHERE User_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['User_pass'] : false;
    }

    /**
     * Atualiza o perfil em uma única transação.
     * Faz UPDATE na users e INSERT ... ON DUPLICATE KEY UPDATE na clients.
     */
    public function atualizarPerfil($userId, $nome, $email, $telefone, $nascimento, $senhaHash = null) {
        try {
            $this->pdo->beginTransaction();

            // Atualizar tabela users
            $sqlUsers = "UPDATE users SET User_name = ?, User_email = ? WHERE User_id = ?";
            $stmtUsers = $this->pdo->prepare($sqlUsers);
            $stmtUsers->execute([$nome, $email, $userId]);

            // Atualizar/Criar tabela clients (UPSERT)
            $sqlClients = "INSERT INTO clients (User_id, Cli_phone, Cli_birth) 
                           VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           Cli_phone = VALUES(Cli_phone), 
                           Cli_birth = VALUES(Cli_birth)";
            $stmtClients = $this->pdo->prepare($sqlClients);
            $stmtClients->execute([$userId, $telefone, $nascimento]);

            // Atualizar senha se houver
            if ($senhaHash !== null) {
                $sqlSenha = "UPDATE users SET User_pass = ? WHERE User_id = ?";
                $stmtSenha = $this->pdo->prepare($sqlSenha);
                $stmtSenha->execute([$senhaHash, $userId]);
            }

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }
}
?>
