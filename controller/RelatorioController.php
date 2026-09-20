<?php
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/Relatorio.php";

class RelatorioController {
    private $relatorioModel;

    public function __construct($pdo) {
        $this->relatorioModel = new Relatorio($pdo);
    }

    public function processarFiltro() {
        // Por padrão, pega o primeiro e último dia do mês atual
        $dataInicial = $_GET['data_inicial'] ?? date('Y-m-01');
        $dataFinal = $_GET['data_final'] ?? date('Y-m-t');
        $empId = !empty($_GET['emp_id']) ? $_GET['emp_id'] : null;

        $dados = $this->relatorioModel->getDadosRelatorio($dataInicial, $dataFinal, $empId);
        $totaisStatusRaw = $this->relatorioModel->getTotaisPorStatus($dataInicial, $dataFinal, $empId);
        
        // Garantir que todos os status principais apareçam no array
        $todosStatus = [
            'Pendente' => 0,
            'Confirmado' => 0,
            'Em Atendimento' => 0,
            'Concluido' => 0,
            'Cancelado pelo Cliente' => 0,
            'Cancelado pelo Funcionario' => 0,
            'Cancelado pelo Administrador' => 0,
            'Nao Compareceu' => 0
        ];

        foreach ($totaisStatusRaw as $row) {
            $todosStatus[$row['Status']] = $row['Total'];
        }

        $totaisStatus = [];
        foreach ($todosStatus as $status => $total) {
            $totaisStatus[] = ['Status' => $status, 'Total' => $total];
        }

        $totalRegistros = count($dados);
        
        // Exemplo de métrica extra: calcular o faturamento dos concluídos
        $faturamentoTotal = 0;
        foreach ($dados as $dado) {
            if (strpos(strtolower($dado['Status']), 'concluido') !== false) {
                $faturamentoTotal += $dado['Valor_Servico'];
            }
        }

        // Buscar funcionários para o select
        require_once __DIR__ . "/../model/Employee.php";
        $empModel = new Employee($this->relatorioModel->pdo ?? $GLOBALS['pdo'] ?? null);
        // Precisamos ter certeza de como o Employee usa o pdo. A view já passou $pdo.
        // É melhor instanciar direto passando $GLOBALS['pdo'] ou o pdo que temos na conexao
        global $pdo;
        $empModel = new Employee($pdo);
        $funcionarios = $empModel->listarEquipeAdmin();

        return [
            'dataInicial' => $dataInicial,
            'dataFinal' => $dataFinal,
            'empId' => $empId,
            'dados' => $dados,
            'totaisStatus' => $totaisStatus,
            'totalRegistros' => $totalRegistros,
            'faturamentoTotal' => $faturamentoTotal,
            'funcionarios' => $funcionarios
        ];
    }
}
?>
