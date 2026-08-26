<?php

class EmployeeBlock {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Lista todos os bloqueios futuros de um funcionário.
     */
    public function listarPorFuncionario($empId) {
        $hoje = date('Y-m-d');
        // Lista apenas bloqueios de hoje em diante
        $sql = "SELECT * FROM employee_blocks 
                WHERE Emp_id = ? AND Block_date >= ?
                ORDER BY Block_date ASC, Block_start ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$empId, $hoje]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Insere um novo bloqueio na agenda.
     */
    public function store($empId, $date, $start, $end, $reason) {
        try {
            $sql = "INSERT INTO employee_blocks (Emp_id, Block_date, Block_start, Block_end, Block_reason) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$empId, $date, $start, $end, $reason]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Exclui um bloqueio, garantindo que ele pertença ao funcionário logado.
     */
    public function destroy($blockId, $empId) {
        try {
            $sql = "DELETE FROM employee_blocks WHERE Block_id = ? AND Emp_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$blockId, $empId]);
        } catch (Exception $e) {
            return false;
        }
    }

}
?>
