<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

// Buscar todos os funcionários habilitados (User_perm = 'F')
$sql = "SELECT e.Emp_id, u.User_name, e.Emp_photo, e.Emp_specialty, e.Emp_bio 
        FROM employees e
        JOIN users u ON e.User_id = u.User_id
        WHERE u.User_perm = 'F'
        ORDER BY u.User_name ASC";

$stmt = $pdo->query($sql);
$funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nossa Equipe</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/cliente/funcionarios.css">
</head>

<body>
    <?php include '../components/Header.php'; ?>
    <?php include '../components/LoginModal.php'; ?>

    <div class="team-container">
        <a href="../../Index.php" class="back-link">← Voltar para a Home</a>
        <h1 class="team-title">Conheça Nossa Equipe</h1>

        <?php if (count($funcionarios) > 0): ?>
            <div class="team-grid">
                <?php foreach ($funcionarios as $f): ?>
                    <div class="team-card">
                        <div class="team-photo-container">
                            <?php if (!empty($f['Emp_photo'])): ?>
                                <img src="../../<?= htmlspecialchars($f['Emp_photo']) ?>"
                                    alt="<?= htmlspecialchars($f['User_name']) ?>" class="team-photo">
                            <?php else: ?>
                                <div
                                    style="width:100%; height:100%; background:#222; display:flex; align-items:center; justify-content:center; color:#888;">
                                    <span>Sem Foto</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h3 class="team-name"><?= htmlspecialchars($f['User_name']) ?></h3>
                        <p class="team-specialty"><?= htmlspecialchars($f['Emp_specialty'] ?: 'Profissional da Beleza') ?></p>
                        <?php if (!empty($f['Emp_bio'])): ?>
                            <p class="team-bio">"<?= htmlspecialchars($f['Emp_bio']) ?>"</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color:#aaa;">No momento, não temos profissionais cadastrados.</p>
        <?php endif; ?>
    </div>
</body>

</html>