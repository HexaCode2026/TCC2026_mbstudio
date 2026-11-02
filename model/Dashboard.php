<?php

class Dashboard
{
    private $pdo;
    private ?array $resumoCache = null;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Consolida as 6 contagens do painel administrativo em uma única query agregada,
     * reduzindo de 6 round-trips ao banco para apenas 1.
     */
    public function obterResumoGeral(): array
    {
        if ($this->resumoCache !== null) {
            return $this->resumoCache;
        }

        $sql = "
            SELECT
                COUNT(*) AS total_usuarios,
                 COALESCE(SUM(CASE WHEN User_perm = 'C' THEN 1 ELSE 0 END), 0) AS total_clientes,
                 COALESCE(SUM(CASE WHEN User_perm = 'F' THEN 1 ELSE 0 END), 0) AS total_funcionarios,
                 COALESCE(SUM(CASE WHEN User_perm = 'A' THEN 1 ELSE 0 END), 0) AS total_administradores,
                (SELECT COUNT(*) FROM services) AS total_servicos,
                (SELECT COUNT(*) FROM appointments) AS total_agendamentos
            FROM users
        ";

        $stmt = $this->pdo->query($sql);
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : [];

        $this->resumoCache = [
            'usuarios'        => (int)($row['total_usuarios'] ?? 0),
            'clientes'        => (int)($row['total_clientes'] ?? 0),
            'funcionarios'    => (int)($row['total_funcionarios'] ?? 0),
            'administradores' => (int)($row['total_administradores'] ?? 0),
            'servicos'        => (int)($row['total_servicos'] ?? 0),
            'agendamentos'    => (int)($row['total_agendamentos'] ?? 0),
        ];

        return $this->resumoCache;
    }

    public function totalUsuarios(): int
    {
        return $this->obterResumoGeral()['usuarios'];
    }

    public function totalClientes(): int
    {
        return $this->obterResumoGeral()['clientes'];
    }

    public function totalFuncionarios(): int
    {
        return $this->obterResumoGeral()['funcionarios'];
    }

    public function totalAdministradores(): int
    {
        return $this->obterResumoGeral()['administradores'];
    }

    public function totalServicos(): int
    {
        return $this->obterResumoGeral()['servicos'];
    }

    public function totalAgendamentos(): int
    {
        return $this->obterResumoGeral()['agendamentos'];
    }
}
?>
