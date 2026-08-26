<?php

class Appointment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Lista todos os agendamentos de um determinado funcionário com dados complementares do cliente e serviço.
     */
    public function listarPorFuncionario($empId, $filtroData = null, $filtroStatus = null) {
        $sql = "SELECT 
                    a.Appo_id,
                    a.Cli_id,
                    a.Emp_id,
                    a.Ser_id,
                    a.Appo_date,
                    a.Appo_start,
                    a.Appo_end,
                    a.Appo_status,
                    a.Appo_cancel_by,
                    a.Appo_cancel_reason,
                    a.Appo_observation,
                    a.Appo_created,
                    a.Appo_updated,
                    c_user.User_name AS client_name,
                    c_user.User_email AS client_email,
                    c.Cli_phone AS client_phone,
                    s.Ser_name,
                    s.Ser_price,
                    s.Ser_duration
                FROM appointments a
                INNER JOIN clients c ON a.Cli_id = c.Cli_id
                INNER JOIN users c_user ON c.User_id = c_user.User_id
                INNER JOIN services s ON a.Ser_id = s.Ser_id
                WHERE a.Emp_id = :emp_id";

        $params = [':emp_id' => $empId];

        if (!empty($filtroData)) {
            $sql .= " AND a.Appo_date = :data";
            $params[':data'] = $filtroData;
        }

        if (!empty($filtroStatus)) {
            $sql .= " AND a.Appo_status = :status";
            $params[':status'] = $filtroStatus;
        }

        $sql .= " ORDER BY a.Appo_date DESC, a.Appo_start ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca dados detalhados de um agendamento específico.
     */
    public function buscarPorId($appoId) {
        $sql = "SELECT 
                    a.*,
                    c_user.User_name AS client_name,
                    c_user.User_email AS client_email,
                    c.Cli_phone AS client_phone,
                    s.Ser_name,
                    s.Ser_price,
                    s.Ser_duration,
                    e_user.User_name AS employee_name
                FROM appointments a
                INNER JOIN clients c ON a.Cli_id = c.Cli_id
                INNER JOIN users c_user ON c.User_id = c_user.User_id
                INNER JOIN services s ON a.Ser_id = s.Ser_id
                INNER JOIN employees e ON a.Emp_id = e.Emp_id
                INNER JOIN users e_user ON e.User_id = e_user.User_id
                WHERE a.Appo_id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $appoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza o status de um agendamento.
     */
    public function atualizarStatus($appoId, $empId, $status, $cancelBy = null, $cancelReason = null) {
        // Garantir que o funcionário só atualize seus próprios agendamentos (ou admin)
        $sqlCheck = "SELECT Appo_id FROM appointments WHERE Appo_id = :id AND Emp_id = :emp_id";
        $stmtCheck = $this->pdo->prepare($sqlCheck);
        $stmtCheck->execute([':id' => $appoId, ':emp_id' => $empId]);
        if (!$stmtCheck->fetch()) {
            return false;
        }

        if ($cancelBy !== null) {
            $sql = "UPDATE appointments 
                    SET Appo_status = :status, 
                        Appo_cancel_by = :cancel_by, 
                        Appo_cancel_reason = :cancel_reason 
                    WHERE Appo_id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':status' => $status,
                ':cancel_by' => $cancelBy,
                ':cancel_reason' => $cancelReason,
                ':id' => $appoId
            ]);
        } else {
            $sql = "UPDATE appointments 
                    SET Appo_status = :status 
                    WHERE Appo_id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':status' => $status,
                ':id' => $appoId
            ]);
        }
    }

    /**
     * Retorna contadores e estatísticas da agenda para o dashboard.
     */
    public function obterEstatisticas($empId) {
        $hoje = date('Y-m-d');
        
        $sql = "SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN Appo_date = :hoje AND Appo_status NOT LIKE 'Cancelado%' THEN 1 ELSE 0 END) AS hoje,
                    SUM(CASE WHEN Appo_status = 'Pendente' THEN 1 ELSE 0 END) AS pendentes,
                    SUM(CASE WHEN Appo_status = 'Confirmado' THEN 1 ELSE 0 END) AS confirmados,
                    SUM(CASE WHEN Appo_status = 'Em Atendimento' THEN 1 ELSE 0 END) AS em_atendimento,
                    SUM(CASE WHEN Appo_status = 'Concluido' THEN 1 ELSE 0 END) AS concluidos,
                    SUM(CASE WHEN Appo_status LIKE 'Cancelado%' THEN 1 ELSE 0 END) AS cancelados
                FROM appointments 
                WHERE Emp_id = :emp_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':emp_id' => $empId, ':hoje' => $hoje]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => (int)($stats['total'] ?? 0),
            'hoje' => (int)($stats['hoje'] ?? 0),
            'pendentes' => (int)($stats['pendentes'] ?? 0),
            'confirmados' => (int)($stats['confirmados'] ?? 0),
            'em_atendimento' => (int)($stats['em_atendimento'] ?? 0),
            'concluidos' => (int)($stats['concluidos'] ?? 0),
            'cancelados' => (int)($stats['cancelados'] ?? 0)
        ];
    }

    // =====================================
    // LISTAR AGENDAMENTOS DO CLIENTE
    // =====================================
    public function listarMeusAgendamentos($cliId) {
        $query = "SELECT a.*, 
                         e_user.User_name AS employee_name, 
                         e.Emp_photo AS employee_photo,
                         s.Ser_name, 
                         s.Ser_price, 
                         s.Ser_duration
                  FROM appointments a
                  JOIN employees e ON a.Emp_id = e.Emp_id
                  JOIN users e_user ON e.User_id = e_user.User_id
                  JOIN services s ON a.Ser_id = s.Ser_id
                  WHERE a.Cli_id = ?
                  ORDER BY a.Appo_date DESC, a.Appo_start DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$cliId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =====================================
    // VERIFICAR SE O HORÁRIO ESTÁ LIVRE
    // =====================================
    public function verificarConflitoHorario($empId, $date, $start, $end) {
        $sqlCheck = "SELECT Appo_id FROM appointments 
                     WHERE Emp_id = ? AND Appo_date = ? 
                       AND Appo_status NOT IN ('Cancelado pelo Cliente', 'Cancelado pelo Funcionario', 'Cancelado pelo Administrador', 'Nao Compareceu')
                       AND (
                           (Appo_start <= ? AND Appo_end > ?) OR
                           (Appo_start < ? AND Appo_end >= ?) OR
                           (? <= Appo_start AND ? >= Appo_end)
                       )";
        $stmtCheck = $this->pdo->prepare($sqlCheck);
        $stmtCheck->execute([
            $empId, $date, 
            $start, $start, 
            $end, $end, 
            $start, $end
        ]);
        return $stmtCheck->rowCount() > 0;
    }

    // =====================================
    // CRIAR AGENDAMENTO
    // =====================================
    public function criarAgendamento($cliId, $empId, $serId, $date, $start, $end) {
        $sqlInsert = "INSERT INTO appointments (Cli_id, Emp_id, Ser_id, Appo_date, Appo_start, Appo_end, Appo_status) 
                      VALUES (?, ?, ?, ?, ?, ?, 'Pendente')";
        $stmtInsert = $this->pdo->prepare($sqlInsert);
        return $stmtInsert->execute([$cliId, $empId, $serId, $date, $start, $end]);
    }

    // =====================================
    // BUSCAR AGENDAMENTO POR ID E CLIENTE (Para cancelamento)
    // =====================================
    public function verificarDonoAgendamento($appoId, $userId) {
        $check = $this->pdo->prepare("SELECT a.Appo_id FROM appointments a JOIN clients c ON a.Cli_id = c.Cli_id WHERE a.Appo_id = ? AND c.User_id = ?");
        $check->execute([$appoId, $userId]);
        return $check->fetchColumn();
    }

    public function buscarParaHorarios($empId, $date) {
        $sqlAppo = "SELECT Appo_start, Appo_end FROM appointments 
                    WHERE Emp_id = ? AND Appo_date = ? 
                    AND Appo_status NOT IN ('Cancelado pelo Cliente', 'Cancelado pelo Funcionario', 'Cancelado pelo Administrador', 'Nao Compareceu')";
        $stmtAppo = $this->pdo->prepare($sqlAppo);
        $stmtAppo->execute([$empId, $date]);
        return $stmtAppo->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarTodosAgendamentosAtivosDoDia($date) {
        $sqlAppo = "SELECT Emp_id, Appo_start, Appo_end FROM appointments 
                    WHERE Appo_date = ? 
                    AND Appo_status NOT IN ('Cancelado pelo Cliente', 'Cancelado pelo Funcionario', 'Cancelado pelo Administrador', 'Nao Compareceu')";
        $stmtAppo = $this->pdo->prepare($sqlAppo);
        $stmtAppo->execute([$date]);
        return $stmtAppo->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
