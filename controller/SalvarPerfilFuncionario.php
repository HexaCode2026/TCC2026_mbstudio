<?php


require_once __DIR__ . "/../config/conexao.php";

require_once __DIR__ . "/../core/Session.php";

require_once __DIR__ . "/../model/User.php";

require_once __DIR__ . "/../model/Employee.php";



Session::iniciar();



// =====================================
// VERIFICAÇÃO DE ACESSO E SESSÃO
// =====================================

if(

!isset($_SESSION['User_id'])

||

!isset($_SESSION['User_perm'])

||

$_SESSION['User_perm'] != 'F'

){


    header(
    "Location: ../Index.php"
    );

    exit;


}



// =====================================
// APENAS REQUISIÇÃO POST
// =====================================

if($_SERVER["REQUEST_METHOD"] != "POST"){


    header(
    "Location: ../View/funcionario/Perfil.php"
    );

    exit;


}

Session::validarCsrf();

$userId = $_SESSION['User_id'];



$employeeModel = new Employee($pdo);

$userModel = new User($pdo);




// =====================================
// VERIFICAR REGISTRO DO FUNCIONÁRIO
// =====================================

$dadosAtuais = $employeeModel->buscarPorUserId($userId);



if(!$dadosAtuais){


    Session::setFlash('erro', 'Cadastro de funcionário não encontrado no sistema.');
    header("Location: ../View/funcionario/Perfil.php");

    exit;


}




// =====================================
// RECEBER DADOS
// =====================================

$nome            = trim($_POST['nome'] ?? '');

$email           = trim($_POST['email'] ?? '');

$especialidade   = trim($_POST['especialidade'] ?? '');

$bio             = trim($_POST['bio'] ?? '');

$senhaAtual      = $_POST['senha_atual'] ?? '';

$novaSenha       = $_POST['nova_senha'] ?? '';

$confirmarSenha  = $_POST['confirmar_senha'] ?? '';




// =====================================
// VALIDAÇÃO: NOME
// =====================================

if(empty($nome)){


    Session::setFlash('erro', 'O campo Nome é obrigatório.');
    header("Location: ../View/funcionario/Perfil.php");

    exit;


}



if(mb_strlen($nome) > 100){


    Session::setFlash('erro', 'O campo Nome deve ter no máximo 100 caracteres.');
    header("Location: ../View/funcionario/Perfil.php");

    exit;


}




// =====================================
// VALIDAÇÃO: EMAIL
// =====================================

if(empty($email)){


    Session::setFlash('erro', 'O campo Email é obrigatório.');
    header("Location: ../View/funcionario/Perfil.php");

    exit;


}



if(!filter_var($email, FILTER_VALIDATE_EMAIL)){


    Session::setFlash('erro', 'Informe um email válido.');
    header("Location: ../View/funcionario/Perfil.php");

    exit;


}




// Verificar se o email foi alterado e se já existe em outra conta

if($email !== $dadosAtuais['User_email']){


    if($userModel->emailExiste($email)){


        Session::setFlash('erro', 'Este email já está sendo utilizado por outro usuário.');
    header("Location: ../View/funcionario/Perfil.php");

        exit;


    }


}




// =====================================
// VALIDAÇÃO: ESPECIALIDADE
// =====================================

if(mb_strlen($especialidade) > 100){


    Session::setFlash('erro', 'O campo Especialidade deve ter no máximo 100 caracteres.');
    header("Location: ../View/funcionario/Perfil.php");

    exit;


}




// =====================================
// VALIDAÇÃO: SENHA (OPCIONAL)
// =====================================

$novaSenhaHash = null;



if(!empty($novaSenha)){


    if(empty($senhaAtual)){


        Session::setFlash('erro', 'Para alterar a senha, você deve informar a senha atual.');
    header("Location: ../View/funcionario/Perfil.php");

        exit;


    }



    if(empty($confirmarSenha)){


        Session::setFlash('erro', 'Confirme a nova senha.');
    header("Location: ../View/funcionario/Perfil.php");

        exit;


    }



    if(mb_strlen($novaSenha) < 6){


        Session::setFlash('erro', 'A nova senha deve ter no mínimo 6 caracteres.');
    header("Location: ../View/funcionario/Perfil.php");

        exit;


    }



    if($novaSenha !== $confirmarSenha){


        Session::setFlash('erro', 'A nova senha e a confirmação de senha não coincidem.');
    header("Location: ../View/funcionario/Perfil.php");

        exit;


    }



    $dadosSenha = $employeeModel->buscarSenha($userId);



    if(!$dadosSenha || !password_verify($senhaAtual, $dadosSenha['User_pass'])){


        Session::setFlash('erro', 'A senha atual informada está incorreta.');
    header("Location: ../View/funcionario/Perfil.php");

        exit;


    }



    // Gera o hash da nova senha (salvamento ocorre após atualização do perfil)

    $novaSenhaHash = User::hashSenha($novaSenha);


}




// =====================================
// VALIDAÇÃO: FOTO DE PERFIL (OPCIONAL)
// =====================================

$caminhoNovaFoto = null;


if (

    isset($_FILES['foto'])

    &&

    $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE

) {


    // 1. Verificar erro de upload

    if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {


        Session::setFlash('erro', 'Erro no envio da foto. Tente novamente.');
    header("Location: ../View/funcionario/Perfil.php");

        exit;


    }


    // 2. Verificar tamanho (máximo 2MB)

    if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {


        Session::setFlash('erro', 'A foto deve ter no máximo 2MB.');
    header("Location: ../View/funcionario/Perfil.php");

        exit;


    }


    // 3. Verificar extensão

    $extensao = strtolower(
        pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION)
    );

    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($extensao, $extensoesPermitidas, true)) {


        Session::setFlash('erro', 'Formato de imagem não permitido. Use JPG, PNG ou WebP.');
    header("Location: ../View/funcionario/Perfil.php");

        exit;


    }


    // 4. Verificar MIME real via finfo (não confiar na extensão)

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $mimeReal = $finfo->file($_FILES['foto']['tmp_name']);

    $mimesPermitidos = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($mimeReal, $mimesPermitidos, true)) {


        Session::setFlash('erro', 'O arquivo enviado não é uma imagem válida.');
    header("Location: ../View/funcionario/Perfil.php");

        exit;


    }


    // 5. Verificar conteúdo de imagem real

    if (!getimagesize($_FILES['foto']['tmp_name'])) {


        Session::setFlash('erro', 'O arquivo enviado não é uma imagem válida.');
    header("Location: ../View/funcionario/Perfil.php");

        exit;


    }


    // 6. Determinar extensão segura baseada no MIME real

    $mapaExtensoes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $extensaoSegura = $mapaExtensoes[$mimeReal];


    // 7. Gerar nome único e mover arquivo

    $nomeArquivo = 'func_' . $userId . '_' . bin2hex(random_bytes(16)) . '.' . $extensaoSegura;

    $pastaDestino = __DIR__ . '/../uploads/funcionarios/';


    if (!is_dir($pastaDestino)) {

        mkdir($pastaDestino, 0755, true);

    }


    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $pastaDestino . $nomeArquivo)) {


        Session::setFlash('erro', 'Erro ao salvar a foto no servidor. Tente novamente.');
    header("Location: ../View/funcionario/Perfil.php");

        exit;


    }


    $caminhoNovaFoto = 'uploads/funcionarios/' . $nomeArquivo;


}




// =====================================
// GUARDAR FOTO ANTIGA ANTES DA ATUALIZAÇÃO
// =====================================

$fotoAntiga = $dadosAtuais['Emp_photo'] ?? '';




// =====================================
// ATUALIZAR PERFIL (TRANSAÇÃO ÚNICA)
// =====================================

$atualizou = $employeeModel->atualizarPerfil(

    $userId,

    $nome,

    $email,

    $especialidade,

    $bio,

    $novaSenhaHash,

    $caminhoNovaFoto

);




if(!$atualizou){


    // Se nova foto foi movida, remover para evitar arquivo órfão

    if ($caminhoNovaFoto !== null) {

        $caminhoAbsolutoNovo = __DIR__ . '/../' . $caminhoNovaFoto;

        if (is_file($caminhoAbsolutoNovo)) {

            unlink($caminhoAbsolutoNovo);

        }

    }


    Session::setFlash('erro', 'Erro ao atualizar o perfil. Tente novamente.');
    header("Location: ../View/funcionario/Perfil.php");

    exit;


}




// =====================================
// SUCESSO: REMOVER FOTO ANTIGA COM SEGURANÇA
// =====================================

if ($caminhoNovaFoto !== null && !empty($fotoAntiga)) {

    $caminhoAbsolutoAntigo = __DIR__ . '/../' . $fotoAntiga;

    $pastaPermitida = realpath(__DIR__ . '/../uploads/funcionarios/');


    if ($pastaPermitida !== false) {

        $caminhoRealAntigo = realpath($caminhoAbsolutoAntigo);


        if (

            $caminhoRealAntigo !== false

            &&

            strpos($caminhoRealAntigo, $pastaPermitida) === 0

            &&

            dirname($caminhoRealAntigo) === $pastaPermitida

            &&

            is_file($caminhoRealAntigo)

        ) {

            unlink($caminhoRealAntigo);

        }

    }

}




// =====================================
// SUCESSO: ATUALIZAR SESSÃO E REDIRECIONAR
// =====================================

$_SESSION['User_name'] = $nome;

$_SESSION['User_email'] = $email;



Session::setFlash('sucesso', 'Perfil atualizado com sucesso!');
    header("Location: ../View/funcionario/Perfil.php");


?>
