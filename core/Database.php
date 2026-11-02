<?php
require_once __DIR__ . "/../config/config.php";

class Database {
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    private string $host;
    private string $db;
    private string $user;
    private string $pass;
    private int $timeout;

    public function __construct() {
        $this->host    = defined('DB_HOST') ? (string)DB_HOST : 'localhost';
        $this->db      = defined('DB_NAME') ? (string)DB_NAME : 'tcc_agendamento';
        $this->user    = defined('DB_USER') ? (string)DB_USER : 'root';
        $this->pass    = defined('DB_PASS') ? (string)DB_PASS : '';
        $this->timeout = defined('DB_TIMEOUT') ? (int)DB_TIMEOUT : 5;
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function conectar(): PDO {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->db};charset=utf8mb4",
                $this->user,
                $this->pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_PERSISTENT         => false,
                    PDO::ATTR_TIMEOUT            => $this->timeout,
                ]
            );
            return $this->pdo;
        } catch (PDOException $e) {
            $logDir = __DIR__ . '/../logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $entry = "[" . date('Y-m-d H:i:s') . "] [DB_CRITICAL] Database Connection Error: " . $e->getMessage() . PHP_EOL;
            @file_put_contents($logDir . '/erros.log', $entry, FILE_APPEND | LOCK_EX);

            if (!headers_sent()) {
                http_response_code(503);
                $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (stripos($accept, 'application/json') !== false || stripos($contentType, 'application/json') !== false) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Serviço temporariamente indisponível. Por favor, tente novamente em instantes.'
                    ]);
                    exit;
                }
            }
            die("Serviço temporariamente indisponível. Por favor, tente novamente em instantes.");
        }
    }

    public function desconectar(): void {
        $this->pdo = null;
    }
}
?>
