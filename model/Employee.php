<?php


class Employee{


    private $pdo;



    public function __construct($pdo){


        $this->pdo = $pdo;


    }




    // =====================================
    // BUSCAR FUNCIONÁRIO POR USER_ID
    // =====================================

    public function buscarPorUserId($userId){



        $sql = "

        SELECT

            u.User_name,

            u.User_email,

            e.Emp_id,

            e.Emp_specialty,

            e.Emp_bio

        FROM employees e

        INNER JOIN users u
        ON e.User_id = u.User_id

        WHERE e.User_id = ?

        ";



        $consulta = $this->pdo->prepare($sql);



        $consulta->execute([

            $userId

        ]);



        return $consulta->fetch(PDO::FETCH_ASSOC);



    }




    // =====================================
    // ATUALIZAR DADOS DO FUNCIONÁRIO
    // =====================================

    public function atualizar(

        $userId,

        $nome,

        $email,

        $especialidade,

        $bio

    ){

        try{

            $this->pdo->beginTransaction();


            $sqlUser = "

            UPDATE users

            SET

                User_name = ?,

                User_email = ?

            WHERE User_id = ?

            ";


            $updateUser = $this->pdo->prepare($sqlUser);

            $updateUser->execute([

                $nome,

                $email,

                $userId

            ]);


            $sqlEmp = "

            UPDATE employees

            SET

                Emp_specialty = ?,

                Emp_bio = ?

            WHERE User_id = ?

            ";


            $updateEmp = $this->pdo->prepare($sqlEmp);

            $updateEmp->execute([

                $especialidade,

                $bio,

                $userId

            ]);


            $this->pdo->commit();

            return true;


        }catch(PDOException $e){

            if($this->pdo->inTransaction()){

                $this->pdo->rollBack();

            }

            return false;

        }

    }




    // =====================================
    // ATUALIZAR PERFIL COMPLETO (TRANSAÇÃO ÚNICA)
    // =====================================

    public function atualizarPerfil(

        $userId,

        $nome,

        $email,

        $especialidade,

        $bio,

        $senhaHash = null

    ){

        try{

            $this->pdo->beginTransaction();


            // 1. Atualizar dados do usuário (nome e email)

            $sqlUser = "

            UPDATE users

            SET

                User_name = ?,

                User_email = ?

            WHERE User_id = ?

            ";


            $updateUser = $this->pdo->prepare($sqlUser);

            $updateUser->execute([

                $nome,

                $email,

                $userId

            ]);


            // 2. Atualizar dados do funcionário (especialidade e bio)

            $sqlEmp = "

            UPDATE employees

            SET

                Emp_specialty = ?,

                Emp_bio = ?

            WHERE User_id = ?

            ";


            $updateEmp = $this->pdo->prepare($sqlEmp);

            $updateEmp->execute([

                $especialidade,

                $bio,

                $userId

            ]);


            // 3. Se houver nova senha, atualizar na mesma transação

            if($senhaHash !== null){

                $sqlSenha = "

                UPDATE users

                SET User_pass = ?

                WHERE User_id = ?

                ";


                $updateSenha = $this->pdo->prepare($sqlSenha);

                $updateSenha->execute([

                    $senhaHash,

                    $userId

                ]);

            }


            $this->pdo->commit();

            return true;


        }catch(PDOException $e){

            if($this->pdo->inTransaction()){

                $this->pdo->rollBack();

            }

            return false;

        }

    }




    // =====================================
    // ATUALIZAR SENHA
    // =====================================

    public function atualizarSenha($userId, $senhaHash){



        $sql = "

        UPDATE users

        SET User_pass = ?

        WHERE User_id = ?

        ";



        $update = $this->pdo->prepare($sql);



        return $update->execute([

            $senhaHash,

            $userId

        ]);



    }




    // =====================================
    // BUSCAR SENHA ATUAL
    // =====================================

    public function buscarSenha($userId){



        $sql = "

        SELECT User_pass

        FROM users

        WHERE User_id = ?

        ";



        $consulta = $this->pdo->prepare($sql);



        $consulta->execute([

            $userId

        ]);



        return $consulta->fetch(PDO::FETCH_ASSOC);



    }




    // =====================================
    // LISTAR EQUIPE COM DETALHES (ADMIN)
    // =====================================
    public function listarEquipeAdmin() {
        $sql = "
        SELECT 
            e.Emp_id, 
            u.User_name, 
            e.Emp_photo, 
            e.Emp_specialty, 
            e.Emp_bio,
            u.User_id,
            u.User_active,
            (SELECT COUNT(Ava_id) FROM availabilities a WHERE a.Emp_id = e.Emp_id) AS total_horarios,
            GROUP_CONCAT(s.Ser_name SEPARATOR ', ') AS servicos_atribuidos
        FROM employees e
        JOIN users u ON e.User_id = u.User_id
        LEFT JOIN employee_services es ON e.Emp_id = es.Emp_id
        LEFT JOIN services s ON es.Ser_id = s.Ser_id
        WHERE u.User_perm = 'F'
        GROUP BY e.Emp_id, u.User_name, e.Emp_photo, e.Emp_specialty, e.Emp_bio, u.User_id, u.User_active
        ORDER BY u.User_name ASC
        ";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =====================================
    // OBTER OU CRIAR EMP_ID PELO USER_ID
    // =====================================
    public function getEmpIdByUserId($userId) {
        $stmtEmp = $this->pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
        $stmtEmp->execute([$userId]);
        $empId = $stmtEmp->fetchColumn();
        return $empId;
    }

    public function inserirEmpregado($userId) {
        $insertEmp = $this->pdo->prepare("INSERT INTO employees (User_id) VALUES (?)");
        $insertEmp->execute([$userId]);
        return $this->pdo->lastInsertId();
    }
    // =====================================
    // LISTAR FUNCIONÁRIOS ATIVOS (PARA O CLIENTE)
    // =====================================
    public function listarFuncionariosAtivos() {
        $sql = "SELECT e.Emp_id, u.User_name, e.Emp_photo, e.Emp_specialty, e.Emp_bio 
                FROM employees e
                JOIN users u ON e.User_id = u.User_id
                WHERE u.User_active = 1 AND u.User_perm = 'F'
                ORDER BY u.User_name ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // =====================================
    // BUSCAR NOME DO USUARIO POR EMP_ID
    // =====================================
    public function buscarNomePorEmpId($empId) {
        $sql = "SELECT u.User_name 
                FROM employees e 
                JOIN users u ON e.User_id = u.User_id 
                WHERE e.Emp_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$empId]);
        return $stmt->fetchColumn();
    }
}
?>
