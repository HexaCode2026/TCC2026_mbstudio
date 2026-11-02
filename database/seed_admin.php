<?php
/**
 * Script de Inicialização / Seeder Seguro (Idempotente) para Deploy
 * 1. Garante a existência da coluna User_must_change_pass na tabela users.
 * 2. Cria o usuário Administrador inicial ('kaio.gois013@gmail.com') com senha temporária
 *    'admin123' (PASSWORD_BCRYPT) e User_must_change_pass = 1 apenas se o e-mail não existir.
 */

if (php_sapi_name() !== 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('Acesso direto via navegador bloqueado.');
}

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../model/User.php';

function executarBootstrapAdmin(PDO $pdo): array
{
    $resultado = [
        'coluna_adicionada' => false,
        'admin_criado'      => false,
        'admin_atualizado'  => false,
        'hash_gerado'       => ''
    ];

    // 1. Verifica e adiciona a coluna User_must_change_pass se não existir
    $stmtCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'User_must_change_pass'");
    if ($stmtCol && $stmtCol->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN User_must_change_pass BOOLEAN DEFAULT FALSE AFTER User_active");
        $resultado['coluna_adicionada'] = true;
    }

    // 2. Gera o hash seguro Bcrypt para a senha temporária 'admin123'
    $emailAdmin = 'kaio.gois013@gmail.com';
    $nomeAdmin  = 'Administrador';
    $hashSenha  = User::hashSenha('admin123');
    $resultado['hash_gerado'] = $hashSenha;

    // 3. Seeder Idempotente: só insere se o e-mail não existir na tabela users
    $sqlSeed = "
        INSERT INTO users (
            User_name,
            User_email,
            User_pass,
            User_perm,
            User_active,
            User_must_change_pass
        )
        SELECT :nome, :email, :pass, 'A', 1, 1
        FROM DUAL
        WHERE NOT EXISTS (
            SELECT 1 FROM users WHERE User_email = :email_check
        )
    ";

    $stmtSeed = $pdo->prepare($sqlSeed);
    $stmtSeed->execute([
        ':nome'        => $nomeAdmin,
        ':email'       => $emailAdmin,
        ':pass'        => $hashSenha,
        ':email_check' => $emailAdmin
    ]);

    if ($stmtSeed->rowCount() > 0) {
        $resultado['admin_criado'] = true;
    } elseif (in_array('--force-admin', $_SERVER['argv'] ?? [], true)) {
        $stmtUpd = $pdo->prepare("
            UPDATE users
            SET User_name = :nome,
                User_pass = :pass,
                User_perm = 'A',
                User_active = 1,
                User_must_change_pass = 1
            WHERE User_email = :email
        ");
        $stmtUpd->execute([
            ':nome'  => $nomeAdmin,
            ':pass'  => $hashSenha,
            ':email' => $emailAdmin
        ]);
        $resultado['admin_atualizado'] = ($stmtUpd->rowCount() > 0);
    }

    return $resultado;
}

if (php_sapi_name() === 'cli' && isset($pdo)) {
    $res = executarBootstrapAdmin($pdo);
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
