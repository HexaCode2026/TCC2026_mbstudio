<?php

class User
{
    private $pdo;

    /**
     * Proteção contra Mass Assignment:
     * User_id e User_perm são atributos protegidos (guarded) e nunca podem ser
     * atribuídos em massa a partir de inputs de formulários comuns.
     */
    protected array $guarded = ['User_id', 'User_perm'];

    protected array $fillable = [
        'User_name',
        'User_email',
        'User_pass',
        'User_active',
        'User_must_change_pass'
    ];

    /**
     * Custo calibrado do Bcrypt (2^11 = 2.048 iterações, ~90ms), em conformidade estrita com
     * recomendações OWASP/NIST para autenticação web interativa, evitando saturação de CPU
     * sob picos de 50+ acessos simultâneos enquanto mantém forte resistência a ataques offline.
     */
    public const PASSWORD_COST = 11;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Gera o hash de uma senha utilizando algoritmo adaptativo moderno (Argon2id se configurado ou Bcrypt cost calibrado)
     * com salt criptográfico individual de 128 bits gerado automaticamente via CSPRNG nativo.
     */
    public static function hashSenha(string $senha): string
    {
        $algoPref = strtolower((string)(getenv('PASSWORD_ALGO') ?: ($_ENV['PASSWORD_ALGO'] ?? 'bcrypt')));
        if ($algoPref === 'argon2id' && defined('PASSWORD_ARGON2ID')) {
            return password_hash($senha, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost'   => 4,
                'threads'     => 1,
            ]);
        }

        $cost = (int)(getenv('PASSWORD_COST') ?: ($_ENV['PASSWORD_COST'] ?? self::PASSWORD_COST));
        if ($cost < 10 || $cost > 14) {
            $cost = self::PASSWORD_COST;
        }

        return password_hash($senha, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    /**
     * Verifica se um hash de senha existente precisa ser atualizado (ex: custo < calibrado ou algoritmo antigo).
     */
    public static function precisaRehash(string $hash): bool
    {
        $algoPref = strtolower((string)(getenv('PASSWORD_ALGO') ?: ($_ENV['PASSWORD_ALGO'] ?? 'bcrypt')));
        if ($algoPref === 'argon2id' && defined('PASSWORD_ARGON2ID')) {
            return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost'   => 4,
                'threads'     => 1,
            ]);
        }

        $cost = (int)(getenv('PASSWORD_COST') ?: ($_ENV['PASSWORD_COST'] ?? self::PASSWORD_COST));
        if ($cost < 10 || $cost > 14) {
            $cost = self::PASSWORD_COST;
        }

        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    /**
     * Obtém a chave secreta de aplicação para HMAC-SHA256 de tokens efêmeros.
     */
    private static function obterSegredoHmac(): string
    {
        $secret = defined('APP_SECRET') ? (string)APP_SECRET : (string)(getenv('APP_SECRET') ?: '');
        if ($secret === '' || $secret === 'mbstudio_tcc_2026_hmac_secret_key') {
            error_log('[Security] APP_SECRET ausente ou padrão em User::obterSegredoHmac().');
            $secret = hash('sha256', __DIR__ . '|' . php_uname('n') . '|mbstudio_appsec_hmac_key_v2');
        }
        return $secret;
    }

    /**
     * Gera um hash criptograficamente seguro (Salted HMAC-SHA256 com Context Binding) para códigos OTP de 6 dígitos.
     * Formato armazenado: hmac256$<salt_hex_32_chars>$<hmac_sha256_hex_64_chars> (105 caracteres).
     * O salt aleatório de 128 bits (random_bytes(16)) + vínculo de contexto neutraliza Rainbow Tables
     * mesmo no espaço de busca de 10^6 dos códigos numéricos.
     */
    public static function gerarHashCodigo(string $codigo, string $contexto = ''): string
    {
        $secret = self::obterSegredoHmac();
        $salt = bin2hex(random_bytes(16));
        $contextoNorm = strtolower(trim($contexto));
        $payload = $salt . ':' . $contextoNorm . ':' . trim($codigo);

        return 'hmac256$' . $salt . '$' . hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verifica um código OTP de 6 dígitos em tempo constante via hash_equals()
     * (compatível com Salted HMAC-SHA256, HMAC legado sem salt e hashes Bcrypt legados).
     */
    public static function verificarHashCodigo(string $codigo, string $hashArmazenado, string $contexto = ''): bool
    {
        $codigo = trim($codigo);
        if ($codigo === '' || $hashArmazenado === '') {
            return false;
        }

        if (strpos($hashArmazenado, 'hmac256$') === 0) {
            $secret = self::obterSegredoHmac();
            $partes = explode('$', $hashArmazenado);

            // Formato Salted HMAC-SHA256: hmac256$<salt>$<mac>
            if (count($partes) === 3) {
                $salt = $partes[1];
                $macArmazenado = $partes[2];
                $contextoNorm = strtolower(trim($contexto));
                $payload = $salt . ':' . $contextoNorm . ':' . $codigo;
                $macEsperado = hash_hmac('sha256', $payload, $secret);

                return hash_equals($macArmazenado, $macEsperado);
            }

            // Compatibilidade retroativa para formato anterior sem salt: hmac256$<mac>
            if (count($partes) === 2) {
                $esperadoLegado = 'hmac256$' . hash_hmac('sha256', $codigo, $secret);
                return hash_equals($hashArmazenado, $esperadoLegado);
            }

            return false;
        }

        return password_verify($codigo, $hashArmazenado);
    }

    /**
     * Filtra um array de atributos garantindo que campos protegidos (User_id, User_perm)
     * sejam removidos antes de qualquer operação de persistência.
     */
    public function filtrarAtributosPermitidos(array $dados): array
    {
        return array_intersect_key(
            array_diff_key($dados, array_flip($this->guarded)),
            array_flip($this->fillable)
        );
    }

    // =====================================
    // BUSCAR POR E-MAIL (FLUXO DE AUTENTICAÇÃO OTIMIZADO - 1 QUERY POR ÍNDICE UNIQUE)
    // =====================================
    public function findByEmail(string $email)
    {
        $sql = "
            SELECT
                User_id,
                User_name,
                User_email,
                User_pass,
                User_perm,
                User_active,
                User_must_change_pass,
                User_created_at
            FROM users
            WHERE User_email = ?
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([trim($email)]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // =====================================
    // BUSCAR POR ID (OMITINDO A SENHA)
    // =====================================
    public function findById(int $userId)
    {
        $sql = "
            SELECT
                User_id,
                User_name,
                User_email,
                User_perm,
                User_active,
                User_must_change_pass,
                User_created_at
            FROM users
            WHERE User_id = ?
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // =====================================
    // BUSCAR HASH DA SENHA ATUAL POR ID (USO INTERNO DE VALIDAÇÃO)
    // =====================================
    public function findPasswordHashById(int $userId): ?string
    {
        $sql = "SELECT User_pass FROM users WHERE User_id = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        return $hash !== false ? (string)$hash : null;
    }

    // =====================================
    // ATUALIZAR SENHA NO PRIMEIRO ACESSO
    // =====================================
    public function updateFirstPassword(int $userId, string $newPasswordHash): bool
    {
        $sql = "
            UPDATE users
            SET User_pass = ?,
                User_must_change_pass = 0
            WHERE User_id = ?
        ";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$newPasswordHash, $userId]);
    }

    // =====================================
    // ATUALIZAR PERFIL DO ADMIN (NUNCA ALTERA EMAIL OU PERMISSÃO)
    // =====================================
    public function updateProfile(int $userId, string $name, ?string $newPasswordHash = null): bool
    {
        if ($newPasswordHash !== null && $newPasswordHash !== '') {
            $sql = "
                UPDATE users
                SET User_name = ?,
                    User_pass = ?
                WHERE User_id = ?
            ";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([trim($name), $newPasswordHash, $userId]);
        }

        $sql = "
            UPDATE users
            SET User_name = ?
            WHERE User_id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([trim($name), $userId]);
    }

    // =====================================
    // VERIFICAR SE É O ÚNICO ADMIN ATIVO (REGRA DE INTEGRIDADE)
    // =====================================
    public function isLastAdmin(int $userId): bool
    {
        $user = $this->findById($userId);
        if (!$user || ($user['User_perm'] ?? '') !== 'A' || (int)($user['User_active'] ?? 0) !== 1) {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM users WHERE User_perm = 'A' AND User_active = 1";
        $stmt = $this->pdo->query($sql);
        $totalActiveAdmins = (int)$stmt->fetchColumn();

        return $totalActiveAdmins <= 1;
    }

    // =====================================
    // LIMPAR CADASTROS ANTIGOS NÃO CONFIRMADOS
    // =====================================
    public function limparCadastrosNaoConfirmados($email = null)
    {
        try {
            $sql = "
                SELECT DISTINCT u.User_id
                FROM users u
                INNER JOIN verification_codes vc
                    ON vc.User_id = u.User_id AND vc.Code_type = 'Cadastro' AND vc.Code_used = 0
                LEFT JOIN verification_codes vc2
                    ON vc2.User_id = u.User_id AND vc2.Code_type = 'Cadastro' AND vc2.Code_used = 1
                LEFT JOIN clients c
                    ON c.User_id = u.User_id
                LEFT JOIN appointments a
                    ON a.Cli_id = c.Cli_id
                WHERE u.User_active = 0
                  AND u.User_perm = 'C'
                  AND vc2.Code_id IS NULL
                  AND a.Appo_id IS NULL
                  " . ($email !== null ? "AND u.User_email = :email" : "") . "
            ";
            $stmt = $this->pdo->prepare($sql);
            if ($email !== null) {
                $stmt->execute([':email' => $email]);
            } else {
                $stmt->execute();
            }
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($ids)) {
                $inPlaceholders = implode(',', array_fill(0, count($ids), '?'));
                $delCodes = $this->pdo->prepare("DELETE FROM verification_codes WHERE User_id IN ($inPlaceholders)");
                $delCodes->execute($ids);

                $delUsers = $this->pdo->prepare("DELETE FROM users WHERE User_id IN ($inPlaceholders)");
                $delUsers->execute($ids);
            }
        } catch (Exception $e) {
            // Silencioso caso alguma tabela auxiliar não exista
        }
    }

    // =====================================
    // VERIFICAR EMAIL EXISTENTE (SO CHAMA LIMPEZA SE ENCONTRAR REGISTRO INATIVO)
    // =====================================
    public function emailExiste($email)
    {
        $email = trim((string)$email);
        $sql = "SELECT User_id, User_active FROM users WHERE User_email = ? LIMIT 1";
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute([$email]);
        $row = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false;
        }

        if ((int)$row['User_active'] === 0) {
            $this->limparCadastrosNaoConfirmados($email);
            $recheck = $this->pdo->prepare("SELECT User_id FROM users WHERE User_email = ? LIMIT 1");
            $recheck->execute([$email]);
            return (bool)$recheck->fetchColumn();
        }

        return true;
    }

    // =====================================
    // CADASTRAR USUÁRIO (APÓS CONFIRMAÇÃO DO CÓDIGO)
    // =====================================
    public function cadastrar($nome, $email, $senha)
    {
        $senhaHash = self::hashSenha((string)$senha);
        return $this->cadastrarConfirmado($nome, $email, $senhaHash);
    }

    public function cadastrarConfirmado($nome, $email, $senhaHash)
    {
        $sql = "
            INSERT INTO users (
                User_name,
                User_email,
                User_pass,
                User_perm,
                User_active,
                User_must_change_pass
            ) VALUES (?, ?, ?, 'C', 1, 0)
        ";

        $insert = $this->pdo->prepare($sql);
        $success = $insert->execute([
            $nome,
            $email,
            $senhaHash
        ]);

        if ($success) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    public function atualizarDadosInativos($id, $nome, $senha)
    {
        $senhaHash = self::hashSenha((string)$senha);
        $sql = "UPDATE users SET User_name = ?, User_pass = ? WHERE User_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$nome, $senhaHash, $id]);
    }

    // =====================================
    // ATUALIZAR SENHA
    // =====================================
    public function atualizarSenha($id, $novaSenha)
    {
        $senhaHash = self::hashSenha((string)$novaSenha);
        $sql = "UPDATE users SET User_pass = ?, User_must_change_pass = 0 WHERE User_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$senhaHash, $id]);
    }

    /**
     * Atualiza apenas o hash de senha após rehash transparente no login (sem alterar User_must_change_pass).
     */
    public function atualizarHashSenha(int $userId, string $novoHash): bool
    {
        $sql = "UPDATE users SET User_pass = ? WHERE User_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$novoHash, $userId]);
    }

    // =====================================
    // LOGIN (DELEGA PARA findByEmail)
    // =====================================
    public function login($email)
    {
        return $this->findByEmail((string)$email);
    }

    // =====================================
    // LISTAR TODOS OS USUÁRIOS (ADMIN)
    // =====================================
    public function listarTodos()
    {
        $this->limparCadastrosNaoConfirmados();

        $sql = "SELECT User_id, User_name, User_email, User_perm, User_active, User_must_change_pass FROM users ORDER BY User_name ASC";
        $consulta = $this->pdo->query($sql);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    // =====================================
    // BUSCAR USUÁRIO POR ID (COMPATIBILIDADE)
    // =====================================
    public function buscarPorId($id)
    {
        return $this->findById((int)$id);
    }

    // =====================================
    // ALTERAR PERMISSÃO DE UM USUÁRIO (EXCETO ADMIN / ÚLTIMO ADMIN)
    // =====================================
    public function alterarPermissao($id, $novaPermissao)
    {
        if ($this->isLastAdmin((int)$id)) {
            return false;
        }

        $sql = "UPDATE users SET User_perm = ? WHERE User_id = ? AND User_perm != 'A'";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$novaPermissao, $id]);
    }

    // =====================================
    // ALTERNAR STATUS (ATIVO/INATIVO)
    // =====================================
    public function alternarStatus($id, $status)
    {
        if ((int)$status === 0 && $this->isLastAdmin((int)$id)) {
            return false;
        }

        $sql = "UPDATE users SET User_active = ? WHERE User_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$status, $id]);
    }

    // =====================================
    // EXCLUIR USUÁRIO
    // =====================================
    public function excluir($id)
    {
        if ($this->isLastAdmin((int)$id)) {
            return false;
        }

        $sql = "DELETE FROM users WHERE User_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    // =====================================
    // CÓDIGOS DE VERIFICAÇÃO ATÔMICOS (2FA, Cadastro e Recuperação)
    // =====================================
    public function invalidarCodigosPendentes($userId, $tipo = 'Login2FA'): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE verification_codes
            SET Code_used = 1
            WHERE User_id = ? AND Code_type = ? AND Code_used = 0
        ");
        return $stmt->execute([(int)$userId, (string)$tipo]);
    }

    public function possuiCodigoRecente($userId, $tipo = 'Login2FA', int $segundos = 30): bool
    {
        $sql = "
            SELECT 1
            FROM verification_codes
            WHERE User_id = ?
              AND Code_type = ?
              AND Code_used = 0
              AND Code_expires_at > NOW()
              AND Code_created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$userId, (string)$tipo, $segundos]);
        return (bool)$stmt->fetchColumn();
    }

    public function gerarCodigoVerificacao($userId, $tipo = 'Login2FA')
    {
        $codigo = (string) random_int(100000, 999999);
        $contexto = (int)$userId . ':' . (string)$tipo;
        $tokenHash = self::gerarHashCodigo($codigo, $contexto);

        $iniciouTransacao = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $iniciouTransacao = true;
        }

        try {
            // Invalida códigos anteriores pendentes do mesmo tipo de forma atômica
            $this->invalidarCodigosPendentes($userId, $tipo);

            // Limpeza probabilística (10% das chamadas) para não adicionar overhead em cada login
            if (random_int(1, 10) === 1) {
                $cleanup = $this->pdo->prepare("
                    DELETE FROM verification_codes
                    WHERE User_id = ?
                      AND (Code_used = 1 OR Code_expires_at < NOW())
                      AND Code_created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
                ");
                $cleanup->execute([(int)$userId]);
            }

            $sql = "
                INSERT INTO verification_codes (User_id, Code_token, Code_type, Code_expires_at)
                VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([(int)$userId, $tokenHash, (string)$tipo]);

            if ($iniciouTransacao) {
                $this->pdo->commit();
            }

            return $codigo;
        } catch (Exception $e) {
            if ($iniciouTransacao && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function validarCodigoVerificacao($userId, $codigo, $tipo = 'Login2FA')
    {
        $iniciouTransacao = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $iniciouTransacao = true;
        }

        try {
            // Bloqueio pessimista (FOR UPDATE) impede Race Condition (TOCTOU) entre requisições paralelas
            $sql = "
                SELECT *,
                       (Code_expires_at < NOW()) AS is_expired
                FROM verification_codes
                WHERE User_id = ?
                  AND Code_type = ?
                  AND Code_used = FALSE
                ORDER BY Code_id DESC
                LIMIT 1
                FOR UPDATE
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([(int)$userId, (string)$tipo]);
            $codigoData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$codigoData) {
                if ($iniciouTransacao) {
                    $this->pdo->commit();
                }
                return ['status' => false, 'msg' => 'Nenhum código pendente.'];
            }

            if (!empty($codigoData['is_expired']) || strtotime($codigoData['Code_expires_at']) < time()) {
                $this->pdo->prepare("UPDATE verification_codes SET Code_used = 1 WHERE Code_id = ?")->execute([$codigoData['Code_id']]);
                if ($iniciouTransacao) {
                    $this->pdo->commit();
                }
                return ['status' => false, 'msg' => 'Código expirado. Solicite um novo.'];
            }

            if ((int)$codigoData['Code_attempts'] >= 5) {
                $this->pdo->prepare("UPDATE verification_codes SET Code_used = 1 WHERE Code_id = ?")->execute([$codigoData['Code_id']]);
                if ($iniciouTransacao) {
                    $this->pdo->commit();
                }
                return ['status' => false, 'msg' => 'Muitas tentativas. Solicite um novo código.'];
            }

            $contexto = (int)$userId . ':' . (string)$tipo;
            if (self::verificarHashCodigo((string)$codigo, (string)$codigoData['Code_token'], $contexto)) {
                try {
                    $this->pdo->prepare("UPDATE verification_codes SET Code_used = 1, Code_used_at = NOW() WHERE Code_id = ?")->execute([$codigoData['Code_id']]);
                } catch (PDOException $e) {
                    $this->pdo->prepare("UPDATE verification_codes SET Code_used = 1 WHERE Code_id = ?")->execute([$codigoData['Code_id']]);
                }

                if ($tipo === 'Cadastro') {
                    $this->pdo->prepare("UPDATE users SET User_active = 1 WHERE User_id = ?")->execute([(int)$userId]);
                }

                if ($iniciouTransacao) {
                    $this->pdo->commit();
                }
                return ['status' => true, 'msg' => 'Código verificado com sucesso!'];
            }

            $novasTentativas = (int)$codigoData['Code_attempts'] + 1;
            $esgotou = $novasTentativas >= 5 ? 1 : 0;

            $this->pdo->prepare("
                UPDATE verification_codes
                SET Code_attempts = ?,
                    Code_used = CASE WHEN ? = 1 THEN 1 ELSE Code_used END
                WHERE Code_id = ?
            ")->execute([$novasTentativas, $esgotou, $codigoData['Code_id']]);

            if ($iniciouTransacao) {
                $this->pdo->commit();
            }

            if ($esgotou) {
                return ['status' => false, 'msg' => 'Muitas tentativas incorretas. Solicite um novo código.'];
            }

            return ['status' => false, 'msg' => 'Código incorreto.'];
        } catch (Exception $e) {
            if ($iniciouTransacao && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            log_message('error', '[User::validarCodigoVerificacao] Erro transacional: ' . $e->getMessage());
            return ['status' => false, 'msg' => 'Erro temporário ao validar o código. Tente novamente.'];
        }
    }
}
?>
