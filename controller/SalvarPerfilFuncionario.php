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
// ATUALIZAR PERFIL (TRANSAÇÃO ÚNICA)
// =====================================

$atualizou = $employeeModel->atualizarPerfil(

    $userId,

    $nome,

    $email,

    $especialidade,

    $bio,

    $novaSenhaHash

);




if(!$atualizou){


    echo "

    <script>

    alert('Erro ao atualizar o perfil. Tente novamente.');

    window.location='../View/funcionario/Perfil.php';

    </script>

    ";

    exit;


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
