DROP DATABASE IF EXISTS tcc_agendamento;
CREATE DATABASE tcc_agendamento;
USE tcc_agendamento;

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
    User_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- 2. CLIENTES
-- ==========================================
CREATE TABLE clients(
    Cli_id INT AUTO_INCREMENT PRIMARY KEY,
    User_id INT UNIQUE NOT NULL,
    Cli_phone VARCHAR(20),
    Cli_birth DATE,
    Cli_observation TEXT, -- Observações internas que os funcionários podem adicionar
    FOREIGN KEY(User_id) REFERENCES users(User_id) ON DELETE CASCADE
);

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
);

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
    Ser_active BOOLEAN DEFAULT TRUE
);

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
);

-- ==========================================
-- 6. DISPONIBILIDADE
-- ==========================================
CREATE TABLE availabilities(
    Ava_id INT AUTO_INCREMENT PRIMARY KEY,
    Emp_id INT NOT NULL,
    Ava_date DATE NOT NULL,
    Ava_start TIME NOT NULL,
    Ava_end TIME NOT NULL,
    Ava_status ENUM('Disponivel','Folga','Ferias','Bloqueado') DEFAULT 'Disponivel',
    FOREIGN KEY(Emp_id) REFERENCES employees(Emp_id) ON DELETE CASCADE
);

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
    FOREIGN KEY(Emp_id) REFERENCES employees(Emp_id) ON DELETE CASCADE
);

-- ==========================================
-- 8. AGENDAMENTOS
-- ==========================================
CREATE TABLE appointments(
    Appo_id INT AUTO_INCREMENT PRIMARY KEY,
    Cli_id INT NOT NULL,
    Emp_id INT NOT NULL,
    Ser_id INT NOT NULL,
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
    
    Appo_payment_method ENUM('Pix', 'Dinheiro', 'Cartão') NULL, -- Forma de pagamento selecionada na finalização
    
    Appo_cancel_by ENUM('Cliente','Funcionario','Administrador') NULL,
    Appo_cancel_reason TEXT,
    Appo_observation TEXT,
    Appo_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Appo_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY(Cli_id) REFERENCES clients(Cli_id) ON DELETE CASCADE,
    FOREIGN KEY(Emp_id) REFERENCES employees(Emp_id) ON DELETE CASCADE,
    FOREIGN KEY(Ser_id) REFERENCES services(Ser_id) ON DELETE CASCADE
);

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
    s.Ser_price AS Valor_Servico,
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
    Code_type ENUM('Cadastro', 'Login2FA') DEFAULT 'Cadastro',
    Code_expires_at DATETIME NOT NULL,
    Code_attempts INT DEFAULT 0,
    Code_used BOOLEAN DEFAULT FALSE,
    Code_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(User_id) REFERENCES users(User_id) ON DELETE CASCADE
);tcc_agendamento