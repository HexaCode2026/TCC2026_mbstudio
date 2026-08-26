<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";
require_once "../../model/Service.php";

Session::iniciar();

// Proteção Admin
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'A') {
    header("Location: ../../Index.php");
    exit;
}

$serviceModel = new Service($pdo);
$servicos = $serviceModel->index();

// Obter todos os usuários marcados como 'F' (Funcionário), ignorando a tabela employees inicialmente
$sqlEmp = "SELECT User_id, User_name FROM users WHERE User_perm = 'F'";
$stmtEmp = $pdo->query($sqlEmp);
$funcionarios = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Gerenciar Serviços</title>
</head>

<body>
    <?php include '../components/AuthButton.php'; ?>
    <h1>Gerenciamento de Serviços</h1>
    <a href="Dashboard.php">Voltar ao Dashboard</a>
    <hr>

    <!-- FORMULÁRIO DE CADASTRO -->
    <h2>Cadastrar Novo Serviço (Anúncio)</h2>
    <form action="../../controller/SalvarServico.php" method="POST" enctype="multipart/form-data">
        <div>
            <label>Nome do Serviço:</label><br>
            <input type="text" name="nome" required>
        </div>
        <br>
        <div>
            <label>Descrição:</label><br>
            <textarea name="descricao"></textarea>
        </div>
        <br>
        <div>
            <label>Preço (R$):</label><br>
            <input type="number" step="0.01" name="preco" required>
        </div>
        <br>
        <div>
            <label>Duração (Minutos):</label><br>
            <input type="number" name="duracao" required>
        </div>
        <br>
        <div>
            <label>Imagem (Anúncio):</label><br>
            <input type="file" name="imagem" accept="image/*" required>
        </div>
        <br>
        <div>
            <label>Atribuir Funcionários:</label><br>
            <?php foreach ($funcionarios as $func): ?>
                <label>
                    <input type="checkbox" name="funcionarios[]" value="<?= $func['User_id'] ?>">
                    <?= htmlspecialchars($func['User_name']) ?>
                </label><br>
            <?php endforeach; ?>
        </div>
        <br>
        <button type="submit">Salvar Serviço</button>
    </form>

    <hr>

    <!-- LISTAGEM DE SERVIÇOS -->
    <h2>Serviços Cadastrados</h2>
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Imagem</th>
                <th>Nome</th>
                <th>Preço</th>
                <th>Duração</th>
                <th>Funcionários</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($servicos as $s): ?>
                <tr>
                    <td><?= $s['Ser_id'] ?></td>
                    <td>
                        <?php if (!empty($s['Ser_active'])): ?>
                            <!-- Aqui exibimos se há caminho de imagem, Ser_image não veio no select de ListarServicos, vou arrumar logo -->
                            [Imagem salva]
                        <?php endif; ?>
                    </td>
                    <td><?= $s['Ser_name'] ?></td>
                    <td>R$ <?= number_format($s['Ser_price'], 2, ',', '.') ?></td>
                    <td><?= $s['Ser_duration'] ?> min</td>
                    <td><?= $s['Employees'] ?: 'Nenhum' ?></td>
                    <td><?= $s['Ser_active'] ? 'Ativo' : 'Inativo' ?></td>
                    <td>
                        <!-- INTERAÇÃO COM DETAILS PARA EDIÇÃO (SEM JS) -->
                        <details>
                            <summary>Editar</summary>
                            <form action="../../controller/EditarServico.php" method="POST" enctype="multipart/form-data"
                                style="margin-top:10px; border:1px solid #ccc; padding:10px;">
                                <input type="hidden" name="id" value="<?= $s['Ser_id'] ?>">

                                <label>Nome:</label><br>
                                <input type="text" name="nome" value="<?= $s['Ser_name'] ?>" required><br>

                                <label>Descrição:</label><br>
                                <textarea name="descricao"><?= $s['Ser_description'] ?></textarea><br>

                                <label>Preço:</label><br>
                                <input type="number" step="0.01" name="preco" value="<?= $s['Ser_price'] ?>" required><br>

                                <label>Duração:</label><br>
                                <input type="number" name="duracao" value="<?= $s['Ser_duration'] ?>" required><br>

                                <label>Nova Imagem (Opcional):</label><br>
                                <input type="file" name="imagem" accept="image/*"><br>

                                <label>Ativo (1=Sim, 0=Não):</label><br>
                                <input type="number" name="ativo" value="<?= $s['Ser_active'] ?>" min="0" max="1"
                                    required><br>

                                <label>Reatribuir Funcionários:</label><br>
                                <?php foreach ($funcionarios as $func): ?>
                                    <label>
                                        <input type="checkbox" name="funcionarios[]" value="<?= $func['User_id'] ?>">
                                        <?= htmlspecialchars($func['User_name']) ?>
                                    </label><br>
                                <?php endforeach; ?>
                                <br>

                                <button type="submit">Atualizar</button>
                            </form>
                        </details>

                        <br>

                        <!-- AÇÃO DE EXCLUIR -->
                        <a href="../../controller/ExcluirServico.php?id=<?= $s['Ser_id'] ?>"
                            onclick="return confirm('Tem certeza que deseja excluir?');">
                            <button>Excluir</button>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>

</html>