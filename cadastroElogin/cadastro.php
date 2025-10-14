<?php
require 'mysql.php';



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome  = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT); // senha segura

    // Se for sempre administrador por enquanto
    $tipo = "administrador"; 
    $id_professor = "NULL";


    // Depois insere na tabela usuarios
    $sql_user = "INSERT INTO usuarios (email, senha, tipo_usuario, id_professor) 
                 VALUES ('$email', '$senha', '$tipo', $id_professor)";

    if ($conn->query($sql_user) === TRUE) {
        session_start();
        $_SESSION['mensagem'] = "Usuário cadastrado com sucesso!";
        header('Location: /CalenafrontGABAS/cadastroElogin/login.php');
        exit();
    } else {
        echo "<p>Erro: " . $conn->error . "</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
</head>
<body>
     <div class="form-container">
    <h2>Cadastro</h2>
    <form action="cadastro.php" method="POST">
      <div class="input-group">
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" placeholder="Digite seu nome" required>
      </div>
      
      <div class="input-group">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" placeholder="Digite seu e-mail" required>
      </div>
      <div class="input-group">
        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" placeholder="Crie uma senha" required>
      </div>
      <button type="submit" class="submit-btn">Cadastrar</button>
    </form>
    <div class="form-footer">
      <p>Já tem uma conta? <a href="login.php">Faça login</a></p>
    </div>
  </div>


    <link rel="stylesheet" href="style.css">
</body>
</html>
