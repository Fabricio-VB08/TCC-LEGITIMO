<?php
require 'mysql.php';



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome  = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT); // senha segura

    // Verifica se já existe algum usuário no banco
    $sql_check_users = "SELECT COUNT(*) as total FROM usuarios";
    $result = $conn->query($sql_check_users);
    $row = $result->fetch_assoc();

    // Se não houver usuários, o primeiro é administrador. Senão, é professor.
    if ($row['total'] == 0) {
        $tipo = "administrador";
    } else {
        $tipo = "professor";
    }

    $id_professor = "NULL";

    // Depois insere na tabela usuarios
    $stmt = $conn->prepare("INSERT INTO usuarios (email, senha, tipo_usuario, id_professor) VALUES (?, ?, ?, NULL)");
    $stmt->bind_param("sss", $email, $senha, $tipo);
    if ($stmt->execute()) {
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
