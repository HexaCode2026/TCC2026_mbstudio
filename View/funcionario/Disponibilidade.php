<?php
date_default_timezone_set('Etc/GMT+3');
require_once "../../config/conexao.php";
require_once "../../core/Session.php";

Session::iniciar();

// Proteção para Funcionário
if (!isset($_SESSION['User_perm']) || $_SESSION['User_perm'] != 'F') {
    header("Location: ../../Index.php");
    exit;
}

// Buscar ou garantir o Emp_id do funcionário logado
$userId = $_SESSION['User_id'];
$stmtEmp = $pdo->prepare("SELECT Emp_id FROM employees WHERE User_id = ?");
$stmtEmp->execute([$userId]);
$empId = $stmtEmp->fetchColumn();

if (!$empId) {
    $insertEmp = $pdo->prepare("INSERT INTO employees (User_id) VALUES (?)");
    $insertEmp->execute([$userId]);
    $empId = $pdo->lastInsertId();
}

// Buscar serviços vinculados ao funcionário através da tabela employee_services
// Seleciona EmpSer_id, a chave estrangeira Ser_id e dados do serviço para o Emp_id do funcionário logado
$sqlServicos = "SELECT es.EmpSer_id, es.Emp_id, es.Ser_id, s.Ser_name, s.Ser_duration 
                FROM employee_services es
                INNER JOIN services s ON es.Ser_id = s.Ser_id
                WHERE es.Emp_id = ? AND (s.Ser_active = 1 OR s.Ser_active IS NULL)
                ORDER BY s.Ser_name ASC";
$stmtServicos = $pdo->prepare($sqlServicos);
$stmtServicos->execute([$empId]);
$servicos = $stmtServicos->fetchAll(PDO::FETCH_ASSOC);

// Buscar todas as disponibilidades já cadastradas para o funcionário (incluindo datas passadas)
require_once "../../model/Availability.php";
$availabilityModel = new Availability($pdo);
$minhasDisponibilidades = $availabilityModel->listarPorFuncionario($empId);

// Obter datas únicas das disponibilidades para o filtro
$datasCadastradas = [];
if (!empty($minhasDisponibilidades)) {
    foreach ($minhasDisponibilidades as $disp) {
        $data = $disp['Ava_date'];
        if (!in_array($data, $datasCadastradas)) {
            $datasCadastradas[] = $data;
        }
    }
    usort($datasCadastradas, function ($a, $b) {
        return strtotime($a) - strtotime($b);
    });
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disponibilidade | MB Studio</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --gold-primary: #d4af37;
            --gold-light: #f3e5ab;
            --gold-dark: #8a6508;
            --gold-darker: #6e5005;
            --gold-border: rgba(212, 175, 55, 0.35);
            --bg-dark: #0f0f11;
            --bg-card: #161619;
            --bg-card-hover: #1e1e24;
            --text-light: #ffffff;
            --text-muted: #a0a0a8;
            --font-heading: 'Playfair Display', Georgia, serif;
            --font-cursive: 'Alex Brush', 'Brush Script MT', cursive;
            --font-body: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --transition-smooth: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
            --shadow-card: 0 15px 35px rgba(0, 0, 0, 0.5);
        }

        body {
            font-family: var(--font-body);
            background-color: var(--bg-dark);
            color: var(--text-light);
            min-height: 100vh;
            padding-top: 70px;
            overflow-x: hidden;
            margin: 0;
        }

        .cursiva-gold {
            font-family: var(--font-cursive);
            color: var(--gold-primary);
            font-style: italic;
            font-size: 1.25em;
            line-height: 1;
            display: inline-block;
            padding: 0 4px;
        }

        .pagina-disponibilidade {
            width: 92%;
            max-width: 1300px;
            margin: 0 auto;
            padding: 40px 0 80px;
            box-sizing: border-box;
        }

        /* Top Navigation */
        .disp-top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn-back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--gold-light);
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--gold-border);
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition-smooth);
        }

        .btn-back-link:hover {
            background: rgba(212, 175, 55, 0.15);
            border-color: var(--gold-primary);
            color: #ffffff;
            transform: translateX(-3px);
        }

        /* Header da Página */
        .disp-header-box {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .disp-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 16px;
            background: rgba(212, 175, 55, 0.1);
            border: 1px solid var(--gold-border);
            border-radius: 30px;
            color: var(--gold-light);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .tituloPagina {
            font-family: var(--font-heading);
            font-size: clamp(28px, 4vw, 42px);
            font-weight: 600;
            color: #ffffff;
            margin: 0 0 10px 0;
            line-height: 1.2;
        }

        .tituloPagina span {
            color: var(--gold-primary);
        }

        .subtitulo {
            font-size: 15px;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto 35px;
            line-height: 1.6;
        }

        /* Form Card */
        .cadastro-grid-card {
            background: linear-gradient(145deg, #18181c, #121215);
            border: 1px solid var(--gold-border);
            border-radius: 18px;
            padding: 35px;
            box-shadow: var(--shadow-card);
            margin-bottom: 45px;
            box-sizing: border-box;
        }

        .TabelaCadastro {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 25px;
        }

        .TabelaCadastro tr {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        .TabelaCadastro td.cards {
            flex: 1.1;
            background: transparent;
            border: none;
            padding: 0;
            height: auto;
        }

        .TabelaCadastro td.coluna-direita {
            flex: 0.9;
            background: transparent;
            border: none;
            padding: 0;
            max-width: none;
            width: auto;
        }

        /* Calendário Customizado */
        .campo-calendario {
            width: 100%;
        }

        #calendario-customizado {
            width: 100%;
            background: #1e1e23;
            border: 1px solid var(--gold-border);
            border-radius: 14px;
            padding: 22px;
            box-sizing: border-box;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }

        .calendario-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
            padding-bottom: 12px;
        }

        .calendario-mes {
            font-family: var(--font-heading);
            font-size: 18px;
            font-weight: 600;
            color: var(--gold-light);
            letter-spacing: 0.5px;
        }

        .calendario-btn {
            width: 36px;
            height: 36px;
            border: 1px solid var(--gold-border);
            border-radius: 8px;
            background: rgba(212, 175, 55, 0.1);
            color: var(--gold-light);
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition-smooth);
        }

        .calendario-btn:hover {
            background: var(--gold-primary);
            color: #000000;
            transform: scale(1.08);
        }

        .calendario-semana,
        .calendario-dias {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            text-align: center;
        }

        .calendario-semana {
            margin-bottom: 12px;
        }

        .calendario-semana span {
            font-size: 12px;
            font-weight: 700;
            color: var(--gold-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .calendario-dias button {
            width: 100%;
            height: 38px;
            border: 1px solid transparent;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.03);
            color: #e0e0e0;
            font-size: 13.5px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition-smooth);
        }

        .calendario-dias button:hover:not(:disabled) {
            background: rgba(212, 175, 55, 0.2);
            border-color: var(--gold-primary);
            color: var(--gold-light);
            transform: translateY(-2px);
        }

        .calendario-dias button:disabled {
            color: #55555e;
            background: transparent;
            cursor: not-allowed;
            opacity: 0.4;
        }

        .calendario-dias button.dia-selecionado {
            background: linear-gradient(135deg, #d4af37, #b8860b) !important;
            color: #000000 !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
        }

        /* Coluna Direita */
        .area-servico {
            width: 100%;
            margin: 0 0 25px 0;
            text-align: left;
        }

        .titulo-servicos,
        .titulo-horarios {
            font-family: var(--font-heading);
            font-size: 18px;
            font-weight: 600;
            color: var(--gold-light);
            margin: 0 0 12px 0;
            letter-spacing: 0.5px;
        }

        .area-servico select,
        #Dis_ser {
            width: 100%;
            height: 48px;
            padding: 0 40px 0 14px;
            border: 1px solid var(--gold-border);
            border-radius: 10px;
            background-color: #1e1e23;
            color: #ffffff;
            font-size: 14px;
            font-family: var(--font-body);
            cursor: pointer;
            outline: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23d4af37' d='M2 4l4 4 4-4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            transition: var(--transition-smooth);
        }

        #Dis_ser:focus {
            border-color: var(--gold-primary);
            box-shadow: 0 0 12px rgba(212, 175, 55, 0.3);
        }

        #Dis_ser option {
            background: #18181c;
            color: #ffffff;
        }

        .area-horarios {
            display: flex;
            gap: 20px;
            margin: 0 0 25px 0;
        }

        .campo-horario {
            flex: 1;
        }

        .campo-horario label {
            display: block;
            margin-bottom: 8px;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--gold-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .campo-horario input {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1px solid var(--gold-border);
            border-radius: 10px;
            background: #1e1e23;
            color: #ffffff;
            font-size: 14px;
            font-family: var(--font-body);
            outline: none;
            transition: var(--transition-smooth);
            box-sizing: border-box;
        }

        .campo-horario input:focus {
            border-color: var(--gold-primary);
            box-shadow: 0 0 12px rgba(212, 175, 55, 0.3);
        }

        /* Horários Gerados */
        .secao-horarios-gerados {
            margin-top: 30px;
            border-top: 1px solid rgba(212, 175, 55, 0.2);
            padding-top: 25px;
        }

        .label-horarios-titulo {
            display: block;
            font-family: var(--font-heading);
            font-size: 20px;
            color: var(--gold-light);
            margin-bottom: 15px;
        }

        #Dis_scheduled-times {
            background: #19191d;
            border: 1px solid var(--gold-border);
            border-radius: 12px;
            padding: 20px;
            box-sizing: border-box;
        }

        #lista-horarios-vazia {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.6;
            text-align: center;
            padding: 15px 0;
        }

        #lista-horarios {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
        }

        #lista-horarios li {
            background: #202026;
            border: 1px solid rgba(212, 175, 55, 0.25);
            border-radius: 8px;
            padding: 12px 16px !important;
            margin: 0 !important;
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            border-bottom: none !important;
            transition: var(--transition-smooth);
        }

        #lista-horarios li:hover {
            border-color: var(--gold-primary);
            background: #25252d;
        }

        #lista-horarios li strong {
            color: var(--gold-light);
            font-size: 14px;
        }

        .status-select {
            background: #141417;
            color: #ffffff;
            border: 1px solid var(--gold-border);
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12.5px;
            outline: none;
            cursor: pointer;
        }

        /* Botão de Salvar */
        #btnSalvar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, #d4af37 0%, #a67c00 50%, #8a6508 100%);
            color: #0b0b0c;
            font-family: var(--font-body);
            font-weight: 800;
            font-size: 14px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 16px 36px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.3);
            transition: var(--transition-smooth);
            cursor: pointer;
            margin-top: 25px;
        }

        #btnSalvar:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(212, 175, 55, 0.5);
            background: linear-gradient(135deg, #f3e5ab 0%, #d4af37 60%, #a67c00 100%);
            color: #000;
        }

        #btnSalvar:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            box-shadow: none;
            filter: grayscale(0.5);
        }

        /* Tabela e Filtros */
        .secao-cadastradas {
            margin-top: 50px;
        }

        .secao-cadastradas h2 {
            font-family: var(--font-heading);
            font-size: 26px;
            color: #ffffff;
            margin-bottom: 20px;
        }

        .filtro-cadastradas-bar {
            background: linear-gradient(145deg, #18181c, #121214);
            border: 1px solid var(--gold-border);
            border-radius: 12px;
            padding: 18px 24px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filtro-cadastradas-bar label {
            font-size: 13px;
            font-weight: 600;
            color: var(--gold-light);
            text-transform: uppercase;
        }

        .filtro-cadastradas-bar select {
            background: #202026;
            color: #ffffff;
            border: 1px solid var(--gold-border);
            border-radius: 8px;
            height: 40px;
            padding: 0 14px;
            font-size: 13.5px;
            outline: none;
            cursor: pointer;
        }

        #acoes-lote-data {
            background: rgba(212, 175, 55, 0.08);
            border: 1px solid var(--gold-border);
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 25px;
        }

        #acoes-lote-data label {
            font-size: 13px;
            color: var(--gold-light);
        }

        #acoes-lote-data button {
            background: #202026;
            color: var(--gold-light);
            border: 1px solid var(--gold-border);
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            margin-right: 8px;
            margin-top: 6px;
            transition: var(--transition-smooth);
        }

        #acoes-lote-data button:hover {
            background: var(--gold-primary);
            color: #000000;
            transform: translateY(-2px);
        }

        .table-disp-wrapper {
            background: linear-gradient(145deg, #18181c, #121215);
            border: 1px solid var(--gold-border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: var(--shadow-card);
        }

        #tabelaDisponibilidades {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13.5px;
        }

        #tabelaDisponibilidades thead {
            background: rgba(212, 175, 55, 0.08);
            border-bottom: 2px solid var(--gold-border);
        }

        #tabelaDisponibilidades th {
            padding: 16px 14px;
            color: var(--gold-light);
            font-family: var(--font-heading);
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        #tabelaDisponibilidades tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            transition: var(--transition-smooth);
        }

        #tabelaDisponibilidades tbody tr:hover {
            background-color: rgba(212, 175, 55, 0.04);
        }

        #tabelaDisponibilidades td {
            padding: 16px 14px;
            color: #e0e0e0;
            vertical-align: middle;
        }

        #tabelaDisponibilidades select {
            background: #202026;
            color: #ffffff;
            border: 1px solid var(--gold-border);
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12.5px;
            outline: none;
            cursor: pointer;
        }

        #tabelaDisponibilidades button[type="submit"] {
            background: rgba(212, 175, 55, 0.15);
            color: var(--gold-light);
            border: 1px solid var(--gold-border);
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            margin-left: 6px;
            transition: var(--transition-smooth);
        }

        #tabelaDisponibilidades button[type="submit"]:hover {
            background: var(--gold-primary);
            color: #000000;
        }

        #tabelaDisponibilidades form[action*="Excluir"] button {
            background: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
            border-color: rgba(231, 76, 60, 0.4);
        }

        #tabelaDisponibilidades form[action*="Excluir"] button:hover {
            background: #e74c3c;
            color: #ffffff;
        }

        /* Responsividade */
        @media (max-width: 950px) {
            .TabelaCadastro tr {
                flex-direction: column;
                gap: 25px;
            }

            .TabelaCadastro td.cards,
            .TabelaCadastro td.coluna-direita {
                width: 100%;
                flex: none;
            }

            .table-disp-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            #tabelaDisponibilidades {
                min-width: 750px;
            }
        }

        @media (max-width: 600px) {
            .pagina-disponibilidade {
                width: 95%;
                padding: 25px 0 60px;
            }

            .cadastro-grid-card {
                padding: 22px 16px;
            }

            .area-horarios {
                flex-direction: column;
                gap: 15px;
            }

            .btn-back-link {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <!-- Cabeçalho Global MB Studio -->
    <?php include '../components/Header.php'; ?>
    <?php include '../components/LoginModal.php'; ?>

    <main class="pagina-disponibilidade">

        <!-- Top Navigation -->
        <div class="disp-top-nav">
            <a href="Home.php" class="btn-back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Voltar ao Início
            </a>

            <a href="Agenda.php" class="btn-back-link"
                style="border-color: var(--gold-primary); color: var(--gold-light);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Ver Minha Agenda
            </a>
        </div>

        <!-- Header da Página -->
        <div class="disp-header-box">
            <div class="disp-badge">
                <span>✦ Gestão de Atendimento ✦</span>
            </div>
            <h1 class="tituloPagina">Meus <span class="cursiva-gold">Horários</span></h1>
            <p class="subtitulo">Cadastre e gerencie os dias e turnos em que você estará disponível para atender no MB
                Studio.</p>
        </div>

        <!-- CARD PRINCIPAL DO FORMULÁRIO -->
        <div class="cadastro-grid-card">
            <form action="../../controller/SalvarDisponibilidade.php" method="POST" id="formDisponibilidade">

                <input type="hidden" name="Fun_id" value="<?= htmlspecialchars($_SESSION['User_id']) ?>">
                <input type="hidden" name="Emp_id" value="<?= htmlspecialchars($empId) ?>">

                <table class="TabelaCadastro">
                    <tbody>
                        <tr>
                            <!-- CALENDÁRIO INTERATIVO -->
                            <td class="cards">
                                <div class="campo-calendario">
                                    <div id="calendario-customizado"></div>
                                    <input type="hidden" name="Dis_date" id="Dis_date" min="<?= date('Y-m-d') ?>"
                                        required>
                                </div>
                            </td>

                            <!-- ÁREA DIREITA (SERVIÇO & HORÁRIOS) -->
                            <td class="coluna-direita">
                                <!-- SERVIÇO -->
                                <div class="area-servico">
                                    <h2 class="titulo-servicos">Serviço a Disponibilizar</h2>
                                    <select name="Dis_ser" id="Dis_ser" required>
                                        <option value="">Selecione um serviço</option>
                                        <?php if (!empty($servicos)): ?>
                                            <?php foreach ($servicos as $ser): ?>
                                                <option value="<?= htmlspecialchars($ser['Ser_id']) ?>"
                                                    data-duration="<?= htmlspecialchars($ser['Ser_duration']) ?>"
                                                    data-empser="<?= htmlspecialchars($ser['EmpSer_id']) ?>">
                                                    <?= htmlspecialchars($ser['Ser_name']) ?>
                                                    (<?= htmlspecialchars($ser['Ser_duration']) ?> min)
                                                </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="" disabled>Nenhum serviço vinculado ao perfil</option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <!-- HORÁRIOS INÍCIO E FIM -->
                                <h2 class="titulo-horarios">Janela de Atendimento</h2>
                                <div class="area-horarios">
                                    <div class="campo-horario">
                                        <label for="Dis_start">Horário Início</label>
                                        <input type="time" name="Dis_start" id="Dis_start" min="08:00" max="19:00"
                                            required>
                                    </div>

                                    <div class="campo-horario">
                                        <label for="Dis_end">Horário Término</label>
                                        <input type="time" name="Dis_end" id="Dis_end" min="08:00" max="19:00" required>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- CONTAINER DINÂMICO DOS HORÁRIOS DE ATENDIMENTO GERADOS -->
                <div class="secao-horarios-gerados">
                    <label for="Dis_scheduled-times" class="label-horarios-titulo">
                        <strong>Horários de Atendimento Calculados</strong>
                    </label>

                    <div id="Dis_scheduled-times">
                        <div id="lista-horarios-vazia">
                            Selecione uma data no calendário, um serviço e a faixa de horário (entre 08:00 e 19:00) para
                            gerar os horários de atendimento.
                        </div>

                        <ul id="lista-horarios">
                            <!-- Horários gerados dinamicamente pelo JavaScript -->
                        </ul>
                    </div>
                </div>

                <div style="text-align: right; margin-top: 15px;">
                    <button type="submit" id="btnSalvar" disabled>
                        <span>Salvar Disponibilidade</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <!-- LISTAGEM DAS DISPONIBILIDADES JÁ CADASTRADAS -->
        <div class="secao-cadastradas">
            <h2>Disponibilidades Cadastradas</h2>

            <?php if (!empty($minhasDisponibilidades)): ?>
                <div class="filtro-cadastradas-bar">
                    <label><strong>Filtrar por Data:</strong></label>
                    <select id="filtroAno" onchange="atualizarMeses()">
                        <option value="nenhuma">Ano...</option>
                        <option value="todas">Todos</option>
                        <?php 
                        $anosUnicos = array_unique(array_map(function($d) { return explode('-', $d)[0]; }, $datasCadastradas));
                        foreach ($anosUnicos as $ano): ?>
                            <option value="<?= htmlspecialchars($ano) ?>"><?= htmlspecialchars($ano) ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select id="filtroMes" onchange="atualizarDias()" disabled>
                        <option value="nenhuma">Mês...</option>
                    </select>

                    <select id="filtroDia" onchange="aplicarFiltroData()" disabled>
                        <option value="nenhuma">Dia...</option>
                    </select>
                </div>

                <!-- AÇÕES EM LOTE -->
                <div id="acoes-lote-data" style="display: none;">
                    <label><strong>Definir todos os horários desta data selecionada como:</strong></label>
                    <div style="margin-top: 8px;">
                        <button type="button" onclick="salvarLote('Disponivel')">✓ Disponível</button>
                        <button type="button" onclick="salvarLote('Folga')">☕ Folga</button>
                        <button type="button" onclick="salvarLote('Ferias')">✈ Férias</button>
                        <button type="button" onclick="salvarLote('Bloqueado')">⛔ Bloqueado</button>
                    </div>
                    <!-- Formulário oculto preenchido pelo JS -->
                    <form action="../../controller/EditarDisponibilidade.php" method="POST" id="formLoteData"
                        style="display: none;">
                    </form>
                </div>

                <!-- TABELA DE DISPONIBILIDADES -->
                <div class="table-disp-wrapper">
                    <table id="tabelaDisponibilidades">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Início</th>
                                <th>Fim</th>
                                <th>Status Atual</th>
                                <th>Editar Status</th>
                                <th style="text-align: center;">Excluir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($minhasDisponibilidades as $disp): ?>
                                <tr data-data="<?= htmlspecialchars($disp['Ava_date']) ?>">
                                    <td><strong><?= date('d/m/Y', strtotime($disp['Ava_date'])) ?></strong></td>
                                    <td><span
                                            style="color: var(--gold-light); font-weight: 600;"><?= htmlspecialchars(substr($disp['Ava_start'], 0, 5)) ?></span>
                                    </td>
                                    <td><span
                                            style="color: var(--text-muted);"><?= htmlspecialchars(substr($disp['Ava_end'], 0, 5)) ?></span>
                                    </td>
                                    <td>
                                        <strong
                                            style="color: <?= $disp['Ava_status'] === 'Disponivel' ? '#2ecc71' : ($disp['Ava_status'] === 'Folga' ? '#f1c40f' : ($disp['Ava_status'] === 'Ferias' ? '#3498db' : '#e74c3c')) ?>;">
                                            ● <?= htmlspecialchars($disp['Ava_status']) ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <!-- FORMULÁRIO DE EDIÇÃO POR AVA_ID -->
                                        <form action="../../controller/EditarDisponibilidade.php" method="POST"
                                            style="display: inline-flex; align-items: center;">
                                            <input type="hidden" name="Ava_id" value="<?= htmlspecialchars($disp['Ava_id']) ?>">
                                            <select name="Ava_status" required>
                                                <option value="Disponivel" <?= $disp['Ava_status'] === 'Disponivel' ? 'selected' : '' ?>>Disponível</option>
                                                <option value="Folga" <?= $disp['Ava_status'] === 'Folga' ? 'selected' : '' ?>>
                                                    Folga</option>
                                                <option value="Ferias" <?= $disp['Ava_status'] === 'Ferias' ? 'selected' : '' ?>>
                                                    Férias</option>
                                                <option value="Bloqueado" <?= $disp['Ava_status'] === 'Bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                                            </select>
                                            <button type="submit">Salvar</button>
                                        </form>
                                    </td>
                                    <td style="text-align: center;">
                                        <!-- FORMULÁRIO DE EXCLUSÃO POR AVA_ID -->
                                        <form action="../../controller/ExcluirDisponibilidade.php" method="POST"
                                            onsubmit="return confirm('Tem certeza que deseja excluir esta disponibilidade?');"
                                            style="margin: 0;">
                                            <input type="hidden" name="Ava_id" value="<?= htmlspecialchars($disp['Ava_id']) ?>">
                                            <button type="submit">✕ Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <p style="color: var(--text-muted); font-size: 15px; margin-top: 15px;">Nenhuma disponibilidade cadastrada
                    no momento.</p>
            <?php endif; ?>
        </div>

    </main>

    <script>
        const datasCadastradasJs = [
            <?php foreach ($datasCadastradas as $data): ?>
                "<?= htmlspecialchars($data) ?>",
            <?php endforeach; ?>
        ];

        const datasHierarchy = {};
        datasCadastradasJs.forEach(data => {
            const parts = data.split('-');
            const ano = parts[0];
            const mes = parts[1];
            const dia = parts[2];
            
            if (!datasHierarchy[ano]) datasHierarchy[ano] = {};
            if (!datasHierarchy[ano][mes]) datasHierarchy[ano][mes] = new Set();
            datasHierarchy[ano][mes].add(dia);
        });

        function getMesesDisponiveis(ano) {
            if (ano === 'todas') {
                const meses = new Set();
                for (let a in datasHierarchy) {
                    for (let m in datasHierarchy[a]) {
                        meses.add(m);
                    }
                }
                return Array.from(meses).sort();
            } else {
                return Object.keys(datasHierarchy[ano] || {}).sort();
            }
        }

        function getDiasDisponiveis(ano, mes) {
            const dias = new Set();
            const anosAvaliar = (ano === 'todas') ? Object.keys(datasHierarchy) : [ano];
            
            for (let a of anosAvaliar) {
                if (datasHierarchy[a]) {
                    const mesesAvaliar = (mes === 'todas') ? Object.keys(datasHierarchy[a]) : [mes];
                    for (let m of mesesAvaliar) {
                        if (datasHierarchy[a][m]) {
                            datasHierarchy[a][m].forEach(d => dias.add(d));
                        }
                    }
                }
            }
            return Array.from(dias).sort();
        }

        function atualizarMeses() {
            const selectAno = document.getElementById('filtroAno');
            const selectMes = document.getElementById('filtroMes');
            const selectDia = document.getElementById('filtroDia');
            
            const ano = selectAno.value;
            
            selectMes.innerHTML = '<option value="nenhuma">Mês...</option>';
            selectDia.innerHTML = '<option value="nenhuma">Dia...</option>';
            selectDia.disabled = true;

            if (ano !== 'nenhuma') {
                selectMes.disabled = false;
                selectMes.innerHTML += '<option value="todas">Todos</option>';
                const meses = getMesesDisponiveis(ano);
                meses.forEach(mes => {
                    selectMes.innerHTML += `<option value="${mes}">${mes}</option>`;
                });
            } else {
                selectMes.disabled = true;
            }
            filtrarTabelaPorData();
        }

        function atualizarDias() {
            const selectAno = document.getElementById('filtroAno');
            const selectMes = document.getElementById('filtroMes');
            const selectDia = document.getElementById('filtroDia');
            
            const ano = selectAno.value;
            const mes = selectMes.value;
            
            selectDia.innerHTML = '<option value="nenhuma">Dia...</option>';

            if (mes !== 'nenhuma') {
                selectDia.disabled = false;
                selectDia.innerHTML += '<option value="todas">Todos</option>';
                const dias = getDiasDisponiveis(ano, mes);
                dias.forEach(dia => {
                    selectDia.innerHTML += `<option value="${dia}">${dia}</option>`;
                });
            } else {
                selectDia.disabled = true;
            }
            filtrarTabelaPorData();
        }

        function aplicarFiltroData() {
            filtrarTabelaPorData();
        }

        function filtrarTabelaPorData() {
            const ano = document.getElementById('filtroAno').value;
            const mes = document.getElementById('filtroMes').value;
            const dia = document.getElementById('filtroDia').value;
            
            const tabela = document.getElementById('tabelaDisponibilidades');
            const linhas = document.querySelectorAll('#tabelaDisponibilidades tbody tr');
            const divLote = document.getElementById('acoes-lote-data');
            
            const isSpecificDate = (ano !== 'nenhuma' && ano !== 'todas') && 
                                   (mes !== 'nenhuma' && mes !== 'todas') && 
                                   (dia !== 'nenhuma' && dia !== 'todas');

            if (ano === 'nenhuma' || mes === 'nenhuma' || dia === 'nenhuma') {
                tabela.style.display = 'none';
                if (divLote) divLote.style.display = 'none';
            } else {
                tabela.style.display = '';
                linhas.forEach(linha => {
                    const dataLine = linha.getAttribute('data-data');
                    const parts = dataLine.split('-');
                    const matchAno = (ano === 'todas' || parts[0] === ano);
                    const matchMes = (mes === 'todas' || parts[1] === mes);
                    const matchDia = (dia === 'todas' || parts[2] === dia);
                    
                    if (matchAno && matchMes && matchDia) {
                        linha.style.display = '';
                    } else {
                        linha.style.display = 'none';
                    }
                });

                if (divLote) {
                    divLote.style.display = isSpecificDate ? 'block' : 'none';
                }
            }
        }

        function salvarLote(status) {
            const formLote = document.getElementById('formLoteData');
            formLote.innerHTML = '';

            const inputStatus = document.createElement('input');
            inputStatus.type = 'hidden';
            inputStatus.name = 'Ava_status';
            inputStatus.value = status;
            formLote.appendChild(inputStatus);

            const linhas = document.querySelectorAll('#tabelaDisponibilidades tbody tr');
            let adicionou = false;
            linhas.forEach(linha => {
                if (linha.style.display !== 'none') {
                    const idInput = linha.querySelector('input[name="Ava_id"]');
                    if (idInput) {
                        const inputId = document.createElement('input');
                        inputId.type = 'hidden';
                        inputId.name = 'Ava_id[]';
                        inputId.value = idInput.value;
                        formLote.appendChild(inputId);
                        adicionou = true;
                    }
                }
            });

            if (adicionou) {
                formLote.submit();
            } else {
                alert('Nenhum horário disponível para edição nesta data.');
            }
        }

        const MIN_TIME = 8 * 60;   // 08:00 (480 minutos)
        const MAX_TIME = 19 * 60;  // 19:00 (1140 minutos)

        function getHojeFormatado() {
            const hoje = new Date();
            const ano = hoje.getFullYear();
            const mes = String(hoje.getMonth() + 1).padStart(2, '0');
            const dia = String(hoje.getDate()).padStart(2, '0');
            return `${ano}-${mes}-${dia}`;
        }

        function atualizarDataMinima() {
            const inputDate = document.getElementById('Dis_date');
            const hoje = getHojeFormatado();
            inputDate.min = hoje;

            // Se o usuário tinha selecionado uma data anterior à data atual (ex: virada de dia), limpa o campo
            if (inputDate.value && inputDate.value < hoje) {
                inputDate.value = '';
            }
        }

        function timeToMinutes(timeStr) {
            if (!timeStr) return null;
            const parts = timeStr.split(':').map(Number);
            return parts[0] * 60 + parts[1];
        }

        function minutesToTime(totalMinutes) {
            const h = String(Math.floor(totalMinutes / 60)).padStart(2, '0');
            const m = String(totalMinutes % 60).padStart(2, '0');
            return `${h}:${m}`;
        }

        let feriadosCache = {};

        async function getFeriados(ano) {
            if (feriadosCache[ano]) return feriadosCache[ano];
            try {
                const response = await fetch(`https://brasilapi.com.br/api/feriados/v1/${ano}`);
                if (response.ok) {
                    const data = await response.json();
                    feriadosCache[ano] = data.map(f => f.date);
                    return feriadosCache[ano];
                }
            } catch (e) {
                console.error('Erro ao buscar feriados', e);
            }
            return [];
        }

        async function validarDiaBloqueado(dateString) {
            if (!dateString) return false;
            const dataObj = new Date(dateString + 'T12:00:00');

            if (dataObj.getDay() === 1) {
                return "Segundas-feiras o estabelecimento está fechado.";
            }

            const ano = dateString.substring(0, 4);
            const feriados = await getFeriados(ano);

            if (feriados.includes(dateString)) {
                return "Este dia é feriado e o estabelecimento está fechado.";
            }

            return false;
        }

        async function validarFormulario() {
            atualizarDataMinima();
            const inputDate = document.getElementById('Dis_date');
            const selectSer = document.getElementById('Dis_ser');
            const inputStart = document.getElementById('Dis_start');
            const inputEnd = document.getElementById('Dis_end');
            const btnSalvar = document.getElementById('btnSalvar');

            let msgErroData = document.getElementById('msg-erro-data');
            if (!msgErroData) {
                msgErroData = document.createElement('div');
                msgErroData.id = 'msg-erro-data';
                msgErroData.style.color = '#ff6b6b';
                msgErroData.style.fontSize = '13px';
                msgErroData.style.marginTop = '8px';
                inputDate.parentNode.appendChild(msgErroData);
            }
            msgErroData.innerText = '';

            const hoje = getHojeFormatado();
            let hasDate = inputDate.value.trim() !== '' && inputDate.value >= hoje;

            if (hasDate) {
                const motivoBloqueio = await validarDiaBloqueado(inputDate.value);
                if (motivoBloqueio) {
                    msgErroData.innerText = motivoBloqueio;
                    hasDate = false;
                }
            }

            const selectedOption = selectSer.options[selectSer.selectedIndex];
            const duration = selectedOption ? parseInt(selectedOption.getAttribute('data-duration')) : 0;
            const hasSer = selectSer.value.trim() !== '' && duration > 0;
            const startMin = timeToMinutes(inputStart.value);
            const endMin = timeToMinutes(inputEnd.value);
            const hasStart = startMin !== null;
            const hasEnd = endMin !== null;

            const isWithinAllowedRange = hasStart && hasEnd &&
                startMin >= MIN_TIME && startMin <= MAX_TIME &&
                endMin >= MIN_TIME && endMin <= MAX_TIME;

            const isValidTime = isWithinAllowedRange && (startMin < endMin) && (startMin + duration <= endMin);

            const podeSalvarEditar = (hasDate && hasSer && isValidTime);

            if (btnSalvar) btnSalvar.disabled = !podeSalvarEditar;
        }

        function gerarHorariosAtendimento() {
            const selectSer = document.getElementById('Dis_ser');
            const inputStart = document.getElementById('Dis_start');
            const inputEnd = document.getElementById('Dis_end');
            const containerLista = document.getElementById('lista-horarios');
            const msgVazia = document.getElementById('lista-horarios-vazia');

            const selectedOption = selectSer.options[selectSer.selectedIndex];
            const duration = selectedOption ? parseInt(selectedOption.getAttribute('data-duration')) : 0;
            const startMin = timeToMinutes(inputStart.value);
            const endMin = timeToMinutes(inputEnd.value);

            containerLista.innerHTML = '';

            if (!duration || startMin === null || endMin === null) {
                msgVazia.style.display = 'block';
                msgVazia.innerText = 'Selecione um serviço, horário de início e término para gerar os horários de atendimento (entre 08:00 e 19:00).';
                containerLista.style.display = 'none';
                validarFormulario();
                return;
            }

            if (startMin < MIN_TIME || startMin > MAX_TIME || endMin < MIN_TIME || endMin > MAX_TIME) {
                msgVazia.style.display = 'block';
                msgVazia.innerText = 'Os horários de início e término devem estar entre 08:00 (8h) e 19:00 (19h).';
                containerLista.style.display = 'none';
                validarFormulario();
                return;
            }

            if (startMin >= endMin) {
                msgVazia.style.display = 'block';
                msgVazia.innerText = 'O horário de início não pode ser maior ou igual ao horário de término.';
                containerLista.style.display = 'none';
                validarFormulario();
                return;
            }

            if (startMin + duration > endMin) {
                msgVazia.style.display = 'block';
                msgVazia.innerText = `O intervalo selecionado (${endMin - startMin} min) é menor que a duração do serviço (${duration} min).`;
                containerLista.style.display = 'none';
                validarFormulario();
                return;
            }

            let count = 0;
            for (let time = startMin; time + duration <= endMin; time += duration) {
                const startTimeStr = minutesToTime(time);
                const endTimeStr = minutesToTime(time + duration);

                const li = document.createElement('li');
                li.innerHTML = `
                    <div>
                        <strong>${startTimeStr} às ${endTimeStr}</strong>
                    </div>
                    <div>
                        <label for="status_${count}" style="font-size:12px; color:var(--text-muted); margin-right:4px;">Status: </label>
                        <select name="horarios[${count}][status]" id="status_${count}" class="status-select">
                            <option value="Disponivel" selected>Disponível</option>
                            <option value="Folga">Folga</option>
                            <option value="Ferias">Férias</option>
                            <option value="Bloqueado">Bloqueado</option>
                        </select>
                        <input type="hidden" name="horarios[${count}][start]" value="${startTimeStr}">
                        <input type="hidden" name="horarios[${count}][end]" value="${endTimeStr}">
                    </div>
                `;
                containerLista.appendChild(li);
                count++;
            }

            msgVazia.style.display = 'none';
            containerLista.style.display = 'grid';

            validarFormulario();
        }

        document.getElementById('Dis_date').addEventListener('focus', atualizarDataMinima);
        document.getElementById('Dis_date').addEventListener('input', validarFormulario);
        document.getElementById('Dis_date').addEventListener('change', validarFormulario);
        document.getElementById('Dis_ser').addEventListener('change', gerarHorariosAtendimento);
        document.getElementById('Dis_start').addEventListener('input', gerarHorariosAtendimento);
        document.getElementById('Dis_start').addEventListener('change', gerarHorariosAtendimento);
        document.getElementById('Dis_end').addEventListener('input', gerarHorariosAtendimento);
        document.getElementById('Dis_end').addEventListener('change', gerarHorariosAtendimento);

        // Atualiza a data mínima periodicamente (a cada 60s) para garantir o bloqueio assim que o dia virar
        setInterval(validarFormulario, 60000);

        // Validação e configuração inicial ao carregar a página
        validarFormulario();
        filtrarTabelaPorData();

        // Calendário customizado
        const calendario = document.getElementById('calendario-customizado');

        if (calendario) {
            calendario.innerHTML = `
                <div class="calendario-header">
                    <button type="button" class="calendario-btn">‹</button>
                    <span class="calendario-mes"></span>
                    <button type="button" class="calendario-btn">›</button>
                </div>

                <div class="calendario-semana">
                    <span>Dom</span>
                    <span>Seg</span>
                    <span>Ter</span>
                    <span>Qua</span>
                    <span>Qui</span>
                    <span>Sex</span>
                    <span>Sáb</span>
                </div>

                <div class="calendario-dias"></div>
            `;
        }

        let dataCalendario = new Date();

        function gerarCalendario() {
            const mesElemento = document.querySelector('.calendario-mes');
            const diasElemento = document.querySelector('.calendario-dias');

            if (!mesElemento || !diasElemento) return;

            const ano = dataCalendario.getFullYear();
            const mes = dataCalendario.getMonth();

            const nomeMeses = [
                'Janeiro', 'Fevereiro', 'Março', 'Abril',
                'Maio', 'Junho', 'Julho', 'Agosto',
                'Setembro', 'Outubro', 'Novembro', 'Dezembro'
            ];

            mesElemento.textContent = `${nomeMeses[mes]} ${ano}`;
            diasElemento.innerHTML = '';

            const primeiroDia = new Date(ano, mes, 1).getDay();
            const ultimoDia = new Date(ano, mes + 1, 0).getDate();

            // Espaços antes do primeiro dia do mês
            for (let i = 0; i < primeiroDia; i++) {
                diasElemento.appendChild(document.createElement('span'));
            }

            // Criação dos dias
            for (let dia = 1; dia <= ultimoDia; dia++) {
                const elementoDia = document.createElement('button');
                elementoDia.type = 'button';
                elementoDia.textContent = dia;

                const dataDia = new Date(ano, mes, dia);
                const hoje = new Date();

                dataDia.setHours(0, 0, 0, 0);
                hoje.setHours(0, 0, 0, 0);

                // Bloqueia dias anteriores a hoje
                if (dataDia < hoje) {
                    elementoDia.disabled = true;
                }

                // Clique no dia
                elementoDia.addEventListener('click', function () {
                    const campoData = document.getElementById('Dis_date');
                    const mesFormatado = String(mes + 1).padStart(2, '0');
                    const diaFormatado = String(dia).padStart(2, '0');
                    const dataSelecionada = `${ano}-${mesFormatado}-${diaFormatado}`;

                    // Se clicar novamente no mesmo dia, desmarca
                    if (elementoDia.classList.contains('dia-selecionado')) {
                        elementoDia.classList.remove('dia-selecionado');
                        campoData.value = '';
                    } else {
                        // Remove seleção de outros dias
                        document.querySelectorAll('.calendario-dias button')
                            .forEach(botao => {
                                botao.classList.remove('dia-selecionado');
                            });

                        // Seleciona o novo dia
                        elementoDia.classList.add('dia-selecionado');
                        campoData.value = dataSelecionada;
                    }

                    validarFormulario();
                });

                diasElemento.appendChild(elementoDia);
            }
        }

        gerarCalendario();

        const botoesCalendario = document.querySelectorAll('.calendario-btn');

        if (botoesCalendario.length >= 2) {
            // Botão mês anterior
            botoesCalendario[0].addEventListener('click', function () {
                const hoje = new Date();
                const mesAtual = hoje.getMonth();
                const anoAtual = hoje.getFullYear();

                const mesCalendario = dataCalendario.getMonth();
                const anoCalendario = dataCalendario.getFullYear();

                // Impede voltar para meses anteriores ao atual
                if (
                    anoCalendario > anoAtual ||
                    (anoCalendario === anoAtual && mesCalendario > mesAtual)
                ) {
                    dataCalendario.setMonth(dataCalendario.getMonth() - 1);
                    gerarCalendario();
                }
            });

            // Botão próximo mês
            botoesCalendario[1].addEventListener('click', function () {
                dataCalendario.setMonth(dataCalendario.getMonth() + 1);
                gerarCalendario();
            });
        }
    </script>
</body>

</html>