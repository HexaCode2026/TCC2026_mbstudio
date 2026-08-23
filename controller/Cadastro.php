<?php


require_once __DIR__ . "/../config/conexao.php";

require_once __DIR__ . "/../model/User.php";



if($_SERVER["REQUEST_METHOD"] == "POST"){



    $nome = $_POST['nome'];

    $email = $_POST['email'];

    $senha = $_POST['senha'];




    $usuario = new User($pdo);




    // verificar email


    if($usuario->emailExiste($email)){



        echo "

        <script>

        alert('Este email já foi cadastrado!');

        window.location='../View/Cadastro.php';

        </script>

        ";


        exit;


    }




    // cadastrar


    $cadastro = $usuario->cadastrar(

        $nome,

        $email,

        $senha

    );




    if($cadastro){



        echo "

        <script>

        alert('Cadastro realizado com sucesso!');

        window.location='../Index.php';

        </script>

        ";



    }else{



        echo "

        <script>

        alert('Erro ao realizar cadastro!');

        window.location='../View/Cadastro.php';

        </script>

        ";



    }





}else{


    header(

        "Location: ../View/Cadastro.php"

    );


}


?>