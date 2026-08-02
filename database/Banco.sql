DROP DATABASE IF EXISTS tcc_agendamento;
CREATE DATABASE tcc_agendamento;
USE tcc_agendamento;

-- ==========================================
-- USUÁRIOS
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
-- CLIENTES
-- ==========================================

CREATE TABLE clients(

    Cli_id INT AUTO_INCREMENT PRIMARY KEY,

    User_id INT UNIQUE NOT NULL,

    Cli_phone VARCHAR(20),

    Cli_birth DATE,

    FOREIGN KEY(User_id)
    REFERENCES users(User_id)
    ON DELETE CASCADE

);

-- ==========================================
-- FUNCIONÁRIOS
-- ==========================================

CREATE TABLE employees(

    Emp_id INT AUTO_INCREMENT PRIMARY KEY,

    User_id INT UNIQUE NOT NULL,

    Emp_photo VARCHAR(255),

    Emp_specialty VARCHAR(100),

    Emp_bio TEXT,

    FOREIGN KEY(User_id)
    REFERENCES users(User_id)
    ON DELETE CASCADE

);

-- ==========================================
-- SERVIÇOS
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
-- FUNCIONÁRIO X SERVIÇO
-- ==========================================

CREATE TABLE employee_services(

    EmpSer_id INT AUTO_INCREMENT PRIMARY KEY,

    Emp_id INT NOT NULL,

    Ser_id INT NOT NULL,

    FOREIGN KEY(Emp_id)
    REFERENCES employees(Emp_id)
    ON DELETE CASCADE,

    FOREIGN KEY(Ser_id)
    REFERENCES services(Ser_id)
    ON DELETE CASCADE,

    UNIQUE(Emp_id,Ser_id)

);

-- ==========================================
-- DISPONIBILIDADE
-- ==========================================

CREATE TABLE availabilities(

    Ava_id INT AUTO_INCREMENT PRIMARY KEY,

    Emp_id INT NOT NULL,

    Ava_date DATE NOT NULL,

    Ava_start TIME NOT NULL,

    Ava_end TIME NOT NULL,

    Ava_status ENUM(
        'Disponivel',
        'Folga',
        'Ferias',
        'Bloqueado'
    ) DEFAULT 'Disponivel',

    FOREIGN KEY(Emp_id)
    REFERENCES employees(Emp_id)
    ON DELETE CASCADE

);

-- ==========================================
-- BLOQUEIOS DA AGENDA
-- ==========================================

CREATE TABLE employee_blocks(

    Block_id INT AUTO_INCREMENT PRIMARY KEY,

    Emp_id INT NOT NULL,

    Block_date DATE NOT NULL,

    Block_start TIME NOT NULL,

    Block_end TIME NOT NULL,

    Block_reason VARCHAR(255),

    FOREIGN KEY(Emp_id)
    REFERENCES employees(Emp_id)
    ON DELETE CASCADE

);

-- ==========================================
-- AGENDAMENTOS
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

    Appo_cancel_by ENUM(

        'Cliente',

        'Funcionario',

        'Administrador'

    ) NULL,

    Appo_cancel_reason TEXT,

    Appo_observation TEXT,

    Appo_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    Appo_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY(Cli_id)
    REFERENCES clients(Cli_id)
    ON DELETE CASCADE,

    FOREIGN KEY(Emp_id)
    REFERENCES employees(Emp_id)
    ON DELETE CASCADE,

    FOREIGN KEY(Ser_id)
    REFERENCES services(Ser_id)
    ON DELETE CASCADE

);