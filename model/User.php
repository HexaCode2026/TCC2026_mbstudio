<?php




//Men aranha gostoso//

class User
{


    private $pdo;



    public function __construct($pdo)
    {


        $this->pdo = $pdo;


    }




    // =====================================
    // VERIFICAR EMAIL EXISTENTE
    // =====================================

    public function emailExiste($email)
    {



        $sql = "

        SELECT User_id

        FROM users

        WHERE User_email = ?

        ";



        $consulta = $this->pdo->prepare($sql);



        $consulta->execute([

            $email

        ]);



        if ($consulta->rowCount() > 0) {


            return true;


        }


        return false;



    }







    // =====================================
    // CADASTRAR USUÁRIO
    // =====================================


    public function cadastrar(

        $nome,

        $email,

        $senha

    ) {



        // Criptografar senha

        $senhaHash = password_hash(

            $senha,

            PASSWORD_DEFAULT

        );





        $sql = "

        INSERT INTO users

        (

            User_name,

            User_email,

            User_pass,

            User_perm

        )


        VALUES

        (

            ?,

            ?,

            ?,

            'C'

        )


        ";




        $insert = $this->pdo->prepare($sql);



        return $insert->execute([


            $nome,

            $email,

            $senhaHash


        ]);



    }








    // =====================================
    // LOGIN
    // =====================================


    public function login($email)
    {



        $sql = "

        SELECT *

        FROM users

        WHERE User_email = ?

        AND User_active = TRUE

        ";




        $consulta = $this->pdo->prepare($sql);




        $consulta->execute([


            $email


        ]);




        return $consulta->fetch(PDO::FETCH_ASSOC);



    }



    // =====================================
    // LISTAR TODOS OS USUÁRIOS (ADMIN)
    // =====================================
    public function listarTodos()
    {
        $sql = "SELECT User_id, User_name, User_email, User_perm, User_active FROM users ORDER BY User_name ASC";
        $consulta = $this->pdo->query($sql);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    // =====================================
    // ALTERAR PERMISSÃO DE UM USUÁRIO
    // =====================================
    public function alterarPermissao($id, $novaPermissao)
    {
        $sql = "UPDATE users SET User_perm = ? WHERE User_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$novaPermissao, $id]);
    }

    // =====================================
    // ALTERNAR STATUS (ATIVO/INATIVO)
    // =====================================
    public function alternarStatus($id, $status)
    {
        $sql = "UPDATE users SET User_active = ? WHERE User_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$status, $id]);
    }

    // =====================================
    // EXCLUIR USUÁRIO
    // =====================================
    public function excluir($id)
    {
        $sql = "DELETE FROM users WHERE User_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    // =====================================
    // BUSCAR NOME DO USUÁRIO
    // =====================================
    public function buscarNome($id)
    {
        $sql = "SELECT User_name FROM users WHERE User_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchColumn();
    }

}

?>