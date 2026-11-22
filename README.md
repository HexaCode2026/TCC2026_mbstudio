# MB Studio — Sistema de Agendamento para Salão de Beleza

Sistema web completo de agendamento, gestão de horários e administração de salão de beleza, desenvolvido em **PHP + MySQL (PDO)** com arquitetura **MVC**, autenticação com verificação em duas etapas (**2FA**) por e-mail e controle de acesso baseado em perfis (**RBAC**).

---

## Funcionalidades por Perfil

### Visitante & Cliente (`C`)
- **Página Inicial (`Index.php`):** Apresentação do estúdio, carrossel de destaques, catálogo de serviços e equipe de especialistas.
- **Autenticação Segura:** Cadastro com confirmação por código de 6 dígitos via e-mail, Login com 2FA e Recuperação de Senha.
- **Agendamento em 4 Etapas:** Escolha do Serviço → Seleção de Data → Seleção de Profissional e Horário Disponível → Confirmação.
- **Meus Agendamentos:** Acompanhamento de status em tempo real e cancelamento com justificativa.
- **Perfil:** Atualização de dados cadastrais, telefone, data de nascimento e senha.

### Profissional / Funcionário (`F`)
- **Minha Agenda:** Visualização dos atendimentos com ações de confirmar, iniciar atendimento, finalizar com forma de pagamento (`Pix`, `Dinheiro`, `Cartão`), registrar ausência (`Nao Compareceu`) ou cancelar.
- **Meus Horários (Disponibilidade):** Cadastro e edição de janelas de atendimento por serviço e data (com bloqueio automático de segundas-feiras e feriados nacionais via BrasilAPI).
- **Pausas e Bloqueios:** Registro de intervalos e indisponibilidades pontuais sem conflitar com agendamentos ativos.
- **Meu Perfil:** Edição de especialidade, biografia, foto de perfil e senha.

### Administrador (`A`)
- **Dashboard:** Indicadores gerais de usuários, clientes, profissionais, serviços e agendamentos.
- **Agenda Global:** Acompanhamento e gestão de todos os atendimentos do estúdio.
- **Gestão de Usuários & Equipe:** Alteração de permissões (`A`, `F`, `C`), ativação/inativação e exclusão de contas.
- **Gestão de Serviços:** Cadastro, edição, ativação/inativação e vínculo entre serviços e profissionais (`employee_services`).
- **Gestão de Imagens:** Gerenciamento das imagens do carrossel principal (`site_images`) e dos serviços.
- **Relatórios Gerenciais:** Filtros por período e profissional com totalização por status e faturamento (`vw_relatorio_admin`).
- **Diagnóstico SMTP (`/teste-email`):** Ferramenta restrita ao administrador para testar o envio dos templates de e-mail.

---

## Tecnologias Utilizadas

- **Back-end:** PHP 8+ (PDO, sessões seguras, CSRF, HMAC-SHA256, Bcrypt)
- **Banco de Dados:** MySQL / MariaDB
- **E-mail:** PHPMailer (`libs/PHPMailer`) via SMTP + padrão Outbox opcional (`email_queue`)
- **Front-end:** HTML5, CSS3, JavaScript vanilla
- **Servidor Web:** Apache com `mod_rewrite` e `mod_headers` (ex.: Laragon)

---

## Estrutura do Projeto

```text
TCC2026_mbstudio/
├── Index.php                  # Página inicial pública
├── .htaccess                  # Rotas amigáveis, headers de segurança e bloqueio de arquivos sensíveis
├── config/
│   ├── .env.example           # Modelo de variáveis de ambiente
│   ├── config.php             # Carregador de .env, constantes de DB/SMTP e logs
│   └── conexao.php            # Instância PDO
├── core/
│   ├── Database.php           # Conexão singleton PDO com tratamento de erros
│   └── Session.php            # Sessão segura, timeout, FirstLoginFilter e proteção CSRF
├── middleware/                # Filtros de autenticação por perfil (Auth, AdminAuth, FuncionarioAuth, ClienteAuth, FirstLoginFilter)
├── model/                     # Modelos de acesso a dados (User, Client, Employee, Service, Appointment, Availability, etc.)
├── controller/                # Controladores de fluxo, AJAX e ações de negócio
├── View/                      # Interfaces separadas por perfil (admin/, funcionario/, cliente/, components/)
├── helpers/
│   └── EmailHelper.php        # Serviço de e-mail transacional via PHPMailer + fila Outbox
├── database/
│   ├── Banco.sql              # Schema completo do banco tcc_agendamento
│   ├── migration_verification_codes.sql
│   └── seed_admin.php         # Seeder alternativo para criação do administrador inicial
├── assets/                    # CSS, JS e imagens
├── uploads/                   # Fotos enviadas pelos profissionais
└── logs/                      # Logs de erros e envios de e-mail
```

---

## Instalação e Configuração

### 1. Pré-requisitos
- PHP 8.0 ou superior com extensões `pdo_mysql`, `openssl`, `mbstring` e `fileinfo` habilitadas.
- MySQL ou MariaDB.
- Apache com `mod_rewrite` e `mod_headers` ativos (ex.: Laragon).

### 2. Banco de Dados
1. Importe o arquivo `database/Banco.sql` no seu MySQL/MariaDB (via phpMyAdmin, HeidiSQL ou CLI):
   ```sql
   SOURCE database/Banco.sql;
   ```
   O script cria o banco `tcc_agendamento`, todas as tabelas, a view `vw_relatorio_admin` e um usuário administrador inicial:
   - **E-mail:** `maisonbelle.com@gmail.com`
   - **Senha temporária:** `admin123` *(troca obrigatória exigida no primeiro login)*

   > Caso precise recriar o administrador via CLI, execute: `php database/seed_admin.php`

### 3. Variáveis de Ambiente (`.env`)
1. Copie `config/.env.example` para `config/.env`:
   ```bash
   cp config/.env.example config/.env
   ```
2. Gere uma chave `APP_SECRET` segura de 256 bits:
   ```bash
   php -r "echo bin2hex(random_bytes(32));"
   ```
3. Preencha o `config/.env` com os dados do banco e suas credenciais SMTP:
   ```ini
   APP_ENV=local
   APP_DEBUG=false
   APP_TIMEZONE=America/Sao_Paulo
   APP_SECRET=sua_chave_hex_de_64_caracteres

   DB_HOST=localhost
   DB_NAME=tcc_agendamento
   DB_USER=root
   DB_PASS=

   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_SECURE=tls
   SMTP_AUTH=true
   SMTP_USER=seu_email@dominio.com
   SMTP_PASS=sua_senha_de_app_smtp
   SMTP_FROM_EMAIL=seu_email@dominio.com
   SMTP_FROM_NAME="MB Studio"
   SMTP_ASYNC_QUEUE=false
   ```

### 4. Fila Assíncrona de E-mails (Opcional)
Se definir `SMTP_ASYNC_QUEUE=true` no `.env`, configure uma tarefa agendada (Cron no Linux ou Agendador de Tarefas no Windows) para processar a tabela `email_queue`:
```bash
php controller/ProcessarFilaEmail.php
```

---

## Segurança Implementada

- **Senhas:** Hash com Bcrypt (`PASSWORD_BCRYPT`) e rehash automático quando necessário.
- **2FA & Tokens Efêmeros:** Códigos de 6 dígitos gerados com `random_int`, armazenados como HMAC-SHA256 com expiração e limite de tentativas.
- **Proteção CSRF:** Validação de token CSRF e verificação estrita Same-Origin (`Origin` / `Referer`) em mutações POST.
- **Controle de Concorrência (Overbooking):** Transações MySQL com bloqueio pessimista (`SELECT ... FOR UPDATE`) e retry automático em caso de deadlock em `controller/Agendar.php`.
- **Proteção de Arquivos Sensíveis:** Regras `.htaccess` bloqueiam acesso direto a `.env`, `.sql`, `.log` e listagem de diretórios.
