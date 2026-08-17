<?php


class Dashboard{


    private $pdo;



    public function __construct($pdo){

        $this->pdo = $pdo;

    }




    // Total de usuários

    public function totalUsuarios(){


        $sql = "

        SELECT COUNT(*) AS total

        FROM users

        ";


        $stmt = $this->pdo->query($sql);


        return $stmt->fetch()['total'];


    }






    // Total clientes

    public function totalClientes(){


        $sql = "

        SELECT COUNT(*) AS total

        FROM users

        WHERE User_perm = 'C'

        ";


        $stmt = $this->pdo->query($sql);


        return $stmt->fetch()['total'];


    }






    // Total funcionários

    public function totalFuncionarios(){


        $sql = "

        SELECT COUNT(*) AS total

        FROM users

        WHERE User_perm = 'F'

        ";


        $stmt = $this->pdo->query($sql);


        return $stmt->fetch()['total'];


    }







    // Total administradores

    public function totalAdministradores(){


        $sql = "

        SELECT COUNT(*) AS total

        FROM users

        WHERE User_perm = 'A'

        ";


        $stmt = $this->pdo->query($sql);


        return $stmt->fetch()['total'];


    }







    // Total serviços

    public function totalServicos(){


        $sql = "

        SELECT COUNT(*) AS total

        FROM services

        ";


        $stmt = $this->pdo->query($sql);


        return $stmt->fetch()['total'];


    }







    // Total agendamentos

    public function totalAgendamentos(){


        $sql = "

        SELECT COUNT(*) AS total

        FROM appointments

        ";


        $stmt = $this->pdo->query($sql);


        return $stmt->fetch()['total'];


    }




}

?>