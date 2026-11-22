<?php

class Session {

    public static function getBasePath(): string {
        $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
        $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/../'));
        return $docRoot ? str_ireplace($docRoot, '', $projectRoot) : '';
    }

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

        // Interceptação Global de Primeiro Acesso (bloqueia Dashboard, Relatórios e demais rotas)
        self::verificarPrimeiroAcesso();
    }

    /**
     * Libera imediatamente o lock exclusivo do arquivo de sessão (session_write_close)
     * antes de operações lentas de I/O externo (ex: envio SMTP), evitando bloquear
     * requisições concorrentes do mesmo navegador/usuário.
     */
    public static function liberarLock(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    public static function login($usuario) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Regenera o ID da sessão após autenticação para mitigar Session Fixation
        session_regenerate_id(true);

        $_SESSION['User_id']               = (int)($usuario['User_id'] ?? 0);
        $_SESSION['User_name']             = (string)($usuario['User_name'] ?? '');
        $_SESSION['User_email']            = (string)($usuario['User_email'] ?? '');
        $_SESSION['User_perm']             = (string)($usuario['User_perm'] ?? 'C');
        $_SESSION['User_must_change_pass'] = !empty($usuario['User_must_change_pass']);
        $_SESSION['LAST_ACTIVITY']         = time();
        $_SESSION['csrf_token']            = bin2hex(random_bytes(32));
    }

    /**
     * Filtro Global de Primeiro Acesso:
     * Se User_must_change_pass === true, intercepta qualquer requisição autenticada
     * e redireciona obrigatoriamente para /primeiro-acesso (exceto a própria rota e logout).
     */
    public static function verificarPrimeiroAcesso(): void {
        if (php_sapi_name() === 'cli') {
            return;
        }

        if (empty($_SESSION['User_id']) || empty($_SESSION['User_must_change_pass'])) {
            return;
        }

        $scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        $rotasPermitidasScripts = [
            'PrimeiroAcesso.php',
            'PrimeiroAcessoController.php',
            'Logout.php'
        ];

        if (
            in_array($scriptName, $rotasPermitidasScripts, true) ||
            stripos($requestUri, 'PrimeiroAcesso') !== false ||
            stripos($requestUri, 'primeiro-acesso') !== false
        ) {
            return;
        }

        $basePath = self::getBasePath();
        header("Location: " . $basePath . "/View/PrimeiroAcesso.php");
        exit;
    }

    public static function setFlash(string $key, string $message): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['_flash'][$key] = $message;
    }

    public static function getFlash(string $key): ?string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['_flash'][$key])) {
            $msg = (string)$_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $msg;
        }
        return null;
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string {
        $token = htmlspecialchars(self::gerarTokenCsrf(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Defesa em Profundidade contra CSRF:
     * 1. Valida Token explícito se enviado via POST ou Header X-CSRF-Token.
     * 2. Valida estritamente cabeçalhos Origin e Referer (Same-Origin Policy) em requisições de mutação.
     */
    public static function validarCsrf($token = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

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
