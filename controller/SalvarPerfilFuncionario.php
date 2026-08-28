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



$userId = $_SESSION['User_id'];



$employeeModel = new Employee($pdo);

$userModel = new User($pdo);




// =====================================
// VERIFICAR REGISTRO DO FUNCIONÁRIO
// =====================================

$dadosAtuais = $employeeModel->buscarPorUserId($userId);



if(!$dadosAtuais){


    echo "

    <script>

    alert('Cadastro de funcionário não encontrado no sistema.');

    window.location='../View/funcionario/Perfil.php';

    </script>

    ";

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


    echo "

    <script>

    alert('O campo Nome é obrigatório.');

    window.location='../View/funcionario/Perfil.php';

    </script>

    ";

    exit;


}



if(mb_strlen($nome) > 100){


    echo "

    <script>

    alert('O campo Nome deve ter no máximo 100 caracteres.');

    window.location='../View/funcionario/Perfil.php';

    </script>

    ";

    exit;


}




// =====================================
// VALIDAÇÃO: EMAIL
// =====================================

if(empty($email)){


    echo "

    <script>

    alert('O campo Email é obrigatório.');

    window.location='../View/funcionario/Perfil.php';

    </script>

    ";

    exit;


}



if(!filter_var($email, FILTER_VALIDATE_EMAIL)){


    echo "

    <script>

    alert('Informe um email válido.');

    window.location='../View/funcionario/Perfil.php';

    </script>

    ";

    exit;


}




// Verificar se o email foi alterado e se já existe em outra conta

if($email !== $dadosAtuais['User_email']){


    if($userModel->emailExiste($email)){


        echo "

        <script>

        alert('Este email já está sendo utilizado por outro usuário.');

        window.location='../View/funcionario/Perfil.php';

        </script>

        ";

        exit;


    }


}




// =====================================
// VALIDAÇÃO: ESPECIALIDADE
// =====================================

if(mb_strlen($especialidade) > 100){


    echo "

    <script>

    alert('O campo Especialidade deve ter no máximo 100 caracteres.');

    window.location='../View/funcionario/Perfil.php';

    </script>

    ";

    exit;


}




// =====================================
// VALIDAÇÃO: SENHA (OPCIONAL)
// =====================================

$novaSenhaHash = null;



if(!empty($novaSenha)){


    if(empty($senhaAtual)){


        echo "

        <script>

        alert('Para alterar a senha, você deve informar a senha atual.');

        window.location='../View/funcionario/Perfil.php';

        </script>

        ";

        exit;


    }



    if(empty($confirmarSenha)){


        echo "

        <script>

        alert('Confirme a nova senha.');

        window.location='../View/funcionario/Perfil.php';

        </script>

        ";

        exit;


    }



    if(mb_strlen($novaSenha) < 6){


        echo "

        <script>

        alert('A nova senha deve ter no mínimo 6 caracteres.');

        window.location='../View/funcionario/Perfil.php';

        </script>

        ";

        exit;


    }



    if($novaSenha !== $confirmarSenha){


        echo "

        <script>

        alert('A nova senha e a confirmação de senha não coincidem.');

        window.location='../View/funcionario/Perfil.php';

        </script>

        ";

        exit;


    }



    $dadosSenha = $employeeModel->buscarSenha($userId);



    if(!$dadosSenha || !password_verify($senhaAtual, $dadosSenha['User_pass'])){


        echo "

        <script>

        alert('A senha atual informada está incorreta.');

        window.location='../View/funcionario/Perfil.php';

        </script>

        ";

        exit;


    }



    // Gera o hash da nova senha (salvamento ocorre após atualização do perfil)

    $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);


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


        echo "

        <script>

        alert('Erro no envio da foto. Tente novamente.');

        window.location='../View/funcionario/Perfil.php';

        </script>

        ";

        exit;


    }


    // 2. Verificar tamanho (máximo 2MB)

    if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {


        echo "

        <script>

        alert('A foto deve ter no máximo 2MB.');

        window.location='../View/funcionario/Perfil.php';

        </script>

        ";

        exit;


    }


    // 3. Verificar extensão

    $extensao = strtolower(
        pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION)
    );

    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($extensao, $extensoesPermitidas, true)) {


        echo "

        <script>

        alert('Formato de imagem não permitido. Use JPG, PNG ou WebP.');

        window.location='../View/funcionario/Perfil.php';

        </script>

        ";

        exit;


    }


    // 4. Verificar MIME real via finfo (não confiar na extensão)

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $mimeReal = $finfo->file($_FILES['foto']['tmp_name']);

    $mimesPermitidos = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($mimeReal, $mimesPermitidos, true)) {


        echo "

        <script>

        alert('O arquivo enviado não é uma imagem válida.');

        window.location='../View/funcionario/Perfil.php';

        </script>

        ";

        exit;


    }


    // 5. Verificar conteúdo de imagem real

    if (!getimagesize($_FILES['foto']['tmp_name'])) {


        echo "

        <script>

        alert('O arquivo enviado não é uma imagem válida.');

        window.location='../View/funcionario/Perfil.php';

        </script>

        ";

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

    $nomeArquivo = 'func_' . $userId . '_' . uniqid() . '.' . $extensaoSegura;

    $pastaDestino = __DIR__ . '/../uploads/funcionarios/';


    if (!is_dir($pastaDestino)) {

        mkdir($pastaDestino, 0755, true);

    }


    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $pastaDestino . $nomeArquivo)) {


        echo "

        <script>

        alert('Erro ao salvar a foto no servidor. Tente novamente.');

        window.location='../View/funcionario/Perfil.php';

        </script>

        ";

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


    echo "

    <script>

    alert('Erro ao atualizar o perfil. Tente novamente.');

    window.location='../View/funcionario/Perfil.php';

    </script>

    ";

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



echo "

<script>

alert('Perfil atualizado com sucesso!');

window.location='../View/funcionario/Perfil.php';

</script>

";


?>
