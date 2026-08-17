<!DOCTYPE html>

<html>

<head>

<title>
Cadastro
</title>

</head>


<body>


<h2>
Cadastro de Usuário
</h2>


<form 
method="POST"
action="../controller/Cadastro.php">


<label>
Nome:
</label>

<br>

<input 
type="text"
name="nome"
required>


<br><br>



<label>
Email:
</label>

<br>

<input 
type="email"
name="email"
required>


<br><br>



<label>
Senha:
</label>

<br>


<input
type="password"
name="senha"
required>


<br><br>



<button type="submit">

Cadastrar

</button>



</form>


</body>

</html>