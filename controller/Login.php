<?php


require_once __DIR__ . "/../config/conexao.php";

require_once __DIR__ . "/../model/User.php";

require_once __DIR__ . "/../core/Session.php";



if($_SERVER["REQUEST_METHOD"] == "POST"){


    $email = $_POST['email'];

    $senha = $_POST['senha'];



    $usuario = new User($pdo);



    $dados = $usuario->login($email);



    if($dados){


        if(password_verify(
            $senha,
            $dados['User_pass']
        )){


            Session::login($dados);



            switch($dados['User_perm']){


                case "A":

                    header(
                    "Location: ../View/admin/Home.php"
                    );

                break;



                case "F":

                    header(
                    "Location: ../View/funcionario/Home.php"
                    );

                break;



                case "C":

                    header(
                    "Location: ../Index.php"
                    );

                break;


            }


            exit;


        }


    }



    echo "

    <script>

    alert('Email ou senha incorretos');

    window.location='../Index.php';

    </script>

    ";



}


?>