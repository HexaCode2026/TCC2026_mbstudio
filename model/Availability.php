<?php

class Availability {

    private $pdo;
    private static ?bool $temColunaSerIdCache = null;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Verifica e garante que a coluna Ser_id exista na tabela availabilities,
     * utilizando cache estático em memória para evitar queries repetidas na mesma requisição.
     */
    private function verificarColunaSerId(): bool {
        if (self::$temColunaSerIdCache !== null) {
            return self::$temColunaSerIdCache;
        }

        try {
            $this->pdo->query("SELECT Ser_id FROM availabilities LIMIT 1");
            self::$temColunaSerIdCache = true;
            return true;
        } catch (Exception $e) {
            try {
                $this->pdo->exec("ALTER TABLE availabilities ADD COLUMN Ser_id INT NULL AFTER Emp_id");
                $this->pdo->exec("ALTER TABLE availabilities ADD CONSTRAINT fk_availabilities_service FOREIGN KEY (Ser_id) REFERENCES services(Ser_id) ON DELETE SET NULL");
                self::$temColunaSerIdCache = true;
                return true;
            } catch (Exception $ex) {
                try {
                    $this->pdo->exec("ALTER TABLE availabilities ADD COLUMN Ser_id INT NULL");
                    self::$temColunaSerIdCache = true;
                    return true;
                } catch (Exception $ex2) {
                    self::$temColunaSerIdCache = false;
                    return false;
                }
            }
        }
    }

    /**
     * Salva ou atualiza os blocos de horários de disponibilidade para um funcionário em uma data
     */
    public function salvarDisponibilidades($empId, $serId, $date, array $horarios) {
        $temSerId = $this->verificarColunaSerId();
        try {
            $this->pdo->beginTransaction();

            if ($temSerId && $serId) {
                $deleteSql = "DELETE FROM availabilities WHERE Emp_id = ? AND Ser_id = ? AND Ava_date = ?";
                $deleteStmt = $this->pdo->prepare($deleteSql);
                $deleteStmt->execute([$empId, $serId, $date]);

                $insertSql = "INSERT INTO availabilities (Emp_id, Ser_id, Ava_date, Ava_start, Ava_end, Ava_status) 
                              VALUES (?, ?, ?, ?, ?, ?)";
                $insertStmt = $this->pdo->prepare($insertSql);

                foreach ($horarios as $h) {
                    $start = $h['start'] ?? null;
                    $end = $h['end'] ?? null;
                    $status = $h['status'] ?? 'Disponivel';

                    if ($start && $end) {
                        $insertStmt->execute([
                            $empId,
                            $serId,
                            $date,
                            $start,
                            $end,
                            $status
                        ]);
                    }
                }
            } else {
                $deleteSql = "DELETE FROM availabilities WHERE Emp_id = ? AND Ava_date = ?";
                $deleteStmt = $this->pdo->prepare($deleteSql);
                $deleteStmt->execute([$empId, $date]);

                $insertSql = "INSERT INTO availabilities (Emp_id, Ava_date, Ava_start, Ava_end, Ava_status) 
                              VALUES (?, ?, ?, ?, ?)";
                $insertStmt = $this->pdo->prepare($insertSql);

                foreach ($horarios as $h) {
                    $start = $h['start'] ?? null;
                    $end = $h['end'] ?? null;
                    $status = $h['status'] ?? 'Disponivel';

                    if ($start && $end) {
                        $insertStmt->execute([
                            $empId,
                            $date,
                            $start,
                            $end,
                            $status
                        ]);
                    }
                }
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

    public function editarDisponibilidades($empId, $serId, $date, array $horarios) {
        return $this->salvarDisponibilidades($empId, $serId, $date, $horarios);
    }

    public function excluirDisponibilidades($empId, $date) {
        try {
            $sql = "DELETE FROM availabilities WHERE Emp_id = ? AND Ava_date = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$empId, $date]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function editarPorId($avaId, $empId, $status, $date = null, $start = null, $end = null) {
        try {
            if ($date && $start && $end) {
                $sql = "UPDATE availabilities 
                        SET Ava_status = ?, Ava_date = ?, Ava_start = ?, Ava_end = ? 
                        WHERE Ava_id = ? AND Emp_id = ?";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([$status, $date, $start, $end, $avaId, $empId]);
            } else {
                $sql = "UPDATE availabilities 
                        SET Ava_status = ? 
                        WHERE Ava_id = ? AND Emp_id = ?";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([$status, $avaId, $empId]);
            }
        } catch (Exception $e) {
            return false;
        }
    }

    public function excluirPorId($avaId, $empId) {
        try {
            if (is_array($avaId)) {
                $in = str_repeat('?,', count($avaId) - 1) . '?';
                $sql = "DELETE FROM availabilities WHERE Ava_id IN ($in) AND Emp_id = ?";
                $params = array_merge($avaId, [$empId]);
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute($params);
            } else {
                $sql = "DELETE FROM availabilities WHERE Ava_id = ? AND Emp_id = ?";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([$avaId, $empId]);
            }
        } catch (Exception $e) {
            return false;
        }
    }

    public function listarPorFuncionario($empId, $dataMinima = null) {
        $temSerId = $this->verificarColunaSerId();

        if ($temSerId) {
            if ($dataMinima) {
                $sql = "SELECT a.*, s.Ser_name FROM availabilities a 
                        LEFT JOIN services s ON a.Ser_id = s.Ser_id
                        WHERE a.Emp_id = ? AND a.Ava_date >= ? 
                        ORDER BY a.Ava_date ASC, a.Ava_start ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$empId, $dataMinima]);
            } else {
                $sql = "SELECT a.*, s.Ser_name FROM availabilities a 
                        LEFT JOIN services s ON a.Ser_id = s.Ser_id
                        WHERE a.Emp_id = ? 
                        ORDER BY a.Ava_date ASC, a.Ava_start ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$empId]);
            }
        } else {
            if ($dataMinima) {
                $sql = "SELECT a.*, NULL AS Ser_name FROM availabilities a 
                        WHERE a.Emp_id = ? AND a.Ava_date >= ? 
                        ORDER BY a.Ava_date ASC, a.Ava_start ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$empId, $dataMinima]);
            } else {
                $sql = "SELECT a.*, NULL AS Ser_name FROM availabilities a 
                        WHERE a.Emp_id = ? 
                        ORDER BY a.Ava_date ASC, a.Ava_start ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$empId]);
            }
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorData($empId, $date) {
        $sql = "SELECT * FROM availabilities 
                WHERE Emp_id = ? AND Ava_date = ? 
                ORDER BY Ava_start ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$empId, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
