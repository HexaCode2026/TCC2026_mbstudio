-- Em hospedagens compartilhadas (ex: Hostinger), crie o banco pelo painel (hPanel),
-- selecione-o no phpMyAdmin e importe este arquivo diretamente (DROP/CREATE DATABASE é bloqueado).
-- Para uso local, descomente as linhas abaixo caso deseje recriar o banco do zero:
-- DROP DATABASE IF EXISTS tcc_agendamento;
-- CREATE DATABASE tcc_agendamento;
-- USE tcc_agendamento;

-- ==========================================
-- 1. USUÁRIOS
-- ==========================================
CREATE TABLE users(
    User_id INT AUTO_INCREMENT PRIMARY KEY,
    User_name VARCHAR(100) NOT NULL,
    User_email VARCHAR(150) NOT NULL UNIQUE,
    User_pass VARCHAR(255) NOT NULL,
    User_perm ENUM('A','F','C') DEFAULT 'C',
    User_active BOOLEAN DEFAULT TRUE,
    User_must_change_pass BOOLEAN DEFAULT FALSE,
    User_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_perm_active (User_perm, User_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seeder Idempotente do Administrador Inicial (Senha temporária: 'admin123' com Bcrypt)
INSERT INTO users (User_name, User_email, User_pass, User_perm, User_active, User_must_change_pass)
SELECT 'Administrador', 'maisonbelle.com@gmail.com', '$2y$10$E4i6qMUCtr3u0eLLGiZ2UeiJhiKLarjwqgGteyi0gv7w7MlbSjide', 'A', 1, 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE User_email = 'maisonbelle.com@gmail.com'
);

-- ==========================================
-- 2. CLIENTES
-- ==========================================
CREATE TABLE clients(
    Cli_id INT AUTO_INCREMENT PRIMARY KEY,
    User_id INT UNIQUE NOT NULL,
    Cli_phone VARCHAR(20),
    Cli_birth DATE,
    Cli_observation TEXT,
    FOREIGN KEY(User_id) REFERENCES users(User_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 3. FUNCIONÁRIOS
-- ==========================================
CREATE TABLE employees(
    Emp_id INT AUTO_INCREMENT PRIMARY KEY,
    User_id INT UNIQUE NOT NULL,
    Emp_photo VARCHAR(255),
    Emp_specialty VARCHAR(100),
    Emp_bio TEXT,
    FOREIGN KEY(User_id) REFERENCES users(User_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 4. SERVIÇOS
-- ==========================================
CREATE TABLE services(
    Ser_id INT AUTO_INCREMENT PRIMARY KEY,
    Ser_name VARCHAR(100) NOT NULL,
    Ser_description TEXT,
    Ser_price DECIMAL(10,2) NOT NULL,
    Ser_duration INT NOT NULL,
    Ser_image VARCHAR(255),
    Ser_active BOOLEAN DEFAULT TRUE,
    INDEX idx_services_active (Ser_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 5. FUNCIONÁRIO X SERVIÇO (Relacionamento)
-- ==========================================
CREATE TABLE employee_services(
    EmpSer_id INT AUTO_INCREMENT PRIMARY KEY,
    Emp_id INT NOT NULL,
    Ser_id INT NOT NULL,
    FOREIGN KEY(Emp_id) REFERENCES employees(Emp_id) ON DELETE CASCADE,
    FOREIGN KEY(Ser_id) REFERENCES services(Ser_id) ON DELETE CASCADE,
    UNIQUE(Emp_id,Ser_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 6. DISPONIBILIDADE
-- ==========================================
CREATE TABLE availabilities(
    Ava_id INT AUTO_INCREMENT PRIMARY KEY,
    Emp_id INT NOT NULL,
    Ser_id INT NULL,
    Ava_date DATE NOT NULL,
    Ava_start TIME NOT NULL,
    Ava_end TIME NOT NULL,
    Ava_status ENUM('Disponivel','Folga','Ferias','Bloqueado') DEFAULT 'Disponivel',
    FOREIGN KEY(Emp_id) REFERENCES employees(Emp_id) ON DELETE CASCADE,
    FOREIGN KEY(Ser_id) REFERENCES services(Ser_id) ON DELETE SET NULL,
    INDEX idx_ava_lookup (Ava_date, Ser_id, Ava_status, Emp_id, Ava_start),
    INDEX idx_ava_emp_date (Emp_id, Ava_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 7. BLOQUEIOS DA AGENDA
-- ==========================================
CREATE TABLE employee_blocks(
    Block_id INT AUTO_INCREMENT PRIMARY KEY,
    Emp_id INT NOT NULL,
    Block_date DATE NOT NULL,
    Block_start TIME NOT NULL,
    Block_end TIME NOT NULL,
    Block_reason VARCHAR(255),
    FOREIGN KEY(Emp_id) REFERENCES employees(Emp_id) ON DELETE CASCADE,
    INDEX idx_block_emp_date (Emp_id, Block_date, Block_start, Block_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 8. AGENDAMENTOS
-- ==========================================
CREATE TABLE appointments(
    Appo_id INT AUTO_INCREMENT PRIMARY KEY,
    Cli_id INT NOT NULL,
    Emp_id INT NOT NULL,
    Ser_id INT NOT NULL,
    Appo_price DECIMAL(10,2) NULL DEFAULT NULL,
    Appo_date DATE NOT NULL,
    Appo_start TIME NOT NULL,
    Appo_end TIME NOT NULL,
    Appo_status ENUM(
        'Pendente',
        'Confirmado',
        'Em Atendimento',
        'Concluido',
        'Cancelado pelo Cliente',
        'Cancelado pelo Funcionario',
        'Cancelado pelo Administrador',
        'Nao Compareceu'
    ) DEFAULT 'Pendente',
    Appo_payment_method ENUM('Pix', 'Dinheiro', 'Cartão') NULL,
    Appo_cancel_by ENUM('Cliente','Funcionario','Administrador') NULL,
    Appo_cancel_reason TEXT,
    Appo_observation TEXT,
    Appo_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Appo_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(Cli_id) REFERENCES clients(Cli_id) ON DELETE RESTRICT,
    FOREIGN KEY(Emp_id) REFERENCES employees(Emp_id) ON DELETE RESTRICT,
    FOREIGN KEY(Ser_id) REFERENCES services(Ser_id) ON DELETE RESTRICT,
    INDEX idx_appo_emp_date_status (Emp_id, Appo_date, Appo_status, Appo_start, Appo_end),
    INDEX idx_appo_date_status (Appo_date, Appo_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 9. VIEW: RELATÓRIO DO ADMINISTRADOR
-- ==========================================
CREATE VIEW vw_relatorio_admin AS
SELECT 
    a.Appo_id AS ID_Agendamento,
    a.Appo_date AS Data_Agendamento,
    a.Appo_start AS Hora_Inicio,
    a.Appo_end AS Hora_Fim,
    c_user.User_name AS Nome_Cliente,
    e_user.User_name AS Nome_Profissional,
    a.Emp_id AS ID_Profissional,
    s.Ser_name AS Servico_Realizado,
    COALESCE(a.Appo_price, s.Ser_price) AS Valor_Servico,
    a.Appo_status AS Status,
    a.Appo_payment_method AS Forma_Pagamento,
    a.Appo_cancel_by AS Cancelado_Por
FROM appointments a
JOIN clients c ON a.Cli_id = c.Cli_id
JOIN users c_user ON c.User_id = c_user.User_id
JOIN employees e ON a.Emp_id = e.Emp_id
JOIN users e_user ON e.User_id = e_user.User_id
JOIN services s ON a.Ser_id = s.Ser_id;

-- ==========================================
-- 10. CÓDIGOS DE VERIFICAÇÃO (Email/2FA)
-- ==========================================
CREATE TABLE verification_codes (
    Code_id INT AUTO_INCREMENT PRIMARY KEY,
    User_id INT NOT NULL,
    Code_token VARCHAR(255) NOT NULL,
    Code_type ENUM('Cadastro', 'Login2FA', 'RecuperarSenha') DEFAULT 'Cadastro',
    Code_expires_at DATETIME NOT NULL,
    Code_attempts INT DEFAULT 0,
    Code_used BOOLEAN DEFAULT FALSE,
    Code_used_at DATETIME NULL DEFAULT NULL,
    Code_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(User_id) REFERENCES users(User_id) ON DELETE CASCADE,
    INDEX idx_verification_latest (User_id, Code_type, Code_used, Code_id DESC),
    INDEX idx_verification_lookup (User_id, Code_type, Code_used, Code_expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 11. IMAGENS DO CARROSSEL / DESTAQUE HOME
-- ==========================================
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

-- ==========================================
-- 12. FILA ASSÍNCRONA DE E-MAILS (OUTBOX)
-- ==========================================
CREATE TABLE IF NOT EXISTS email_queue (
    Queue_id INT AUTO_INCREMENT PRIMARY KEY,
    Recipient_email VARCHAR(150) NOT NULL,
    Recipient_name VARCHAR(100) NOT NULL,
    Subject VARCHAR(200) NOT NULL,
    Body_html MEDIUMTEXT NOT NULL,
    Body_alt TEXT NULL,
    Status ENUM('Pendente', 'Processando', 'Enviado', 'Falha') DEFAULT 'Pendente',
    Attempts TINYINT UNSIGNED DEFAULT 0,
    Last_error TEXT NULL,
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Processed_at DATETIME NULL DEFAULT NULL,
    INDEX idx_queue_status_created (Status, Attempts, Created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
