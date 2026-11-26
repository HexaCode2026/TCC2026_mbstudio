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

Session::validarCsrf();

$userId = $_SESSION['User_id'];

$clientModel = new Client($pdo);
$userModel = new User($pdo);

$flashRedirect = static function (string $tipo, string $msg): void {
    Session::setFlash($tipo, $msg);
    header("Location: ../View/cliente/Perfil.php");
    exit;
};

// ======================================================
// VERIFICAR SE O USUÁRIO EXISTE
// ======================================================

$dadosAtuais = $clientModel->buscarPorUserId($userId);

if (!$dadosAtuais) {
    $flashRedirect('erro', 'Erro: Usuário não encontrado no sistema.');
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
    $flashRedirect('erro', 'O campo Nome é obrigatório.');
}

if (mb_strlen($nome) > 100) {
    $flashRedirect('erro', 'O nome não pode ultrapassar 100 caracteres.');
}

// ======================================================
// VALIDAR EMAIL
// ======================================================

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $flashRedirect('erro', 'Por favor, informe um email válido.');
}

if (strlen($email) > 150) {
    $flashRedirect('erro', 'O email não pode ultrapassar 150 caracteres.');
}

// Verificar email duplicado somente se o email foi alterado
if ($email !== $dadosAtuais['User_email']) {

    if ($userModel->emailExiste($email)) {
        $flashRedirect('erro', 'Este email já está sendo usado por outro usuário.');
    }
}

// ======================================================
// VALIDAR TELEFONE
// ======================================================

if (!empty($telefone)) {

    if (strlen($telefone) > 20) {
        $flashRedirect('erro', 'Telefone excede o limite de caracteres.');
    }

    // Permite números, espaços, parênteses e hífen
    if (!preg_match('/^[\d\s\(\)\-]+$/', $telefone)) {
        $flashRedirect('erro', 'Telefone contém caracteres inválidos.');
    }

    // Verifica quantidade real de números
    $apenasDigitos = preg_replace('/\D/', '', $telefone);

    if (strlen($apenasDigitos) < 10 || strlen($apenasDigitos) > 11) {
        $flashRedirect('erro', 'O telefone deve ter 10 ou 11 dígitos.');
    }
}

// ======================================================
// VALIDAR DATA DE NASCIMENTO
// ======================================================

// Data obrigatória
if (empty($nascimento)) {
    $flashRedirect('erro', 'A data de nascimento é obrigatória.');
}

// Validar formato Y-m-d
$dt = DateTime::createFromFormat('Y-m-d', $nascimento);

if (!$dt || $dt->format('Y-m-d') !== $nascimento) {
    $flashRedirect('erro', 'Data de nascimento inválida.');
}

// Não permitir data futura
$hoje = new DateTime();

if ($dt > $hoje) {
    $flashRedirect('erro', 'A data de nascimento não pode estar no futuro.');
}

// ======================================================
// IDADE MÍNIMA: 16 ANOS
// ======================================================

$idade = $hoje->diff($dt)->y;

if ($idade < 16) {
    $flashRedirect('erro', 'É necessário ter pelo menos 16 anos.');
}

// ======================================================
// ALTERAÇÃO DE SENHA
// ======================================================

$senhaHashSalvar = null;

// Só altera a senha se uma nova senha for informada
if (!empty($nova_senha)) {

    // Exigir senha atual
    if (empty($senha_atual)) {
        $flashRedirect('erro', 'Para alterar a senha, você precisa informar a Senha Atual.');
    }

    // Nova senha deve possuir pelo menos 6 caracteres
    if (mb_strlen($nova_senha) < 6) {
        $flashRedirect('erro', 'A nova senha deve ter no mínimo 6 caracteres.');
    }

    // Verificar confirmação
    if ($nova_senha !== $confirmar_senha) {
        $flashRedirect('erro', 'As senhas não coincidem!');
    }

    // Buscar senha atual salva no banco
    $senhaSalva = $clientModel->buscarSenha($userId);

    if (!$senhaSalva || !password_verify($senha_atual, $senhaSalva)) {
        $flashRedirect('erro', 'Senha atual incorreta.');
    }

    // Criar hash da nova senha
    $senhaHashSalvar = User::hashSenha($nova_senha);
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

    $flashRedirect('sucesso', 'Perfil atualizado com sucesso!');
}

$flashRedirect('erro', 'Ocorreu um erro ao atualizar o perfil. Tente novamente mais tarde.');

?>