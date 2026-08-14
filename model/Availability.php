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
