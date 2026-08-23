<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['User_id']) ? 'true' : 'false';
$basePath = '/TCC2026_mbstudio'; 
?>

<style>
/* Estilos modernos e premium para o Modal */
#global-login-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

#global-login-modal.show {
    display: flex;
    opacity: 1;
}

.login-modal-content {
    background: #1a1a1a;
    padding: 40px;
    border-radius: 16px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    transform: translateY(20px);
    transition: transform 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #fff;
    font-family: 'Inter', sans-serif;
    position: relative;
    overflow: hidden;
}

#global-login-modal.show .login-modal-content {
    transform: translateY(0);
}

.login-modal-close {
    position: absolute;
    top: 15px;
    right: 20px;
    cursor: pointer;
    font-size: 24px;
    color: #aaa;
    transition: color 0.2s;
    z-index: 10;
}

.login-modal-close:hover {
    color: #fff;
}

.login-modal-header {
    text-align: center;
    margin-bottom: 30px;
}

.login-modal-header h2 {
    margin: 0;
    font-size: 28px;
    font-weight: 600;
    background: linear-gradient(90deg, #d4af37, #f3e5ab);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.login-modal-header p {
    color: #aaa;
    margin-top: 10px;
    font-size: 14px;
}

.login-form-group {
    margin-bottom: 20px;
}

.login-form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    color: #ddd;
}

.login-form-group input {
    width: 100%;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    color: #fff;
    font-size: 16px;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}

.login-form-group input:focus {
    outline: none;
    border-color: #d4af37;
    box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
}

.login-modal-btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(90deg, #d4af37, #b8860b);
    color: #000;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}

.login-modal-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(212, 175, 55, 0.4);
}

.login-modal-btn:active {
    transform: translateY(0);
}

.login-modal-register-btn {
    display: block;
    width: 100%;
    padding: 14px;
    margin-top: 15px;
    background: transparent;
    color: #d4af37;
    border: 1px solid #d4af37;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    transition: background 0.2s, color 0.2s;
    box-sizing: border-box;
    cursor: pointer;
}

.login-modal-register-btn:hover {
    background: rgba(212, 175, 55, 0.1);
    color: #f3e5ab;
}

.modal-msg {
    font-size: 14px;
    text-align: center;
    margin-bottom: 15px;
    display: none;
    padding: 10px;
    border-radius: 8px;
}
.modal-msg.error {
    color: #ff6b6b;
    background: rgba(255, 107, 107, 0.1);
    border: 1px solid rgba(255, 107, 107, 0.3);
}
.modal-msg.success {
    color: #4cd137;
    background: rgba(76, 209, 55, 0.1);
    border: 1px solid rgba(76, 209, 55, 0.3);
}

/* Transições entre forms */
.form-view {
    display: none;
    animation: fadeIn 0.3s ease;
}
.form-view.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateX(10px); }
    to { opacity: 1; transform: translateX(0); }
}
</style>

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
