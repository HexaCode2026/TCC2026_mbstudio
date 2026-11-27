<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

// Buscar todos os funcionários habilitados (User_perm = 'F')
$sql = "SELECT e.Emp_id, u.User_name, e.Emp_photo, e.Emp_specialty, e.Emp_bio 
        FROM employees e
        JOIN users u ON e.User_id = u.User_id
        WHERE u.User_perm = 'F' AND u.User_active = 1
        ORDER BY u.User_name ASC";

$stmt = $pdo->query($sql);
$funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nossa Equipe | MB Studio</title>
    <link rel="stylesheet" href="../../assets/css/global.css?v=<?= @filemtime(__DIR__ . '/../../assets/css/global.css') ?: '2.4' ?>">
    <link rel="stylesheet" href="../../assets/css/cliente/funcionarios.css?v=<?= @filemtime(__DIR__ . '/../../assets/css/cliente/funcionarios.css') ?: '2.4' ?>">
</head>

<body>
    <?php include '../components/Header.php'; ?>

    <div class="page-content-wrapper" id="page-content">
        <main class="team-page">
            <div class="team-container">
                <div class="team-top-bar">
                    <a href="../../Index.php" class="back-link">← Voltar para a Home</a>
                </div>

                <header class="team-header">
                    <span class="team-subtitle-tag">TALENTO &amp; DEDICAÇÃO</span>
                    <h1 class="team-title">Conheça Nossa <span class="cursiva-gold">Equipe</span></h1>
                    <div class="team-divider"></div>
                    <p class="team-lead">
                        Profissionais especializados em visagismo, cortes e tratamentos capilares de alto padrão para valorizar a sua beleza única.
                    </p>
                </header>

                <?php if (count($funcionarios) > 0): ?>
                    <div class="team-grid">
                        <?php foreach ($funcionarios as $f): ?>
                            <article class="team-card">
                                <div class="team-photo-container">
                                    <?php if (!empty($f['Emp_photo'])): ?>
                                        <img src="../../<?= htmlspecialchars($f['Emp_photo']) ?>"
                                            alt="<?= htmlspecialchars($f['User_name']) ?>" class="team-photo">
                                    <?php else: ?>
                                        <div class="team-photo-placeholder" aria-hidden="true">
                                            <?= htmlspecialchars(mb_strtoupper(mb_substr(trim($f['User_name']), 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <h2 class="team-name"><?= htmlspecialchars($f['User_name']) ?></h2>
                                <p class="team-specialty"><?= htmlspecialchars($f['Emp_specialty'] ?: 'Profissional da Beleza') ?></p>
                                <?php if (!empty($f['Emp_bio'])): ?>
                                    <p class="team-bio">"<?= htmlspecialchars($f['Emp_bio']) ?>"</p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-card">
                        <div class="empty-state-icon" aria-hidden="true">✦</div>
                        <h2 class="empty-state-title">Equipe em atualização</h2>
                        <p class="empty-state-desc">
                            No momento, não temos profissionais disponíveis para exibição. Enquanto isso, explore nosso catálogo de serviços e rituais exclusivos.
                        </p>
                        <a href="Servicos.php" class="btn-gold">Conhecer Nossos Serviços</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <?php include '../components/Footer.php'; ?>
    </div>

    <?php include '../components/LoginModal.php'; ?>
</body>

</html>