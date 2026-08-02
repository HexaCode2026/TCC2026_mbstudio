<?php


class User{


    private $pdo;



    public function __construct($pdo){


        $this->pdo = $pdo;


    }




    // =====================================
    // VERIFICAR EMAIL EXISTENTE
    // =====================================

    public function emailExiste($email){



        $sql = "

        SELECT User_id

        FROM users

        WHERE User_email = ?

        ";



        $consulta = $this->pdo->prepare($sql);



        $consulta->execute([

            $email

        ]);



        if($consulta->rowCount() > 0){


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

    ){



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


    public function login($email){



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





}

?>