<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";
require_once "../../model/User.php";

Session::iniciar();

// Proteção Admin
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'A') {
    header("Location: ../../Index.php");
    exit;
}

$userModel = new User($pdo);
$usuarios = $userModel->listarTodos();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Usuários - Admin</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
</head>
<body>
    <?php include '../components/AuthButton.php'; ?>
    <div class="admin-container">
        <a href="Dashboard.php" class="back-link">← Voltar para o Dashboard</a>
        <h1 class="admin-title">Gerenciamento de Usuários</h1>

        <?php if(count($usuarios) > 0): ?>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Status</th>
                        <th>Permissão</th>
                        <th>Ações (Excluir / Ativar)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($usuarios as $u): ?>
                        <tr>
                            <td><?= $u['User_id'] ?></td>
                            <td><?= htmlspecialchars($u['User_name']) ?></td>
                            <td><?= htmlspecialchars($u['User_email']) ?></td>
                            
                            <td>
                                <?php if($u['User_active'] == 1): ?>
                                    <span class="status-badge status-active">Ativo</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Inativo</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <form action="../../controller/AlterarPermissao.php" method="POST" class="action-form">
                                    <input type="hidden" name="User_id" value="<?= $u['User_id'] ?>">
                                    <select name="User_perm" class="select-perm">
                                        <option value="C" <?= $u['User_perm'] == 'C' ? 'selected' : '' ?>>Cliente (C)</option>
                                        <option value="F" <?= $u['User_perm'] == 'F' ? 'selected' : '' ?>>Funcionário (F)</option>
                                        <option value="A" <?= $u['User_perm'] == 'A' ? 'selected' : '' ?>>Admin (A)</option>
                                    </select>
                                    <button type="submit" class="btn-action btn-save">Salvar</button>
                                </form>
                            </td>
                            
                            <td>
                                <!-- Alternar Status -->
                                <?php if($u['User_id'] != $_SESSION['User_id']): // Não permite inativar a si mesmo ?>
                                    <form action="../../controller/AlternarStatusUsuario.php" method="POST" class="action-form">
                                        <input type="hidden" name="User_id" value="<?= $u['User_id'] ?>">
                                        <input type="hidden" name="User_active" value="<?= $u['User_active'] == 1 ? 0 : 1 ?>">
                                        <button type="submit" class="btn-action btn-toggle">
                                            <?= $u['User_active'] == 1 ? 'Desativar' : 'Ativar' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Excluir Usuário -->
                                <?php if($u['User_id'] != $_SESSION['User_id']): // Não permite excluir a si mesmo ?>
                                    <form action="../../controller/ExcluirUsuario.php" method="POST" class="action-form" onsubmit="return confirm('Tem certeza que deseja excluir fisicamente este usuário?');">
                                        <input type="hidden" name="User_id" value="<?= $u['User_id'] ?>">
                                        <button type="submit" class="btn-action btn-delete">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #aaa;">Nenhum usuário encontrado no sistema.</p>
        <?php endif; ?>
    </div>
</body>
</html>
