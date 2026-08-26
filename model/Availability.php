<?php

class Availability {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Salva ou atualiza os blocos de horários de disponibilidade para um funcionário em uma data
     * 
     * @param int $empId ID do funcionário (Emp_id)
     * @param string $date Data no formato YYYY-MM-DD
     * @param array $horarios Array de slots com start, end e status
     * @return bool
     */
    public function salvarDisponibilidades($empId, $date, array $horarios) {
        try {
            $this->pdo->beginTransaction();

            // Remover disponibilidades anteriores do mesmo funcionário na mesma data
            $deleteSql = "DELETE FROM availabilities WHERE Emp_id = ? AND Ava_date = ?";
            $deleteStmt = $this->pdo->prepare($deleteSql);
            $deleteStmt->execute([$empId, $date]);

            // Inserir cada faixa de horário com seu respectivo status
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

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Atualiza / edita os blocos de horários de disponibilidade para um funcionário em uma data
     * 
     * @param int $empId ID do funcionário (Emp_id)
     * @param string $date Data no formato YYYY-MM-DD
     * @param array $horarios Array de slots com start, end e status
     * @return bool
     */
    public function editarDisponibilidades($empId, $date, array $horarios) {
        return $this->salvarDisponibilidades($empId, $date, $horarios);
    }

    /**
     * Exclui todas as disponibilidades cadastradas para um funcionário em uma data
     * 
     * @param int $empId ID do funcionário (Emp_id)
     * @param string $date Data no formato YYYY-MM-DD
     * @return bool
     */
    public function excluirDisponibilidades($empId, $date) {
        try {
            $sql = "DELETE FROM availabilities WHERE Emp_id = ? AND Ava_date = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$empId, $date]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Atualiza / edita uma disponibilidade específica pelo Ava_id
     * 
     * @param int $avaId ID da disponibilidade (Ava_id)
     * @param int $empId ID do funcionário (Emp_id)
     * @param string $status Novo status
     * @param string|null $date Nova data (opcional)
     * @param string|null $start Novo horário de início (opcional)
     * @param string|null $end Novo horário de término (opcional)
     * @return bool
     */
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

    /**
     * Exclui uma disponibilidade específica pelo Ava_id
     * 
     * @param int|array $avaId ID da disponibilidade (Ava_id) ou array de IDs
     * @param int $empId ID do funcionário (Emp_id)
     * @return bool
     */
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

    /**
     * Busca disponibilidades cadastradas para um funcionário a partir de uma data ou futuras
     */
    public function listarPorFuncionario($empId, $dataMinima = null) {
        if ($dataMinima) {
            $sql = "SELECT * FROM availabilities 
                    WHERE Emp_id = ? AND Ava_date >= ? 
                    ORDER BY Ava_date ASC, Ava_start ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$empId, $dataMinima]);
        } else {
            $sql = "SELECT * FROM availabilities 
                    WHERE Emp_id = ? 
                    ORDER BY Ava_date ASC, Ava_start ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$empId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca disponibilidades cadastradas para um funcionário em uma data
     */
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
