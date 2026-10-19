<?php
require_once __DIR__ . "/../core/Session.php";

/**
 * FirstLoginFilter — Middleware / Filtro de Rota que intercepta usuários autenticados
 * com a flag User_must_change_pass === true e força o redirecionamento para /primeiro-acesso.
 */
class FirstLoginFilter
{
    public static function handle(): void
    {
        Session::iniciar();

        if (!empty($_SESSION['User_id']) && !empty($_SESSION['User_must_change_pass'])) {
            Session::verificarPrimeiroAcesso();
        }
    }
}

FirstLoginFilter::handle();

