# Relatório de Auditoria Técnica e Pedagógica — TCC

**Projeto:** MB Studio — Sistema de Agendamento para Salão de Beleza  
**Curso:** Técnico em Desenvolvimento de Sistemas  
**Avaliação:** Auditoria Full Stack (Arquitetura, UI/UX, Banco de Dados e Segurança da Informação)  
**Data da Auditoria:** 26/11/2026  

---

## 📊 Resumo Executivo da Avaliação

O projeto **MB Studio** demonstra um nível técnico **muito acima da média** esperada para formandos de um curso Técnico em Desenvolvimento de Sistemas. A equipe entregou um sistema completo, funcional, visualmente refinado e com preocupações reais de engenharia de software — como autenticação em duas etapas (2FA), controle de concorrência contra *overbooking* com `SELECT ... FOR UPDATE`, proteção contra *Path Traversal* em uploads, `Session::liberarLock()` antes de operações SMTP e até uma fila assíncrona de e-mails (*Outbox Pattern*).

Os apontamentos listados neste relatório não diminuem o brilho da entrega; pelo contrário, têm o objetivo de **blindar o projeto para a banca avaliadora**, eliminando alguns bugs sutis, lacunas de CSRF/XSS/IDOR e inconsistências pontuais de arquitetura antes da apresentação final.

### Quadro-Resumo por Dimensão

| Dimensão Avaliada | Status Geral | Conceito Sugerido | Principal Destaque | Principal Ponto de Atenção |
| :--- | :---: | :---: | :--- | :--- |
| **1. Arquitetura & Organização** | 🟢 Muito Bom | **9,0 / 10** | Estrutura MVC clara, `Database` Singleton e `Session` dedicada | 15 arquivos vazios (0 bytes) e SQL direto em 11 Views |
| **2. UI & Front-end** | 🟢 Excelente | **9,0 / 10** | Design System consistente (`global.css`), 34 `@media` queries e touch targets 44px | Imagens muito pesadas (até 7,3 MB) e bugs pontuais em JS |
| **3. UX & Usabilidade** | 🟡 Bom | **8,2 / 10** | Fluxo guiado em 4 etapas com *stepper* e calendário inteligente | Edição de serviço zera vínculos de funcionários; mistura de `alert()` com Flash Messages |
| **4. Banco de Dados** | 🟢 Muito Bom | **8,8 / 10** | 3FN bem aplicada, índices compostos e View gerencial | `ON DELETE CASCADE` em `appointments` e ausência de snapshot de preço (`Appo_price`) |
| **5. Segurança da Informação** | 🟡 Bom (com alertas) | **7,8 / 10** | Zero SQL Injection, Bcrypt/HMAC-SHA256 e upload validado por Magic Bytes | Reflected XSS (`Horarios.php`), IDOR (`funcionario/Agenda.php`) e CSRF opcional |

### Inventário Técnico do Workspace

| Métrica | Quantidade | Observação |
| :--- | :---: | :--- |
| Arquivos PHP (código próprio, excl. `libs/`) | 97 | Inclui `Index.php`, `config/`, `core/`, `middleware/`, `model/`, `controller/`, `View/`, `helpers/`, `database/` |
| Folhas de Estilo CSS (`assets/css/`) | 29 | Organizadas por módulo (`admin/`, `cliente/`, `funcionario/`, `components/`) |
| Scripts JavaScript (`assets/js/`) | 4 | 2 ativos (`heroSlideshow.js`, `cliente-transitions.js`) e 2 vazios (`script.js`, `validacao.js`) |
| Arquivos Vazios (0 bytes) | 15 | Esqueletos legados que devem ser removidos antes da entrega |
| Tabelas + Views no Banco (`Banco.sql`) | 11 tabelas + 1 View | Schema completo, relacional e com índices compostos |

---

## 1. Visão Geral e Arquitetura do Projeto

### 1.1 Stack Tecnológica Identificada
- **Back-end:** PHP 8+ puro com PDO (`ATTR_EMULATE_PREPARES => false`, `ERRMODE_EXCEPTION`), sessões nativas com hardening e `PHPMailer` em `libs/PHPMailer/`.
- **Banco de Dados:** MySQL / MariaDB (`tcc_agendamento`), utilizando transações InnoDB, bloqueio pessimista (`FOR UPDATE`) e View SQL (`vw_relatorio_admin`).
- **Front-end:** HTML5, CSS3 vanilla com Design System baseado em variáveis CSS (`:root`), JavaScript vanilla (ES6+, `fetch`, `View Transitions API` e integração externa com a **BrasilAPI** para feriados nacionais).
- **Servidor Web:** Apache com `.htaccess` (rotas amigáveis via `mod_rewrite`, cabeçalhos HTTP de segurança via `mod_headers` e bloqueio de arquivos sensíveis).

### 1.2 Separação de Responsabilidades (MVC)
O projeto adota uma estrutura inspirada em **MVC** muito bem organizada em diretórios: `config/`, `core/`, `middleware/`, `model/`, `controller/`, `View/`, `helpers/`, `database/`, `assets/`, `uploads/` e `logs/`.

**O que foi muito bem implementado:**
- **Separação exemplar nos módulos mais recentes:** `Index.php` delegando para `controller/HomeController.php`; `View/admin/Dashboard.php` consumindo `model/Dashboard.php`; `View/admin/Perfil.php` + `controller/PerfilController.php`; `View/PrimeiroAcesso.php` + `controller/PrimeiroAcessoController.php`.
- **Camada `core/` sólida:** `core/Database.php` implementa Singleton PDO com tratamento de falha gracioso (HTTP 503 em JSON ou texto, sem vazar credenciais) e `core/Session.php` centraliza segurança de sessão, CSRF, Flash Messages e a regra de primeiro acesso.
- **Versionamento de assets (Cache Busting):** Uso inteligente de `@filemtime(...)` em diversos arquivos (`Index.php`, `Header.php`, `MeusAgendamentos.php`, `admin/Imagens.php`) para evitar CSS/JS desatualizados no navegador.

**Pontos de melhoria na separação de responsabilidades:**
1. **Consultas SQL diretas em 11 Views:** Os arquivos abaixo executam `$pdo->query()` ou `$pdo->prepare()` diretamente dentro da camada de apresentação em vez de consumir um Model/Controller:
   - `View/admin/Agenda.php`, `View/admin/Servicos.php`
   - `View/cliente/Servicos.php`, `View/cliente/Data.php`, `View/cliente/Horarios.php`, `View/cliente/Confirmar.php`, `View/cliente/Funcionarios.php`, `View/cliente/MeusAgendamentos.php`
   - `View/funcionario/Agenda.php`, `View/funcionario/Bloqueios.php`, `View/funcionario/Disponibilidade.php`
   - **Caso mais sensível:** `View/funcionario/Agenda.php` (linhas 29–38) processa uma requisição `POST` e executa um `UPDATE clients` diretamente no topo da View.
2. **Duplicação de fluxos e regras:**
   - Autenticação duplicada entre versão AJAX (`AjaxLogin.php`, `AjaxCadastro.php`, `AjaxValidarCodigo.php`) e versão tradicional (`Login.php`, `Cadastro.php`, `ValidarCodigo.php`), aumentando o esforço de manutenção.
   - Lógica de validação e upload de imagens repetida em 4 controladores (`SalvarServico.php`, `EditarServico.php`, `ImagemController.php` e `SalvarPerfilFuncionario.php`). Extrair essa lógica para um `helpers/UploadHelper.php` deixaria o código muito mais limpo (princípio **DRY**).
3. **Bug de redirecionamento em `middleware/FuncionarioAuth.php` (linha 26):**
   - O arquivo usa `header("Location: ../Index.php");`, enquanto `AdminAuth.php` e `ClienteAuth.php` usam `Session::getBasePath() . "/Index.php"`. Quando `FuncionarioAuth.php` é incluído por `View/funcionario/Perfil.php`, o redirecionamento relativo cai em `View/Index.php` (erro **404 Not Found**).
4. **Código residual em `controller/RelatorioController.php` (linhas 54–58):**
   - A linha 54 tenta acessar `$this->relatorioModel->pdo` (que é `private` em `model/Relatorio.php`), e logo em seguida a linha 58 sobrescreve `$empModel` usando `global $pdo`. O ideal é armazenar `$this->pdo = $pdo` no construtor do controller.

### 1.3 Organização de Pastas, Nomenclatura e Código Morto
- **Arquivos vazios (0 bytes — código morto):** Existem **15 arquivos completamente vazios** espalhados no repositório que aparentam ser rascunhos iniciais não utilizados:
  - `assets/css/login.css`, `assets/js/script.js`, `assets/js/validacao.js`
  - `controller/AdicionarServicoFuncionario.php`, `controller/AtivarUsuario.php`, `controller/BuscarFuncionarios.php`, `controller/BuscarHorarios.php`, `controller/RemoverServicoFuncionario.php`
  - `core/Controller.php`, `core/Model.php`
  - `helpers/Funcoes.php`, `helpers/Validacao.php`
  - `model/EmployeeService.php`
  - `View/funcionario/Agendamentos.php`, `View/funcionario/Servicos.php`
- **Inconsistência de formatação entre membros da equipe:** Arquivos como `model/User.php`, `controller/PerfilController.php` e `helpers/EmailHelper.php` seguem excelente padrão PSR-12, enquanto `model/Employee.php` (485 linhas), `controller/SalvarPerfilFuncionario.php` (759 linhas) e `middleware/FuncionarioAuth.php` apresentam 2 a 4 linhas em branco entre quase todas as instruções, além de comparações frouxas (`!=` em vez de `!==`).
- **Sensibilidade a maiúsculas/minúsculas (Linux vs. Windows):** `Index.php` e a pasta `View/` começam com maiúscula, enquanto `model/`, `controller/`, `assets/` e `config/` são minúsculas. No Windows/Laragon isso passa despercebido, mas em produção Linux qualquer referência com caixa trocada gera erro 404.
- **Fuso horário divergente:** `config/config.php` configura `America/Sao_Paulo`, porém `View/admin/Agenda.php`, `View/funcionario/Agenda.php` e `View/funcionario/Disponibilidade.php` sobrescrevem manualmente com `date_default_timezone_set('Etc/GMT+3')`. Centralizar tudo em `config/config.php` evita divergências.

---

## 2. UI (Interface do Usuário) e Front-end

### 2.1 Semântica e Estrutura HTML5
- **Acertos:** Boa utilização de tags semânticas (`<header>`, `<nav>`, `<main>`, `<section>`, `<footer>`, `<table>`, `<thead>`, `<tbody>`) e uso criativo de `<details>`/`<summary>` para edição inline em `View/admin/Servicos.php`.
- **Ajustes recomendados:**
  1. **`Index.php` sem tag `<main>`:** O conteúdo principal está envolvido por `<div class="page-content-wrapper" id="page-content">` (linha 42). Trocar o miolo das seções por `<main>` melhora a semântica e a navegação por leitores de tela.
  2. **`<link rel="stylesheet">` dentro do `<body>`:** Como `View/components/Header.php`, `AuthButton.php` e `LoginModal.php` são incluídos dentro do `<body>`, suas tags `<link rel="stylesheet">` e `<link rel="prefetch">` são renderizadas fora do `<head>`. Além disso, `Index.php` carrega `cliente-transitions.js` e `heroSlideshow.js` **duas vezes** (via `Header.php` com `defer` e novamente ao final da `Index.php`).
  3. **Padronização de `lang`:** Alterna entre `lang="pt-br"` e `lang="pt-BR"`; padronizar para `lang="pt-BR"` em todas as telas.

### 2.2 Estilização, Responsividade e Performance Visual
- **Design System admirável (`assets/css/global.css`):** A paleta *White & Gold Luxury* está super coesa! O arquivo define tokens semânticos (`--bg-canvas`, `--text-primary`, `--gold-primary`, `--color-success/error/warning`), escala espacial de 8pt, escala explícita de `z-index` e tipografia hierárquica (`Playfair Display` + `Alex Brush` + `Inter`).
- **Responsividade madura:** Foram encontradas **34 `@media` queries** ao longo dos arquivos CSS, uso moderno de `clamp()`, CSS Grid (`repeat(auto-fit, minmax(...))`) e Flexbox. Merece elogio especial o bloco de ergonomia mobile em `global.css` (linhas 250–272), que garante `font-size: 16px` nos inputs (evitando zoom forçado no iOS Safari) e `min-height/min-width: 44px` nos botões (padrão WCAG/Apple HIG).
- **Gargalo de Performance — Imagens sem compressão:**
  - `assets/img/logob.png` pesa **1,22 MB** e é carregada em **todas as páginas** dentro do cabeçalho!
  - Na pasta `assets/img/services/`, há arquivos de **7,32 MB** (`ser_6ab5581b7bf18.png`), **2,78 MB** (`ser_c1516979195d31c5.png`) e vários PNGs acima de **1,2 MB**.
  - `assets/img/carousel/hero_6ad60c9fc2610.png` pesa **1,04 MB**.
  - *Impacto:* Em conexões 4G ou no momento da apresentação em rede escolar, a página inicial pode demorar vários segundos para renderizar. Redimensionar e converter para `.webp` (ou `.jpg` otimizado) reduzirá esses arquivos para menos de **80–150 KB** cada.
- **CSS inline / `<style>` embutido:** `View/PrimeiroAcesso.php`, `View/VerificarCodigo.php` e `controller/TesteEmail.php` contêm centenas de linhas de CSS dentro de `<style>` no HTML, enquanto o restante do sistema já separa muito bem os arquivos em `assets/css/`.

### 2.3 Componentização e Qualidade do JavaScript
A equipe criou componentes reutilizáveis (`Header.php`, `AuthButton.php`, `LoginModal.php`) e implementou até transições suaves entre páginas do cliente com a **View Transitions API** (`assets/js/cliente-transitions.js`). Porém, a auditoria revelou **3 bugs concretos no JS**:

1. **Caractere solto em `assets/js/heroSlideshow.js` (linha 43):**
   ```javascript
   if (d === nextIdx) {11
       dots[d].classList.add('active');
   ```
   O número `11` digitado acidentalmente após `{` é código morto (só não gera erro fatal por causa da inserção automática de ponto e vírgula do JS) e deve ser removido.
2. **Ordem invertida no preenchimento do e-mail em `View/components/LoginModal.php` (linhas 299–300 e 427–428):**
   ```javascript
   document.getElementById('modal-email').value = email;
   switchModalView('view-login'); // <-- chama global-login-form.reset() e APAGA o e-mail!
   ```
   Chamar `switchModalView('view-login')` **antes** de atribuir `value = email` resolve o problema (como o grupo já fez corretamente na linha 339).
3. **Scripts inline não executam após transição SPA em `assets/js/cliente-transitions.js`:**
   - Ao navegar de `Index.php` ou `Servicos.php` para `View/cliente/Data.php` ou `View/cliente/Perfil.php` usando `transitionToPage()`, o método `DOMParser().parseFromString()` + `currentContent.replaceWith(newContent)` **não executa** as tags `<script>` da nova página.
   - *Consequência prática:* Se o cliente navegar via clique suave para `Data.php`, os botões de atalho rápido (`Hoje`, `Amanhã`, `Depois de amanhã` — função `setBookingDate`) param de funcionar até um F5; e em `Perfil.php`, a máscara de telefone deixa de ser aplicada.
4. **JS extenso dentro de View PHP:** `View/funcionario/Disponibilidade.php` possui mais de **520 linhas de JavaScript inline** (linhas 330–855), que ficariam muito mais organizadas e fáceis de manter em um arquivo dedicado `assets/js/disponibilidade.js`.

---

## 3. UX (Experiência do Usuário) e Usabilidade

### 3.1 Fluxo de Navegação e Regras de Negócio na Interface
- **Pontos muito positivos:**
  - Fluxo de agendamento dividido em 4 passos claros (**Serviço → Data → Horário → Confirmação**) com barra de progresso (*stepper*), resumo permanente das escolhas e botões fáceis de voltar/trocar serviço.
  - Filtros instantâneos em tempo real (sem recarregar a página) nas telas administrativas de Usuários, Equipe, Serviços e Agenda.
  - Proteção contra cancelamento acidental (botão `Cancelar` na agenda do profissional só habilita após preencher o motivo).
- **Bugs de UX / Regra de Negócio encontrados:**
  1. **Edição de serviço desmarca todos os funcionários (`View/admin/Servicos.php`, linhas 409–415):** No formulário `<details>` de edição de serviço, os checkboxes dos profissionais **não vêm pré-marcados** com os vínculos atuais do banco. Como `Service::update()` executa `DELETE FROM employee_services WHERE Ser_id = ?` antes de reinserir apenas o que veio marcado no `POST`, editar o preço ou descrição de um serviço sem remarcar manualmente toda a equipe **apaga silenciosamente todos os profissionais daquele serviço**!
  2. **Profissionais inativos aparecem na Home (`model/Employee.php`, linha 475):** `listarDestaquesHome()` filtra apenas `WHERE u.User_perm = 'F'`, esquecendo `AND u.User_active = 1` (filtro que existe em `View/cliente/Funcionarios.php`).
  3. **Divergência no horário de funcionamento:** O rodapé da `Index.php` informa atendimento de *Terça a Sexta (09h–19h)* e *Sábados (08h30–18h)*, mas `Disponibilidade.php` e `SalvarDisponibilidade.php` liberam cadastro de `08:00` às `19:00` inclusive aos domingos.

### 3.2 Feedbacks Visuais
- **Pontos fortes:** Estados de carregamento no modal (`Entrando...`, `Cadastrando...`, `Verificando...` com `btn.disabled = true`), *empty states* bem desenhados quando não há registros, e ótimas mensagens *flash* em `View/admin/Perfil.php` e `View/PrimeiroAcesso.php`.
- **Inconsistência a unificar:** Metade dos controladores (`SalvarServico.php`, `EditarServico.php`, `SalvarPerfilCliente.php`, `SalvarPerfilFuncionario.php`, `SalvarDisponibilidade.php`, `EditarDisponibilidade.php`, `ExcluirDisponibilidade.php`) ainda exibe respostas via `<script>alert('...'); window.location='...';</script>`, quebrando a estética moderna do sistema. Como vocês já criaram `Session::setFlash()` e `Session::getFlash()`, migrar esses `alert()` nativos para banners de feedback deixará a experiência 100% profissional.

### 3.3 Acessibilidade Básica (A11y)
- **Acertos:** `:focus-visible` configurado globalmente em `global.css`, atributos `alt` presentes nas fotos de serviços e especialistas, `role="alert"`/`role="status"` nos feedbacks do admin, e menu mobile com `aria-expanded`, `aria-controls` e suporte à tecla `ESC`.
- **Lacunas encontradas:**
  - **`View/components/LoginModal.php`:** Nenhum `<label>` possui o atributo `for` vinculado ao `id` do respectivo `<input>`; o botão de fechar (`×`) é uma `<span>` em vez de `<button type="button" aria-label="Fechar">` (inacessível via teclado); e o modal não declara `role="dialog" aria-modal="true"`.
  - **`View/admin/Servicos.php` e `View/admin/Imagens.php`:** Diversos `<label>` sem atributo `for` e campos sem `id`.
  - **`View/funcionario/Disponibilidade.php`:** Linha 175 usa `<label for="Dis_scheduled-times">` apontando para uma `<div>` (inválido para acessibilidade), e os botões de navegar meses (`‹` e `›`) no calendário não possuem `aria-label`.
  - **Truncamento UTF-8 em iniciais:** `Index.php` (linha 244), `Header.php` (linha 16) e `AuthButton.php` (linha 17) usam `substr($nome, 0, 1)` em vez de `mb_substr($nome, 0, 1, 'UTF-8')`. Nomes que começam com vogal acentuada (ex.: *Ágatha*, *Érica*) exibem um caractere corrompido (``) no avatar.
  - **Links externos em `Index.php` (linhas 368 e 377):** Instagram e WhatsApp usam `target="_blank"` sem `rel="noopener noreferrer"` e sem `aria-label`.

---

## 4. Banco de Dados e Persistência

### 4.1 Modelagem Relacional (`database/Banco.sql` + `migration_verification_codes.sql`)
A modelagem é um dos grandes pilares positivos do TCC:
- **Normalização (3FN):** Separação limpa entre `users` (credenciais e perfil RBAC) e as especializações `clients` e `employees` (relação 1:1 com `UNIQUE NOT NULL` em `User_id`), tabela associativa N:N `employee_services` com `UNIQUE(Emp_id, Ser_id)`, além de tabelas especializadas (`availabilities`, `employee_blocks`, `appointments`, `verification_codes`, `site_images`, `email_queue`).
- **Índices compostos de nível profissional:** A criação de `idx_appo_emp_date_status`, `idx_ava_lookup`, `idx_block_emp_date` e `idx_verification_latest` demonstra compreensão real de como evitar *table locks* em consultas transacionais com `FOR UPDATE`.
- **View gerencial (`vw_relatorio_admin`):** Facilita e padroniza os relatórios administrativos.
- **Seeder idempotente:** O `INSERT ... SELECT ... WHERE NOT EXISTS` em `Banco.sql` e o `database/seed_admin.php` evitam duplicação do administrador ao reimportar o script.

**Pontos importantes de modelagem para revisar/defender na banca:**
1. **Risco de perda de histórico com `ON DELETE CASCADE` em `appointments`:**
   - Na tabela `appointments` (linhas 139–141 de `Banco.sql`), `Cli_id`, `Emp_id` e `Ser_id` estão com `ON DELETE CASCADE`.
   - Somado a isso, `Service::destroy()` (linha 149) só verifica se há agendamentos com status `'Pendente'`, ignorando `'Confirmado'`, `'Em Atendimento'` e `'Concluido'`. Se o administrador excluir um serviço ou usuário antigo, **todo o histórico de atendimentos e faturamento associado é apagado em cascata**.
   - *Recomendação:* Usar `ON DELETE RESTRICT` nas FKs de `appointments` e priorizar a inativação lógica (`Ser_active = 0` / `User_active = 0`).
2. **Preço não congelado no agendamento (Snapshot financeiro):**
   - `vw_relatorio_admin` lê `s.Ser_price` diretamente da tabela `services`. Se um serviço custava R$ 80,00 em outubro e sobe para R$ 100,00 em novembro, o relatório de outubro passa a exibir R$ 100,00 retroativamente!
   - *Recomendação:* Adicionar a coluna `Appo_price DECIMAL(10,2) NOT NULL` em `appointments`, gravando o valor vigente no momento da criação do agendamento.
3. **Consistência de Engine/Charset:** As tabelas 1 a 10 de `Banco.sql` não especificam `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`, enquanto as tabelas 11 e 12 especificam. Padronizar todas garante comportamento uniforme em qualquer instalação MySQL/MariaDB.
4. **DDL em tempo de execução nos Models:** `Availability::verificarColunaSerId()` (`ALTER TABLE`) e `Imagem::garantirTabela()` (`CREATE TABLE`) executam comandos estruturais em runtime. Mesmo protegidos por cache estático, a boa prática é manter toda criação/alteração de tabelas exclusivamente nos scripts em `database/`.

### 4.2 Qualidade das Consultas (Queries)
- **Destaques:**
  - `Dashboard::obterResumoGeral()` consolida 6 contagens distintas em **1 única query agregada**, economizando 5 idas ao banco por carregamento.
  - `Agendar.php` implementa transação curta com `SELECT ... FOR UPDATE` e *retry* automático com *jitter* em caso de deadlock (`SQLSTATE 40001` / erro `1213` ou `1205`).
- **Bugs/ajustes pontuais:**
  - **`RelatorioController.php` (linhas 45–50):** O cálculo de `$faturamentoTotal` percorre o array `$dados` em PHP, mas `Relatorio::getDadosRelatorio()` limita o retorno a `LIMIT 500`. Se houver mais de 500 atendimentos no período filtrado, o faturamento exibido será menor que o real. O cálculo deve vir de uma query `SELECT COALESCE(SUM(Valor_Servico), 0)` no Model.
  - **`Availability::excluirPorId()` (linha 150):** Caso `$avaId` seja um array vazio `[]`, `str_repeat('?,', count($avaId) - 1)` passa `-1` para `str_repeat()`, lançando `ValueError` no PHP 8. Basta validar `if (empty($avaId)) return false;` antes.

---

## 5. Segurança da Informação (Foco Web/Aplicação)

### 5.1 Injeção de SQL (SQL Injection) — ✅ Aprovado com Louvor
Em toda a varredura de 97 arquivos PHP próprios, **não foi encontrada nenhuma vulnerabilidade de SQL Injection**. Todas as consultas com entrada do usuário utilizam **Prepared Statements** via PDO com `PDO::ATTR_EMULATE_PREPARES => false` e vinculação segura de parâmetros (`?` ou `:param`).

### 5.2 Validação, Sanitização e Prevenção contra XSS
- **O que está bem feito:** Quase todas as Views e inclusive os templates HTML de e-mail (`helpers/EmailHelper.php`) aplicam `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- **🔴 Vulnerabilidade Encontrada — Reflected XSS em `View/cliente/Horarios.php` (linha 352):**
  ```php
  <a href="Confirmar.php?Ser_id=<?= $ser_id ?>&data=<?= $data ?>&Emp_id=<?= $func['Emp_id'] ?>&hora=<?= $h ?>" ...>
  ```
  `$ser_id` e `$data` vêm diretamente de `$_GET` sem validação de formato/tipo e sem `htmlspecialchars()` / `urlencode()`. Em MySQL não-estrito, um payload como `Horarios.php?Ser_id=1" onmouseover="alert(1)&data=...` consegue injetar atributos HTML no link.
- **Validações de back-end ausentes em formulários:**
  - `controller/AjaxCadastro.php` e `controller/Cadastro.php` não validam tamanho mínimo da senha (aceitam senhas de 1 caractere, enquanto a redefinição exige 6 e o primeiro acesso exige 8).
  - `controller/SalvarServico.php` e `controller/EditarServico.php` não validam se `$name` está vazio ou se `$price <= 0` / `$duration <= 0`.
  - `controller/SalvarBloqueio.php` não valida formato da data nem impede bloqueios em datas passadas no back-end (depende apenas do atributo `min` do HTML).

### 5.3 Proteção CSRF — 🔴 Falha Estrutural Importante
O método `Session::validarCsrf()` em `core/Session.php` (linhas 178–219) possui duas fragilidades combinadas:
1. **Token opcional:** Se `$_POST['csrf_token']` e o header `X-CSRF-Token` **não forem enviados**, a função não rejeita a requisição — ela apenas verifica `Origin`/`Referer`, e **retorna `true` caso ambos venham vazios** (o que pode ser forçado por um site malicioso usando `<meta name="referrer" content="no-referrer">`).
2. **Mais de 12 formulários POST não enviam `Session::csrfField()`**, incluindo:
   - `View/cliente/Confirmar.php`, `MeusAgendamentos.php`, `Perfil.php`
   - `View/admin/Agenda.php`, `Funcionarios.php`, `Imagens.php`, `Servicos.php` (criar/editar)
   - `View/funcionario/Agenda.php`, `Bloqueios.php` (criar), `Disponibilidade.php`, `Perfil.php`
   - `View/VerificarCodigo.php` e todas as chamadas `fetch()` de `LoginModal.php`.
3. **Ação com efeito colateral via `GET`:** `controller/TesteEmail.php` dispara 3 e-mails SMTP usando `<form method="GET">` (`?disparar=1&email=...`) sem CSRF.

### 5.4 Controle de Acesso (RBAC / IDOR)
- **Acertos:** Separação clara de permissões (`A`, `F`, `C`), validação de propriedade (`Emp_id` / `Cli_id`) em `ConfirmarAgendamento.php`, `IniciarAtendimento.php`, `FinalizarAgendamento.php`, `CancelarAgendamento.php` e `ExcluirBloqueio.php`, além da proteção contra exclusão/rebaixamento do último admin (`User::isLastAdmin()`).
- **🔴 Vulnerabilidade Encontrada — IDOR + Ausência de CSRF em `View/funcionario/Agenda.php` (linhas 29–38):**
  ```php
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_client_obs') {
      $cliId = $_POST['cli_id'] ?? 0;
      $observation = $_POST['cli_observation'] ?? '';
      if ($cliId) {
          $stmtObs = $pdo->prepare("UPDATE clients SET Cli_observation = ? WHERE Cli_id = ?");
          $stmtObs->execute([$observation, $cliId]);
      }
  ```
  Qualquer funcionário autenticado pode alterar `cli_id` no POST e sobrescrever `Cli_observation` de **qualquer cliente do sistema**, mesmo sem nunca tê-lo atendido, e sem verificação de CSRF.

### 5.5 Autenticação, Senhas e Upload de Arquivos
- **Autenticação & Criptografia — ⭐ Nível Excelente:**
  - Senhas com `password_hash` (`PASSWORD_BCRYPT` cost 11 / suporte opcional a `Argon2id`) + `password_needs_rehash()` transparente no login.
  - Códigos 2FA e de ativação gerados com `random_int(100000, 999999)`, armazenados com **Salted HMAC-SHA256 + Context Binding**, validados em tempo constante (`hash_equals`), expiração de 15 min e limite de 5 tentativas sob `FOR UPDATE`.
  - Troca obrigatória de senha no primeiro acesso (`FirstLoginFilter.php`) e cookies de sessão com `HttpOnly`, `SameSite=Lax`, `use_strict_mode=1` e `session_regenerate_id(true)`.
  - *Pequenas melhorias:* Implementar *rate limiting* básico contra força bruta na senha em `AjaxLogin.php`; e em `AjaxRedefinirSenha.php` (linha 35), trocar `'Usuário não encontrado.'` por `'E-mail ou código inválido.'` para não desfazer a proteção contra enumeração de usuários que vocês fizeram tão bem em `AjaxEsqueciSenha.php`.
- **Upload de Arquivos — ⭐ Nível Excelente:**
  - Validação em 4 camadas (tamanho, extensão em lista branca, MIME real via `finfo`, verificação estrutural via `getimagesize()`), renomeação aleatória com `random_bytes()`, `.htaccess` bloqueando execução PHP em `uploads/` e `assets/img/`, e remoção segura de foto antiga com `realpath()` em `SalvarPerfilFuncionario.php`.

### 5.6 Exposição de Dados, Logs e Segredos
- **Acertos:** Credenciais em `.env` (fora do Git, com `.env.example` documentado), `.htaccess` protegendo `.env`, `.sql`, `.log` e pastas internas (`config/`, `database/`, `logs/`), e erros de banco capturados sem exibir stack trace ao usuário.
- **Pontos de atenção:**
  1. **Logs rastreados no Git (`logs/emails_enviados.log`, `logs/erros.log`, `logs/erros_email.log`):** Embora `.gitignore` contenha `/logs/*.log`, esses 3 arquivos já haviam sido commitados anteriormente e continuam versionados. O arquivo `logs/emails_enviados.log` contém logs de depuração SMTP (`PHPMailer Debug L2`) que expõem o nome da máquina (`LAPTOP-NPKSMING`) e um endereço IPv6 público real. Remova-os do índice do Git com `git rm --cached logs/*.log`.
  2. **Fallback previsível para `APP_SECRET` (`config/config.php` linha 56 e `model/User.php` linha 86):** Se `APP_SECRET` não estiver definida no `.env`, o código gera um segredo determinístico a partir de `__DIR__` (`hash('sha256', __DIR__ . '|mbstudio_appsec_hmac_key_v2')`), que é previsível em instalações padrão do Laragon (`C:\laragon\www\TCC2026_mbstudio\config`). Em produção, o ideal é registrar erro crítico ou falhar se `APP_SECRET` estiver vazia.

---

## 6. Parecer Pedagógico e Feedback Final (Mentoria)

### 🌟 Pontos Fortes — Onde a Equipe Mandou Muito Bem!

Parabéns, equipe! É nítido o cuidado, a pesquisa e o amadurecimento técnico aplicados no **MB Studio**. Destaques que merecem ser citados com orgulho na apresentação para a banca:

1. **Criptografia e Autenticação de Nível Profissional (`model/User.php` e `core/Session.php`):**
   - Pouquíssimos projetos de nível técnico implementam **2FA completo por e-mail**, **Salted HMAC-SHA256 com Context Binding** para códigos OTP (`User::gerarHashCodigo`), **rehash transparente de senhas** (`User::precisaRehash`) e **troca obrigatória de senha no primeiro login** (`FirstLoginFilter.php`). Isso mostra domínio real de segurança aplicada!
2. **Controle de Concorrência contra Overbooking (`controller/Agendar.php`):**
   - O uso de transações curtas com `SELECT ... FOR UPDATE`, liberação de lock de sessão (`Session::liberarLock()`) antes do banco/SMTP e *retry* automático em caso de deadlock (códigos `1213`/`1205`) é um diferencial impressionante que normalmente só se vê em sistemas em produção.
3. **Upload de Arquivos Blindado (`controller/SalvarPerfilFuncionario.php` e `uploads/.htaccess`):**
   - Vocês não confiaram na extensão do arquivo: validaram tamanho, *Magic Bytes* com `finfo`, estrutura com `getimagesize()`, geraram nomes aleatórios criptográficos, bloquearam execução de PHP via `.htaccess` e ainda protegeram a exclusão da foto antiga contra *Path Traversal* usando `realpath()`. Excelente!
4. **Identidade Visual, Design System e Ergonomia Mobile (`assets/css/global.css` e `View/components/Header.php`):**
   - O sistema tem cara de produto real! A paleta branca/dourada é sofisticada, os cards de KPI e *empty states* são acolhedores, e o cuidado com `min-height: 44px` e `font-size: 16px` no mobile demonstra atenção genuína ao usuário final.
5. **Performance e Resiliência (`model/Dashboard.php` e `helpers/EmailHelper.php`):**
   - Consolidar 6 queries do Dashboard em 1 consulta agregada e implementar *SMTPKeepAlive*, semáforo de envio e *Outbox Pattern* (`email_queue`) mostra visão sistêmica muito madura.

---

### 🚀 Oportunidades de Melhoria (Plano de Ação / Quick Wins antes da Banca)

Organizamos os ajustes por prioridade (**do mais crítico ao mais estético**) para que vocês consigam aplicar rapidamente antes da defesa do TCC:

#### 1. [CRÍTICO — Segurança] Corrigir Reflected XSS em `View/cliente/Horarios.php`
**Por quê?** `$ser_id` e `$data` vêm da URL (`$_GET`) e são impressos sem validação nem escape na linha 352.

```php
// ❌ ANTES (View/cliente/Horarios.php - linhas 13-19 e 352)
$ser_id = $_GET['Ser_id'] ?? null;
$data = $_GET['data'] ?? null;
// ...
<a href="Confirmar.php?Ser_id=<?= $ser_id ?>&data=<?= $data ?>&Emp_id=<?= $func['Emp_id'] ?>&hora=<?= $h ?>">

// ✅ DEPOIS (validar tipo/formato no topo + escapar na saída)
$ser_id = filter_input(INPUT_GET, 'Ser_id', FILTER_VALIDATE_INT);
$dataRaw = trim($_GET['data'] ?? '');
$dtObj = DateTime::createFromFormat('Y-m-d', $dataRaw);
$data = ($dtObj && $dtObj->format('Y-m-d') === $dataRaw) ? $dataRaw : null;

if (!$ser_id || !$data) {
    header("Location: Servicos.php");
    exit;
}
// ...
<a href="Confirmar.php?Ser_id=<?= (int)$ser_id ?>&data=<?= urlencode($data) ?>&Emp_id=<?= (int)$func['Emp_id'] ?>&hora=<?= urlencode($h) ?>">
```

#### 2. [CRÍTICO — Segurança & MVC] Corrigir IDOR e mover `POST` de `View/funcionario/Agenda.php` para um Controller
**Por quê?** Atualmente a View executa `UPDATE clients` sem validar CSRF e sem checar se o cliente pertence a algum agendamento daquele profissional.

```php
// ✅ Criar controller/SalvarObservacaoCliente.php (com CSRF + checagem de vínculo)
Session::iniciar();
if (($_SESSION['User_perm'] ?? '') !== 'F') {
    header("Location: ../Index.php");
    exit;
}
Session::validarCsrf();

$cliId = filter_input(INPUT_POST, 'cli_id', FILTER_VALIDATE_INT);
$obs   = trim($_POST['cli_observation'] ?? '');

// Garante que o funcionário realmente possui vínculo de agendamento com este cliente
$stmtCheck = $pdo->prepare("
    SELECT 1 FROM appointments a
    INNER JOIN employees e ON a.Emp_id = e.Emp_id
    WHERE a.Cli_id = ? AND e.User_id = ?
    LIMIT 1
");
$stmtCheck->execute([$cliId, (int)$_SESSION['User_id']]);

if ($cliId && $stmtCheck->fetchColumn()) {
    $stmtUpd = $pdo->prepare("UPDATE clients SET Cli_observation = ? WHERE Cli_id = ?");
    $stmtUpd->execute([$obs, $cliId]);
}
header("Location: ../View/funcionario/Agenda.php");
exit;
```

#### 3. [CRÍTICO — Segurança] Tornar o token CSRF obrigatório em `core/Session.php` e incluí-lo em todos os `<form method="POST">`
**Por quê?** Se um formulário omitir `csrf_token`, `validarCsrf()` atualmente permite a requisição caso `Origin`/`Referer` estejam ausentes.

```php
// ✅ 1. Em todos os formulários POST das Views, adicione:
<?= Session::csrfField() ?>

// ✅ 2. Em core/Session.php -> validarCsrf(): exigir token obrigatório em mutações POST
public static function validarCsrf($token = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $tokenRecebido = $token ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($tokenRecebido === '' || !hash_equals($_SESSION['csrf_token'] ?? '', (string)$tokenRecebido)) {
        http_response_code(403);
        die("Ação bloqueada por segurança: Token CSRF ausente ou inválido.");
    }
    return true;
}
```
*(Lembre-se também de incluir o header `'X-CSRF-Token': '<?= Session::gerarTokenCsrf() ?>'` nas requisições `fetch()` de `View/components/LoginModal.php` e chamar `Session::validarCsrf()` nos controllers `Ajax*`.)*

#### 4. [ALTO — Bug Funcional/UX] Pré-marcar funcionários vinculados na edição de serviço (`View/admin/Servicos.php`)
**Por quê?** Evita que o administrador apague sem querer todos os profissionais de um serviço ao editar apenas o preço ou nome.

```php
// ✅ Em model/Service.php -> index(), adicione também os IDs dos usuários vinculados:
GROUP_CONCAT(u.User_id SEPARATOR ',') AS EmployeeUserIds

// ✅ Em View/admin/Servicos.php (linha 409):
<?php $vinculados = array_filter(explode(',', $s['EmployeeUserIds'] ?? '')); ?>
<?php foreach ($funcionarios as $func): ?>
    <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; margin-bottom: 4px; cursor: pointer;">
        <input type="checkbox" name="funcionarios[]" value="<?= (int)$func['User_id'] ?>"
            <?= in_array((string)$func['User_id'], $vinculados, true) ? 'checked' : '' ?>>
        <span><?= htmlspecialchars($func['User_name']) ?></span>
    </label>
<?php endforeach; ?>
```

#### 5. [ALTO — Bugs de Rota e Filtro] Corrigir `FuncionarioAuth.php` e `Employee::listarDestaquesHome()`
- Em `middleware/FuncionarioAuth.php` (linha 26), troque `header("Location: ../Index.php");` por `header("Location: " . Session::getBasePath() . "/Index.php");` e use `!==`.
- Em `model/Employee.php` (linha 475), altere `WHERE u.User_perm = 'F'` para `WHERE u.User_perm = 'F' AND u.User_active = 1`.

#### 6. [MÉDIO — Segurança & Higiene] Limpar logs do Git, validar senha mínima no cadastro e proteger `TesteEmail.php`
1. Execute no terminal para parar de versionar logs sem apagá-los localmente:
   ```bash
   git rm --cached logs/emails_enviados.log logs/erros.log logs/erros_email.log
   ```
2. Em `controller/AjaxCadastro.php` e `controller/Cadastro.php`, adicione validação de tamanho mínimo da senha (`if (mb_strlen($senha) < 6)`).
3. Em `controller/TesteEmail.php`, altere o formulário de `method="GET"` para `method="POST"` com `Session::csrfField()` e `Session::validarCsrf()`.

#### 7. [MÉDIO — Banco de Dados] Proteger histórico financeiro e corrigir soma do relatório
- Em `database/Banco.sql`, considere adicionar `Appo_price DECIMAL(10,2) NOT NULL` em `appointments` e trocar `ON DELETE CASCADE` por `ON DELETE RESTRICT` nas chaves estrangeiras de `appointments`.
- Em `model/Service.php -> destroy()`, valide `Appo_status IN ('Pendente', 'Confirmado', 'Em Atendimento', 'Concluido')` (ou recomende inativar o serviço em vez de excluir).
- Em `model/Relatorio.php`, crie um método `getFaturamentoTotal($dataInicial, $dataFinal, $empId)` usando `SELECT COALESCE(SUM(Valor_Servico), 0)` para não depender do `LIMIT 500`.

#### 8. [MÉDIO — Front-end/JS] Corrigir 3 bugs sutis de JavaScript
- Remover o `11` solto na linha 43 de `assets/js/heroSlideshow.js`.
- Em `View/components/LoginModal.php` (linhas 299–300 e 427–428), chamar `switchModalView('view-login')` **antes** de definir `document.getElementById('modal-email').value = email;`.
- Em `assets/js/cliente-transitions.js`, limitar `isClientUrl()` apenas às páginas institucionais estáticas (`Index.php`, `Servicos.php`, `Funcionarios.php`), deixando telas com formulários/scripts inline (`Data.php`, `Perfil.php`, `Confirmar.php`) navegarem normalmente — ou reinicializar seus scripts após a troca de DOM.

#### 9. [ESTÉTICO — Performance, Acessibilidade & Limpeza] Polimento final para brilhar na banca
- **Otimize as imagens:** Comprima `assets/img/logob.png` (de 1,22 MB para ~30 KB) e as fotos em `assets/img/services/` e `carousel/` (especialmente o PNG de 7,32 MB) usando WebP ou JPEG qualidade 80%.
- **Remova os 15 arquivos de 0 bytes** listados na Seção 1.3.
- **Melhore a acessibilidade do `LoginModal.php`:** adicione `for="..."` em todos os `<label>`, troque `<span class="login-modal-close">` por `<button type="button" aria-label="Fechar modal">` e inclua `role="dialog" aria-modal="true"`.
- **Troque `substr` por `mb_substr(..., 0, 1, 'UTF-8')`** ao gerar iniciais de avatar em `Index.php`, `Header.php` e `AuthButton.php`.
- **Substitua `alert()` por `Session::setFlash()`** nos controllers que ainda usam `<script>alert(...)</script>`.

---

### 💬 Mensagem de Encerramento

Queridos formandos,

Chegar até aqui com um projeto da qualidade do **MB Studio** é motivo de muito orgulho! Vocês não se contentaram com um "CRUD básico de sala de aula": vocês enfrentaram desafios reais da engenharia web — autenticação 2FA, proteção contra *overbooking*, upload seguro de imagens, envio transacional de e-mails, responsividade cuidada nos mínimos detalhes e uma interface que qualquer salão de beleza teria prazer em usar no dia a dia.

Cada ponto de melhoria listado neste relatório é exatamente o tipo de conversa que acontece em *Code Reviews* reais entre desenvolvedores juniores, plenos e seniores nas melhores empresas de tecnologia. Encarem esses ajustes como o polimento final de uma joia que já está muito bem lapidada.

Vocês estão concluindo o curso Técnico em Desenvolvimento de Sistemas com fundamentos sólidos, senso estético e consciência de segurança. Levem essa curiosidade, essa atenção aos detalhes e esse espírito de equipe para a banca e para o mercado de trabalho — **o futuro de vocês na área de tecnologia é brilhante!**

**Parabéns pela conquista e sucesso total na apresentação do TCC! 🎓✨**
