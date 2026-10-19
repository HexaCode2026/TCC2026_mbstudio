<?php
require_once __DIR__ . "/../config/config.php";

class Database {
    private $host;
    private $db;
    private $user;
    private $pass;

    public function __construct() {
        $this->host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $this->db   = defined('DB_NAME') ? DB_NAME : 'tcc_agendamento';
        $this->user = defined('DB_USER') ? DB_USER : 'root';
        $this->pass = defined('DB_PASS') ? DB_PASS : '';
    }

    public function conectar() {
        try {
            $pdo = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db . ";charset=utf8mb4",
                $this->user,
                $this->pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            return $pdo;
        } catch (PDOException $e) {
            $logDir = __DIR__ . '/../logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            @error_log(
                "[" . date('Y-m-d H:i:s') . "] Database Class Connection Error: " . $e->getMessage() . "\n",
                3,
                $logDir . '/erros.log'
            );
            http_response_code(503);
            die("Serviço temporariamente indisponível. Por favor, tente novamente em instantes.");
        }
    }
}
?>