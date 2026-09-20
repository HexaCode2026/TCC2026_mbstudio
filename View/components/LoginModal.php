<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['User_id']) ? 'true' : 'false';
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/../../'));
$basePath = str_ireplace($docRoot, '', $projectRoot);
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/components/loginmodal.css">

<div id="global-login-modal">
    <div class="login-modal-content">
        <span class="login-modal-close" onclick="closeLoginModal()">&times;</span>
        
        <!-- VISÃO DE LOGIN -->
        <div id="view-login" class="form-view active">
            <div class="login-modal-header">
                <h2>Bem-vindo(a)</h2>
                <p>Faça login para continuar.</p>
            </div>
            
            <div id="login-modal-msg" class="modal-msg"></div>

            <form id="global-login-form" onsubmit="handleAjaxLogin(event)">
                <div class="login-form-group">
                    <label>Email</label>
                    <input type="email" id="modal-email" required placeholder="seu@email.com">
                </div>
                <div class="login-form-group">
                    <label>Senha</label>
                    <input type="password" id="modal-senha" required placeholder="••••••••">
                </div>
                <button type="submit" class="login-modal-btn">Entrar</button>
                <button type="button" class="login-modal-register-btn" onclick="switchModalView('view-cadastro')">Não tem conta? Cadastre-se</button>
            </form>
        </div>

        <!-- VISÃO DE CADASTRO -->
        <div id="view-cadastro" class="form-view">
            <div class="login-modal-header">
                <h2>Criar Conta</h2>
                <p>Preencha os dados para se cadastrar.</p>
            </div>
            
            <div id="cadastro-modal-msg" class="modal-msg"></div>

            <form id="global-cadastro-form" onsubmit="handleAjaxCadastro(event)">
                <div class="login-form-group">
                    <label>Nome Completo</label>
                    <input type="text" id="cad-nome" required placeholder="Seu Nome">
                </div>
                <div class="login-form-group">
                    <label>Email</label>
                    <input type="email" id="cad-email" required placeholder="seu@email.com">
                </div>
                <div class="login-form-group">
                    <label>Senha</label>
                    <input type="password" id="cad-senha" required placeholder="••••••••">
                </div>
                <button type="submit" class="login-modal-btn">Cadastrar</button>
                <button type="button" class="login-modal-register-btn" onclick="switchModalView('view-login')">Já tem conta? Faça Login</button>
            </form>
        </div>

    </div>
</div>

<script>
window.isLoggedIn = <?= $isLoggedIn ?>;
window.pendingAuthActionUrl = null;

function checkAuthAndExecute(event, url) {
    if(event) event.preventDefault();
    
    if (window.isLoggedIn) {
        window.location.href = url;
    } else {
        window.pendingAuthActionUrl = url;
        const modal = document.getElementById('global-login-modal');
        switchModalView('view-login'); // sempre abre no login primeiro
        modal.classList.add('show');
    }
}

function closeLoginModal() {
    const modal = document.getElementById('global-login-modal');
    modal.classList.remove('show');
    document.getElementById('login-modal-msg').style.display = 'none';
    document.getElementById('cadastro-modal-msg').style.display = 'none';
    document.getElementById('global-login-form').reset();
    document.getElementById('global-cadastro-form').reset();
}

function switchModalView(viewId) {
    document.querySelectorAll('.form-view').forEach(v => v.classList.remove('active'));
    document.getElementById(viewId).classList.add('active');
    // Limpar mensagens e formulários ao trocar de aba
    document.getElementById('login-modal-msg').style.display = 'none';
    document.getElementById('cadastro-modal-msg').style.display = 'none';
    document.getElementById('global-login-form').reset();
    document.getElementById('global-cadastro-form').reset();
}

function showModalMessage(containerId, isError, text) {
    const el = document.getElementById(containerId);
    el.textContent = text;
    el.className = 'modal-msg ' + (isError ? 'error' : 'success');
    el.style.display = 'block';
}

async function handleAjaxLogin(event) {
    event.preventDefault();
    
    const email = document.getElementById('modal-email').value;
    const senha = document.getElementById('modal-senha').value;
    const btn = event.target.querySelector('button[type="submit"]');
    
    btn.textContent = 'Entrando...';
    btn.disabled = true;
    document.getElementById('login-modal-msg').style.display = 'none';

    try {
        const basePath = "<?= $basePath ?>";
        const response = await fetch(`${basePath}/controller/AjaxLogin.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, senha })
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.isLoggedIn = true;
            if (data.perm === 'A') {
                window.location.href = `${basePath}/View/admin/Home.php`;
            } else if (data.perm === 'F') {
                window.location.href = `${basePath}/View/funcionario/Home.php`;
            } else if (window.pendingAuthActionUrl) {
                window.location.href = window.pendingAuthActionUrl;
            } else {
                closeLoginModal();
                window.location.reload(); 
            }
        } else {
            showModalMessage('login-modal-msg', true, data.message || 'Erro ao realizar login.');
        }
    } catch (err) {
        showModalMessage('login-modal-msg', true, 'Erro de comunicação com o servidor.');
    } finally {
        btn.textContent = 'Entrar';
        btn.disabled = false;
    }
}

async function handleAjaxCadastro(event) {
    event.preventDefault();
    
    const nome = document.getElementById('cad-nome').value;
    const email = document.getElementById('cad-email').value;
    const senha = document.getElementById('cad-senha').value;
    const btn = event.target.querySelector('button[type="submit"]');
    
    btn.textContent = 'Cadastrando...';
    btn.disabled = true;
    document.getElementById('cadastro-modal-msg').style.display = 'none';

    try {
        const basePath = "<?= $basePath ?>";
        const response = await fetch(`${basePath}/controller/AjaxCadastro.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome, email, senha })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showModalMessage('cadastro-modal-msg', false, data.message);
            // Muda para tela de login após 2 segundos de sucesso
            setTimeout(() => {
                // Preenche o email para facilitar
                document.getElementById('modal-email').value = email;
                switchModalView('view-login');
            }, 2000);
        } else {
            showModalMessage('cadastro-modal-msg', true, data.message || 'Erro ao realizar cadastro.');
        }
    } catch (err) {
        showModalMessage('cadastro-modal-msg', true, 'Erro de comunicação com o servidor.');
    } finally {
        btn.textContent = 'Cadastrar';
        btn.disabled = false;
    }
}
</script>
