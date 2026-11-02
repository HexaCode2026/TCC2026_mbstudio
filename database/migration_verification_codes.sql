-- ============================================================================
-- MIGRAÇÃO CONSOLIDADA DE ALTA CONCORRÊNCIA, ÍNDICES E FILA DE E-MAILS (SRE)
-- ============================================================================
-- Execute este script sobre o banco `tcc_agendamento` já existente para:
-- 1. Adicionar auditoria `Code_used_at` e índice em `verification_codes`
-- 2. Adicionar índices compostos em `appointments`, `employee_blocks`,
--    `availabilities`, `users` e `services` (elimina Table Locks no FOR UPDATE)
-- 3. Criar as tabelas `site_images` e `email_queue` (Outbox Pattern)
-- ============================================================================

USE tcc_agendamento;

-- 1. Otimização e Blindagem Criptográfica de Códigos de Verificação (2FA / Recuperação / Cadastro)
--    Code_token VARCHAR(255) acomoda o formato Salted HMAC-SHA256 (hmac256$<32_hex_salt>$<64_hex_mac> = 105 chars)
--    idx_verification_latest atende diretamente 'ORDER BY Code_id DESC LIMIT 1 FOR UPDATE' com zero filesort
ALTER TABLE verification_codes
    MODIFY COLUMN Code_token VARCHAR(255) NOT NULL,
    ADD COLUMN Code_used_at DATETIME NULL DEFAULT NULL AFTER Code_used,
    ADD INDEX idx_verification_latest (User_id, Code_type, Code_used, Code_id DESC),
    ADD INDEX idx_verification_lookup (User_id, Code_type, Code_used, Code_expires_at);

-- 1.1 Garantia de Coluna para Hashes de Senha Adaptativos (Bcrypt cost=11 / Argon2id)
ALTER TABLE users
    MODIFY COLUMN User_pass VARCHAR(255) NOT NULL;

-- 2. Índices Compostos para Agendamentos e Bloqueios (Elimina Deadlocks no FOR UPDATE)
ALTER TABLE appointments
    ADD INDEX idx_appo_emp_date_status (Emp_id, Appo_date, Appo_status, Appo_start, Appo_end),
    ADD INDEX idx_appo_date_status (Appo_date, Appo_status);

ALTER TABLE employee_blocks
    ADD INDEX idx_block_emp_date (Emp_id, Block_date, Block_start, Block_end);

ALTER TABLE availabilities
    ADD INDEX idx_ava_lookup (Ava_date, Ser_id, Ava_status, Emp_id, Ava_start),
    ADD INDEX idx_ava_emp_date (Emp_id, Ava_date);

-- 3. Índices de Usuários e Serviços (Acelera Dashboard e Home)
ALTER TABLE users
    ADD INDEX idx_users_perm_active (User_perm, User_active);

ALTER TABLE services
    ADD INDEX idx_services_active (Ser_active);

-- 4. Tabela de Imagens da Home (evita DDL em runtime)
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

-- 5. Tabela de Fila Assíncrona de E-mails (Outbox Pattern)
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
