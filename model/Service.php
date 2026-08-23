<?php

class Service {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // =====================================
    // CRIAR SERVIÇO (E VINCULAR FUNCIONÁRIOS)
    // =====================================
    public function store($name, $description, $price, $duration, $image, $employee_ids = []) {
        try {
            $this->pdo->beginTransaction();

            $sql = "INSERT INTO services (Ser_name, Ser_description, Ser_price, Ser_duration, Ser_image, Ser_active) 
                    VALUES (?, ?, ?, ?, ?, 1)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$name, $description, $price, $duration, $image]);

            $serviceId = $this->pdo->lastInsertId();

            if (!empty($employee_ids)) {
                $sqlEmp = "INSERT INTO employee_services (Emp_id, Ser_id) VALUES (?, ?)";
                $stmtEmp = $this->pdo->prepare($sqlEmp);
                
                // O painel envia o User_id de usuários com perm F. Precisamos garantir que eles existem em employees
                foreach ($employee_ids as $userId) {
                    $empId = $this->getOrCreateEmployeeId($userId);
                    if ($empId) {
                        $stmtEmp->execute([$empId, $serviceId]);
                    }
                }
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    // =====================================
    // LISTAR SERVIÇOS
    // =====================================
    public function index() {
        $sql = "SELECT 
                    s.Ser_id, s.Ser_name, s.Ser_description, s.Ser_price, s.Ser_duration, s.Ser_image, s.Ser_active,
                    GROUP_CONCAT(u.User_name SEPARATOR ', ') AS Employees
                FROM services s
                LEFT JOIN employee_services es ON s.Ser_id = es.Ser_id
                LEFT JOIN employees e ON es.Emp_id = e.Emp_id
                LEFT JOIN users u ON e.User_id = u.User_id
                GROUP BY s.Ser_id, s.Ser_name, s.Ser_description, s.Ser_price, s.Ser_duration, s.Ser_image, s.Ser_active
                ORDER BY s.Ser_id DESC";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =====================================
    // ATUALIZAR SERVIÇO
    // =====================================
    public function update($id, $name, $description, $price, $duration, $image, $active, $employee_ids = []) {
        try {
            $this->pdo->beginTransaction();

            $sql = "UPDATE services 
                    SET Ser_name = ?, Ser_description = ?, Ser_price = ?, 
                        Ser_duration = ?, Ser_image = ?, Ser_active = ?
                    WHERE Ser_id = ?";
                    
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$name, $description, $price, $duration, $image, $active, $id]);

            // Remover vínculos antigos
            $sqlDelete = "DELETE FROM employee_services WHERE Ser_id = ?";
            $stmtDelete = $this->pdo->prepare($sqlDelete);
            $stmtDelete->execute([$id]);

            // Inserir novos vínculos (Lembrando que chegam como User_id)
            if (!empty($employee_ids)) {
                $sqlEmp = "INSERT INTO employee_services (Emp_id, Ser_id) VALUES (?, ?)";
                $stmtEmp = $this->pdo->prepare($sqlEmp);
                
                foreach ($employee_ids as $userId) {
                    $empId = $this->getOrCreateEmployeeId($userId);
                    if ($empId) {
                        $stmtEmp->execute([$empId, $id]);
                    }
                }
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    // =====================================
    // EXCLUIR SERVIÇO
    // =====================================
    public function destroy($id) {
        $checkSql = "SELECT COUNT(*) FROM appointments WHERE Ser_id = ? AND Appo_status = 'Pendente'";
        $checkStmt = $this->pdo->prepare($checkSql);
        $checkStmt->execute([$id]);
        
        if ($checkStmt->fetchColumn() > 0) {
            return false; // Existem agendamentos pendentes
        }

        $sql = "DELETE FROM services WHERE Ser_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    // =====================================
    // FUNÇÃO AUXILIAR: GARANTIR QUE É EMPREGADO
    // =====================================
    private function getOrCreateEmployeeId($userId) {
        $checkSql = "SELECT Emp_id FROM employees WHERE User_id = ?";
        $checkStmt = $this->pdo->prepare($checkSql);
        $checkStmt->execute([$userId]);
        $empId = $checkStmt->fetchColumn();

        if (!$empId) {
            $insertEmpSql = "INSERT INTO employees (User_id) VALUES (?)";
            $insertEmpStmt = $this->pdo->prepare($insertEmpSql);
            $insertEmpStmt->execute([$userId]);
            $empId = $this->pdo->lastInsertId();
        }
        
        return $empId;
    }

}
?>
