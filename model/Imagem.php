<?php

class Imagem
{
    private $pdo;
    private static bool $tabelaVerificada = false;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Cria a tabela site_images apenas sob demanda (Lazy Initialization) caso uma consulta lance erro 42S02,
     * eliminando contenção de Metadata Lock (DDL) em cada carregamento da Home.
     */
    private function garantirTabela(): void
    {
        if (self::$tabelaVerificada) {
            return;
        }
        self::$tabelaVerificada = true;

        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS site_images (
                    Img_id INT AUTO_INCREMENT PRIMARY KEY,
                    Img_title VARCHAR(150) NOT NULL,
                    Img_subtitle VARCHAR(100) DEFAULT NULL,
                    Img_path VARCHAR(255) NOT NULL,
                    Img_section VARCHAR(50) NOT NULL DEFAULT 'hero',
                    Img_order INT NOT NULL DEFAULT 1,
                    Img_active TINYINT(1) NOT NULL DEFAULT 1,
                    Img_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_img_section_active_order (Img_section, Img_active, Img_order)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            $this->pdo->exec($sql);

            $count = (int) $this->pdo->query("SELECT COUNT(*) FROM site_images")->fetchColumn();
            if ($count === 0) {
                $stmtServ = $this->pdo->query("
                    SELECT Ser_name, Ser_price, Ser_image
                    FROM services
                    WHERE Ser_active = 1 AND Ser_image IS NOT NULL AND Ser_image != ''
                    ORDER BY Ser_id ASC
                    LIMIT 8
                ");
                $servicos = $stmtServ ? $stmtServ->fetchAll(PDO::FETCH_ASSOC) : [];
                $ordem = 1;
                $insert = $this->pdo->prepare("
                    INSERT INTO site_images (Img_title, Img_subtitle, Img_path, Img_section, Img_order, Img_active)
                    VALUES (?, ?, ?, 'hero', ?, 1)
                ");
                foreach ($servicos as $s) {
                    $sub = 'R$ ' . number_format((float)$s['Ser_price'], 2, ',', '.');
                    $insert->execute([$s['Ser_name'], $sub, $s['Ser_image'], $ordem++]);
                }
            }
        } catch (Exception $e) {
            // Falha silenciosa se banco indisponível
        }
    }

    /**
     * Lista todas as imagens do carrossel / destaque
     */
    public function listarTodas($secao = null)
    {
        try {
            if ($secao) {
                $stmt = $this->pdo->prepare("SELECT * FROM site_images WHERE Img_section = ? ORDER BY Img_order ASC, Img_id DESC");
                $stmt->execute([$secao]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            $stmt = $this->pdo->query("SELECT * FROM site_images ORDER BY Img_order ASC, Img_id DESC");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (PDOException $e) {
            $this->garantirTabela();
            try {
                if ($secao) {
                    $stmt = $this->pdo->prepare("SELECT * FROM site_images WHERE Img_section = ? ORDER BY Img_order ASC, Img_id DESC");
                    $stmt->execute([$secao]);
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                $stmt = $this->pdo->query("SELECT * FROM site_images ORDER BY Img_order ASC, Img_id DESC");
                return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            } catch (Exception $ex) {
                return [];
            }
        }
    }

    /**
     * Lista apenas as imagens ativas para exibição no quadrado principal da Home
     */
    public function listarAtivasHero($projectRootDir = null)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM site_images
                WHERE Img_section = 'hero' AND Img_active = 1
                ORDER BY Img_order ASC, Img_id DESC
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->garantirTabela();
            try {
                $stmt = $this->pdo->prepare("
                    SELECT * FROM site_images
                    WHERE Img_section = 'hero' AND Img_active = 1
                    ORDER BY Img_order ASC, Img_id DESC
                ");
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $ex) {
                $rows = [];
            }
        }

        if (!$projectRootDir) {
            $projectRootDir = realpath(__DIR__ . '/../');
        }

        $validas = [];
        foreach ($rows as $r) {
            $path = $r['Img_path'] ?? '';
            if (!empty($path) && (file_exists($path) || file_exists($projectRootDir . '/' . $path))) {
                $validas[] = $r;
            }
        }
        return $validas;
    }

    /**
     * Busca uma imagem pelo ID
     */
    public function buscarPorId($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM site_images WHERE Img_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Adiciona uma nova imagem ao carrossel / quadrado de destaque
     */
    public function adicionar($titulo, $subtitulo, $caminho, $secao = 'hero', $ordem = 1, $ativo = 1)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO site_images (Img_title, Img_subtitle, Img_path, Img_section, Img_order, Img_active)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$titulo, $subtitulo, $caminho, $secao, (int)$ordem, (int)$ativo]);
        } catch (PDOException $e) {
            $this->garantirTabela();
            $stmt = $this->pdo->prepare("
                INSERT INTO site_images (Img_title, Img_subtitle, Img_path, Img_section, Img_order, Img_active)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$titulo, $subtitulo, $caminho, $secao, (int)$ordem, (int)$ativo]);
        }
    }

    /**
     * Atualiza os dados e/ou arquivo de uma imagem existente
     */
    public function atualizar($id, $titulo, $subtitulo, $caminho = null, $ordem = 1, $ativo = 1)
    {
        if ($caminho !== null && $caminho !== '') {
            $stmt = $this->pdo->prepare("
                UPDATE site_images
                SET Img_title = ?, Img_subtitle = ?, Img_path = ?, Img_order = ?, Img_active = ?
                WHERE Img_id = ?
            ");
            return $stmt->execute([$titulo, $subtitulo, $caminho, (int)$ordem, (int)$ativo, $id]);
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE site_images
                SET Img_title = ?, Img_subtitle = ?, Img_order = ?, Img_active = ?
                WHERE Img_id = ?
            ");
            return $stmt->execute([$titulo, $subtitulo, (int)$ordem, (int)$ativo, $id]);
        }
    }

    /**
     * Alterna o status ativo/inativo da imagem
     */
    public function alternarStatus($id, $novoStatus)
    {
        $stmt = $this->pdo->prepare("UPDATE site_images SET Img_active = ? WHERE Img_id = ?");
        return $stmt->execute([(int)$novoStatus, $id]);
    }

    /**
     * Exclui uma imagem da tabela
     */
    public function excluir($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM site_images WHERE Img_id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Lista todos os serviços para permitir modificar as imagens da Galeria de Cortes & Novidades
     */
    public function listarServicosGaleria()
    {
        $stmt = $this->pdo->query("SELECT Ser_id, Ser_name, Ser_price, Ser_image, Ser_active FROM services ORDER BY Ser_name ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Atualiza diretamente a imagem de um serviço da Galeria / Novidades
     */
    public function atualizarImagemServico($serId, $novoCaminho)
    {
        $stmt = $this->pdo->prepare("UPDATE services SET Ser_image = ? WHERE Ser_id = ?");
        return $stmt->execute([$novoCaminho, $serId]);
    }
}
?>
