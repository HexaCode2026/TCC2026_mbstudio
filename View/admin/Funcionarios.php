<?php
require_once "../../config/conexao.php";
require_once "../../core/Session.php";
require_once "../../model/Employee.php";

Session::iniciar();

// Proteção Admin
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'A') {
    header("Location: ../../Index.php");
    exit;
}

$employeeModel = new Employee($pdo);
$funcionarios = $employeeModel->listarEquipeAdmin();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Equipe - Dashboard Admin</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
</head>
<body>
    <?php include '../components/AuthButton.php'; ?>
    <div class="team-container">
        <a href="Dashboard.php" class="back-link">← Voltar para o Dashboard</a>
        <h1 class="team-title">Gestão de Equipe (Funcionários)</h1>

        <?php if(count($funcionarios) > 0): ?>
            <div class="team-grid">
                <?php foreach($funcionarios as $f): ?>
                    <div class="team-card">
                        
                        <div class="card-header">
                            <div class="team-photo-container">
                                <?php if(!empty($f['Emp_photo'])): ?>
                                    <img src="../../<?= htmlspecialchars($f['Emp_photo']) ?>" alt="Foto" class="team-photo">
                                <?php else: ?>
                                    <div style="width:100%; height:100%; background:#222; display:flex; align-items:center; justify-content:center; color:#888; font-size:10px;">
                                        Sem Foto
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 class="team-name"><?= htmlspecialchars($f['User_name']) ?></h3>
                                <p class="team-specialty"><?= htmlspecialchars($f['Emp_specialty'] ?: 'Profissional da Beleza') ?></p>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <div class="info-row">
                                <span class="info-label">Status da Conta:</span>
                                <?php if($f['User_active'] == 1): ?>
                                    <span class="badge badge-active">Ativo</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">Inativo</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">Serviços Atribuídos:</span><br>
                                <?= htmlspecialchars($f['servicos_atribuidos'] ?: 'Nenhum serviço atribuído.') ?>
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">Agenda:</span>
                                <span class="badge badge-count"><?= $f['total_horarios'] ?> horários cadastrados</span>
                            </div>
                            
                            <?php if(!empty($f['Emp_bio'])): ?>
                                <div class="info-row" style="margin-top: 15px; font-style: italic; font-size: 13px; color: #aaa;">
                                    "<?= htmlspecialchars(substr($f['Emp_bio'], 0, 80)) ?><?= strlen($f['Emp_bio']) > 80 ? '...' : '' ?>"
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-footer">
                            <a href="Servicos.php" class="btn-manage">Atribuir Serviços</a>
                            
                            <!-- Formulário para remover da equipe (alterar permissão para 'C') -->
                            <form action="../../controller/AlterarPermissao.php" method="POST" onsubmit="return confirm('Tem certeza que deseja rebaixar este funcionário para Cliente? Ele perderá acesso ao painel de equipe.');" style="flex: 1; display: flex;">
                                <input type="hidden" name="User_id" value="<?= $f['User_id'] ?>">
                                <input type="hidden" name="User_perm" value="C">
                                <button type="submit" class="btn-manage btn-danger" style="width: 100%;">Remover Equipe</button>
                            </form>
                        </div>
                        
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color:#aaa;">No momento, não temos funcionários cadastrados com a permissão 'F'.</p>
        <?php endif; ?>
    </div>
</body>
</html>
