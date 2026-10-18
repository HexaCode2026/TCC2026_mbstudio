<?php

class User
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // =====================================
    // LIMPAR CADASTROS ANTIGOS NÃO CONFIRMADOS
    // =====================================
    public function limparCadastrosNaoConfirmados($email = null)
    {
        try {
            // Remove usuários User_active = 0 que ficaram presos de cadastros não confirmados anteriormente
            // (possuem código de tipo 'Cadastro' não utilizado e nunca tiveram código 'Cadastro' validado)
            $sql = "
                SELECT u.User_id
                FROM users u
                WHERE u.User_active = 0
                  AND u.User_perm = 'C'
                  " . ($email !== null ? "AND u.User_email = :email" : "") . "
                  AND EXISTS (
                      SELECT 1 FROM verification_codes vc
                      WHERE vc.User_id = u.User_id AND vc.Code_type = 'Cadastro' AND vc.Code_used = 0
                  )
                  AND NOT EXISTS (
                      SELECT 1 FROM verification_codes vc2
                      WHERE vc2.User_id = u.User_id AND vc2.Code_type = 'Cadastro' AND vc2.Code_used = 1
                  )
                  AND NOT EXISTS (
                      SELECT 1 FROM appointments a
                      WHERE a.Cli_id = u.User_id
                  )
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
    // VERIFICAR EMAIL EXISTENTE
    // =====================================
    public function emailExiste($email)
    {
        $this->limparCadastrosNaoConfirmados($email);

        $sql = "
        SELECT User_id
        FROM users
        WHERE User_email = ?
        ";

        $consulta = $this->pdo->prepare($sql);
        $consulta->execute([$email]);

        return $consulta->rowCount() > 0;
    }

    // =====================================
    // CADASTRAR USUÁRIO (APÓS CONFIRMAÇÃO DO CÓDIGO)
    // =====================================
    public function cadastrar($nome, $email, $senha)
    {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        return $this->cadastrarConfirmado($nome, $email, $senhaHash);
    }

    public function cadastrarConfirmado($nome, $email, $senhaHash)
    {
        $sql = "
        INSERT INTO users
        (
            User_name,
            User_email,
            User_pass,
            User_perm,
            User_active
        )
        VALUES
        (
            ?,
            ?,
            ?,
            'C',
            1
        )
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
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET User_name = ?, User_pass = ? WHERE User_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$nome, $senhaHash, $id]);
    }

    // =====================================
    // ATUALIZAR SENHA
    // =====================================
    public function atualizarSenha($id, $novaSenha)
    {
        $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET User_pass = ? WHERE User_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$senhaHash, $id]);
    }

    // =====================================
    // LOGIN
    // =====================================
    public function login($email)
    {
        $this->limparCadastrosNaoConfirmados($email);

        $sql = "
        SELECT *
        FROM users
        WHERE User_email = ?
        ";

        $consulta = $this->pdo->prepare($sql);
        $consulta->execute([$email]);

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    // =====================================
    // LISTAR TODOS OS USUÁRIOS (ADMIN)
    // =====================================
    public function listarTodos()
    {
        $this->limparCadastrosNaoConfirmados();

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
    // CÓDIGOS DE VERIFICAÇÃO (2FA e Recuperação)
    // =====================================
    public function gerarCodigoVerificacao($userId, $tipo = 'Login2FA')
    {
        // Invalida códigos anteriores não usados do mesmo tipo para evitar sobrecarga
        $invalidate = $this->pdo->prepare("UPDATE verification_codes SET Code_used = 1 WHERE User_id = ? AND Code_type = ? AND Code_used = 0");
        $invalidate->execute([$userId, $tipo]);

        $codigo = (string) random_int(100000, 999999);
        $expiraEm = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $tokenHash = password_hash($codigo, PASSWORD_DEFAULT);

        $sql = "INSERT INTO verification_codes (User_id, Code_token, Code_type, Code_expires_at) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $tokenHash, $tipo, $expiraEm]);

        return $codigo;
    }

    public function validarCodigoVerificacao($userId, $codigo, $tipo = 'Login2FA')
    {
        $sql = "SELECT * FROM verification_codes WHERE User_id = ? AND Code_type = ? AND Code_used = FALSE ORDER BY Code_id DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $tipo]);
        $codigoData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$codigoData) return ['status' => false, 'msg' => 'Nenhum código pendente.'];
        if (strtotime($codigoData['Code_expires_at']) < time()) return ['status' => false, 'msg' => 'Código expirado. Solicite um novo.'];
        if ($codigoData['Code_attempts'] >= 5) return ['status' => false, 'msg' => 'Muitas tentativas. Solicite um novo código.'];

        if (password_verify($codigo, $codigoData['Code_token'])) {
            $this->pdo->prepare("UPDATE verification_codes SET Code_used = 1 WHERE Code_id = ?")->execute([$codigoData['Code_id']]);
            if ($tipo == 'Cadastro') {
                $this->pdo->prepare("UPDATE users SET User_active = 1 WHERE User_id = ?")->execute([$userId]);
            }
            return ['status' => true, 'msg' => 'Código verificado com sucesso!'];
        } else {
            $this->pdo->prepare("UPDATE verification_codes SET Code_attempts = Code_attempts + 1 WHERE Code_id = ?")->execute([$codigoData['Code_id']]);
            return ['status' => false, 'msg' => 'Código incorreto.'];
        }
    }
}
?>