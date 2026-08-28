<?php

require_once __DIR__ . "/../middleware/ClienteAuth.php";
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../model/User.php";
require_once __DIR__ . "/../model/Client.php";

// Aceita apenas requisições POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../View/cliente/Perfil.php");
    exit;
}

$userId = $_SESSION['User_id'];

$clientModel = new Client($pdo);
$userModel = new User($pdo);

// ======================================================
// VERIFICAR SE O USUÁRIO EXISTE
// ======================================================

$dadosAtuais = $clientModel->buscarPorUserId($userId);

if (!$dadosAtuais) {
    echo "<script>
        alert('Erro: Usuário não encontrado no sistema.');
        window.location='../View/cliente/Perfil.php';
    </script>";
    exit;
}

// ======================================================
// RECEBER DADOS
// ======================================================

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$nascimento = trim($_POST['nascimento'] ?? '');

$senha_atual = $_POST['senha_atual'] ?? '';
$nova_senha = $_POST['nova_senha'] ?? '';
$confirmar_senha = $_POST['confirmar_senha'] ?? '';

// ======================================================
// VALIDAR NOME
// ======================================================

if (empty($nome)) {
    echo "<script>
        alert('O campo Nome é obrigatório.');
        window.location='../View/cliente/Perfil.php';
    </script>";
    exit;
}

if (mb_strlen($nome) > 100) {
    echo "<script>
        alert('O nome não pode ultrapassar 100 caracteres.');
        window.location='../View/cliente/Perfil.php';
    </script>";
    exit;
}

// ======================================================
// VALIDAR EMAIL
// ======================================================

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>
        alert('Por favor, informe um email válido.');
        window.location='../View/cliente/Perfil.php';
    </script>";
    exit;
}

if (strlen($email) > 150) {
    echo "<script>
        alert('O email não pode ultrapassar 150 caracteres.');
        window.location='../View/cliente/Perfil.php';
    </script>";
    exit;
}

// Verificar email duplicado somente se o email foi alterado
if ($email !== $dadosAtuais['User_email']) {

    if ($userModel->emailExiste($email)) {
        echo "<script>
            alert('Este email já está sendo usado por outro usuário.');
            window.location='../View/cliente/Perfil.php';
        </script>";
        exit;
    }
}

// ======================================================
// VALIDAR TELEFONE
// ======================================================

if (!empty($telefone)) {

    if (strlen($telefone) > 20) {
        echo "<script>
            alert('Telefone excede o limite de caracteres.');
            window.location='../View/cliente/Perfil.php';
        </script>";
        exit;
    }

    // Permite números, espaços, parênteses e hífen
    if (!preg_match('/^[\d\s\(\)\-]+$/', $telefone)) {
        echo "<script>
            alert('Telefone contém caracteres inválidos.');
            window.location='../View/cliente/Perfil.php';
        </script>";
        exit;
    }

    // Verifica quantidade real de números
    $apenasDigitos = preg_replace('/\D/', '', $telefone);

    if (strlen($apenasDigitos) < 10 || strlen($apenasDigitos) > 11) {
        echo "<script>
            alert('O telefone deve ter 10 ou 11 dígitos.');
            window.location='../View/cliente/Perfil.php';
        </script>";
        exit;
    }
}

// ======================================================
// VALIDAR DATA DE NASCIMENTO
// ======================================================

// Data obrigatória
if (empty($nascimento)) {
    echo "<script>
        alert('A data de nascimento é obrigatória.');
        window.location='../View/cliente/Perfil.php';
    </script>";
    exit;
}

// Validar formato Y-m-d
$dt = DateTime::createFromFormat('Y-m-d', $nascimento);

if (!$dt || $dt->format('Y-m-d') !== $nascimento) {
    echo "<script>
        alert('Data de nascimento inválida.');
        window.location='../View/cliente/Perfil.php';
    </script>";
    exit;
}

// Não permitir data futura
$hoje = new DateTime();

if ($dt > $hoje) {
    echo "<script>
        alert('A data de nascimento não pode estar no futuro.');
        window.location='../View/cliente/Perfil.php';
    </script>";
    exit;
}

// ======================================================
// IDADE MÍNIMA: 16 ANOS
// ======================================================

$idade = $hoje->diff($dt)->y;

if ($idade < 16) {
    echo "<script>
        alert('É necessário ter pelo menos 16 anos.');
        window.location='../View/cliente/Perfil.php';
    </script>";
    exit;
}

// ======================================================
// ALTERAÇÃO DE SENHA
// ======================================================

$senhaHashSalvar = null;

// Só altera a senha se uma nova senha for informada
if (!empty($nova_senha)) {

    // Exigir senha atual
    if (empty($senha_atual)) {
        echo "<script>
            alert('Para alterar a senha, você precisa informar a Senha Atual.');
            window.location='../View/cliente/Perfil.php';
        </script>";
        exit;
    }

    // Nova senha deve possuir pelo menos 6 caracteres
    if (strlen($nova_senha) < 6) {
        echo "<script>
            alert('A nova senha deve ter no mínimo 6 caracteres.');
            window.location='../View/cliente/Perfil.php';
        </script>";
        exit;
    }

    // Verificar confirmação
    if ($nova_senha !== $confirmar_senha) {
        echo "<script>
            alert('As senhas não coincidem!');
            window.location='../View/cliente/Perfil.php';
        </script>";
        exit;
    }

    // Buscar senha atual salva no banco
    $senhaSalva = $clientModel->buscarSenha($userId);

    if (!$senhaSalva || !password_verify($senha_atual, $senhaSalva)) {
        echo "<script>
            alert('Senha atual incorreta.');
            window.location='../View/cliente/Perfil.php';
        </script>";
        exit;
    }

    // Criar hash da nova senha
    $senhaHashSalvar = password_hash($nova_senha, PASSWORD_DEFAULT);
}

// ======================================================
// ATUALIZAR PERFIL
// ======================================================

$sucesso = $clientModel->atualizarPerfil(
    $userId,
    $nome,
    $email,
    $telefone,
    $nascimento,
    $senhaHashSalvar
);

// ======================================================
// RESULTADO
// ======================================================

if ($sucesso) {

    // Atualiza os dados utilizados na sessão
    $_SESSION['User_name'] = $nome;
    $_SESSION['User_email'] = $email;

    echo "<script>
        alert('Perfil atualizado com sucesso!');
        window.location='../View/cliente/Perfil.php';
    </script>";
    exit;
}

echo "<script>
    alert('Ocorreu um erro ao atualizar o perfil. Tente novamente mais tarde.');
    window.location='../View/cliente/Perfil.php';
</script>";
exit;

?>