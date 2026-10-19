<?php

class Session {

    public static function iniciar() {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

            @ini_set('session.cookie_httponly', '1');
            @ini_set('session.use_only_cookies', '1');
            @ini_set('session.use_strict_mode', '1');
            @ini_set('session.cookie_samesite', 'Lax');
            if ($isHttps) {
                @ini_set('session.cookie_secure', '1');
            }

            session_start();

            // Expiração por inatividade (2 horas = 7200s)
            if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 7200)) {
                self::sair();
                session_start();
            }
            $_SESSION['LAST_ACTIVITY'] = time();

            // Garante a existência de um token CSRF na sessão
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
        }
    }

    public static function login($usuario) {
        self::iniciar();

        // Regenera o ID da sessão após autenticação para mitigar Session Fixation
        session_regenerate_id(true);

        $_SESSION['User_id']    = $usuario['User_id'];
        $_SESSION['User_name']  = $usuario['User_name'];
        $_SESSION['User_email'] = $usuario['User_email'];
        $_SESSION['User_perm']  = $usuario['User_perm'];
        $_SESSION['LAST_ACTIVITY'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    public static function sair() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }

    public static function gerarTokenCsrf() {
        self::iniciar();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Defesa em Profundidade contra CSRF:
     * 1. Valida Token explícito se enviado via POST ou Header X-CSRF-Token.
     * 2. Valida estritamente cabeçalhos Origin e Referer (Same-Origin Policy) em requisições de mutação.
     */
    public static function validarCsrf($token = null) {
        self::iniciar();

        $tokenRecebido = $token ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if ($tokenRecebido !== null && $tokenRecebido !== '') {
            if (!hash_equals($_SESSION['csrf_token'] ?? '', (string)$tokenRecebido)) {
                http_response_code(403);
                die("Ação bloqueada por segurança: Token CSRF inválido.");
            }
            return true;
        }

        // Verificação estrita Same-Origin via Origin / Referer
        $hostAtual = strtolower($_SERVER['HTTP_HOST'] ?? '');
        if ($hostAtual !== '') {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if ($origin !== '') {
                $originHost = strtolower((string)parse_url($origin, PHP_URL_HOST));
                $originPort = parse_url($origin, PHP_URL_PORT);
                $originFull = $originPort ? ($originHost . ':' . $originPort) : $originHost;
                if ($originHost !== $hostAtual && $originFull !== $hostAtual) {
                    http_response_code(403);
                    die("Ação bloqueada por segurança: Origem externa não autorizada (CSRF Protection).");
                }
            }

            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            if ($referer !== '') {
                $refHost = strtolower((string)parse_url($referer, PHP_URL_HOST));
                $refPort = parse_url($referer, PHP_URL_PORT);
                $refFull = $refPort ? ($refHost . ':' . $refPort) : $refHost;
                if ($refHost !== $hostAtual && $refFull !== $hostAtual) {
                    http_response_code(403);
                    die("Ação bloqueada por segurança: Referer externo não autorizado (CSRF Protection).");
                }
            }
        }

        return true;
    }
}
?>