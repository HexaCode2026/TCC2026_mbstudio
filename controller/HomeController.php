<?php
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../model/Service.php';
require_once __DIR__ . '/../model/Employee.php';
require_once __DIR__ . '/../model/Imagem.php';

/**
 * HomeController — Controlador responsável pela lógica de acesso e carregamento
 * dos dados da página inicial (Index.php) dentro da arquitetura MVC.
 */
class HomeController
{
    private ?PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    /**
     * Inicializa a sessão e redireciona administradores/funcionários para seus painéis
     * caso não estejam no modo de pré-visualização (?preview=1).
     */
    public function verificarAcesso(): void
    {
        Session::iniciar();

        if (isset($_SESSION['User_perm']) && empty($_GET['preview'])) {
            if ($_SESSION['User_perm'] === 'A') {
                header("Location: View/admin/Home.php");
                exit;
            } elseif ($_SESSION['User_perm'] === 'F') {
                header("Location: View/funcionario/Home.php");
                exit;
            }
        }
    }

    /**
     * Carrega os dados necessários para renderizar a View da Home (serviços, equipe, novidades e hero).
     */
    public function carregarDadosHome(string $rootDir): array
    {
        $servicos     = [];
        $funcionarios = [];
        $novidades    = [];
        $destaques    = [];

        if (!$this->pdo) {
            return compact('servicos', 'funcionarios', 'novidades', 'destaques');
        }

        try {
            $serviceModel  = new Service($this->pdo);
            $employeeModel = new Employee($this->pdo);
            $imagemModel   = new Imagem($this->pdo);

            $servicos     = $serviceModel->listarAtivosHome(6);
            $funcionarios = $employeeModel->listarDestaquesHome(4);
            $novidades    = $serviceModel->listarNovidadesHome(3);

            $slidesHeroDb = $imagemModel->listarAtivasHero($rootDir);

            if (!empty($slidesHeroDb)) {
                foreach ($slidesHeroDb as $sh) {
                    $rawSub = trim($sh['Img_subtitle'] ?? '');
                    if ($rawSub !== '' && is_numeric(str_replace(',', '.', $rawSub))) {
                        $rawSub = 'R$ ' . number_format((float)str_replace(',', '.', $rawSub), 2, ',', '.');
                    }
                    $destaques[] = [
                        'title'    => $sh['Img_title'],
                        'subtitle' => $rawSub,
                        'image'    => $sh['Img_path']
                    ];
                }
            } else {
                foreach ($servicos as $s) {
                    $imgPath = $s['Ser_image'] ?? '';
                    if (!empty($imgPath) && (file_exists($imgPath) || file_exists($rootDir . '/' . $imgPath))) {
                        $destaques[] = [
                            'title'    => $s['Ser_name'],
                            'subtitle' => 'R$ ' . number_format((float)$s['Ser_price'], 2, ',', '.'),
                            'image'    => $imgPath
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            $servicos     = [];
            $funcionarios = [];
            $novidades    = [];
            $destaques    = [];
        }

        return compact('servicos', 'funcionarios', 'novidades', 'destaques');
    }
}

