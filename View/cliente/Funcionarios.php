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
    <style>
        body { background-color: #111; color: #fff; font-family: 'Inter', sans-serif; }
        .team-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
        }
        .team-title {
            font-size: 36px;
            background: linear-gradient(90deg, #d4af37, #f3e5ab);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 40px;
            font-weight: bold;
        }
        .team-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            justify-content: center;
        }
        .team-card {
            background: rgba(26, 26, 26, 0.8);
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 16px;
            padding: 40px 30px;
            width: 340px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }
        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.4);
            border-color: #d4af37;
        }
        .team-photo-container {
            width: 220px;
            height: 220px;
            margin: 0 auto 25px auto;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #d4af37;
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.2);
        }
        .team-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .team-name {
            font-size: 26px;
            color: #f3e5ab;
            margin: 10px 0 5px;
            font-weight: 600;
        }
        .team-specialty {
            font-size: 16px;
            color: #d4af37;
            margin-bottom: 20px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .team-bio {
            font-size: 15px;
            color: #ddd;
            line-height: 1.6;
            font-style: italic;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 30px;
            color: #d4af37;
            text-decoration: none;
            font-size: 16px;
            border: 1px solid #d4af37;
            padding: 10px 20px;
            border-radius: 30px;
            transition: all 0.3s;
            font-weight: 600;
        }
        .back-link:hover {
            background: rgba(212, 175, 55, 0.1);
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.3);
        }
    </style>
</head>
<body>
    <?php include '../components/Header.php'; ?>
    <?php include '../components/LoginModal.php'; ?>

    <div class="team-container">
        <a href="../../Index.php" class="back-link">← Voltar para a Home</a>
        <h1 class="team-title">Conheça Nossa Equipe</h1>

        <?php if(count($funcionarios) > 0): ?>
            <div class="team-grid">
                <?php foreach($funcionarios as $f): ?>
                    <div class="team-card">
                        <div class="team-photo-container">
                            <?php if(!empty($f['Emp_photo'])): ?>
                                <img src="../../<?= htmlspecialchars($f['Emp_photo']) ?>" alt="<?= htmlspecialchars($f['User_name']) ?>" class="team-photo">
                            <?php else: ?>
                                <div style="width:100%; height:100%; background:#222; display:flex; align-items:center; justify-content:center; color:#888;">
                                    <span>Sem Foto</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h3 class="team-name"><?= htmlspecialchars($f['User_name']) ?></h3>
                        <p class="team-specialty"><?= htmlspecialchars($f['Emp_specialty'] ?: 'Profissional da Beleza') ?></p>
                        <?php if(!empty($f['Emp_bio'])): ?>
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
