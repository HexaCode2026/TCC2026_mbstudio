<?php
class Relatorio {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getDadosRelatorio($dataInicial, $dataFinal, $empId = null) {
        $sql = "SELECT * FROM vw_relatorio_admin 
                WHERE Data_Agendamento BETWEEN :dataInicial AND :dataFinal";
        $params = ['dataInicial' => $dataInicial, 'dataFinal' => $dataFinal];

        if ($empId) {
            $sql .= " AND ID_Profissional = :empId";
            $params['empId'] = $empId;
        }
        
        $sql .= " ORDER BY Data_Agendamento DESC, Hora_Inicio DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotaisPorStatus($dataInicial, $dataFinal, $empId = null) {
        $sql = "SELECT Status, COUNT(*) as Total 
                FROM vw_relatorio_admin 
                WHERE Data_Agendamento BETWEEN :dataInicial AND :dataFinal";
        $params = ['dataInicial' => $dataInicial, 'dataFinal' => $dataFinal];

        if ($empId) {
            $sql .= " AND ID_Profissional = :empId";
            $params['empId'] = $empId;
        }

        $sql .= " GROUP BY Status";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
