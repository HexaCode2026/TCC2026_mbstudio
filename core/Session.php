<?php


class Session{


    public static function iniciar(){


        if(session_status() == PHP_SESSION_NONE){

            session_start();

        }


    }



    public static function login($usuario){


        self::iniciar();


        $_SESSION['User_id'] = $usuario['User_id'];

        $_SESSION['User_name'] = $usuario['User_name'];

        $_SESSION['User_email'] = $usuario['User_email'];

        $_SESSION['User_perm'] = $usuario['User_perm'];


    }



    public static function sair(){


        self::iniciar();


        session_destroy();


    }



}


?>