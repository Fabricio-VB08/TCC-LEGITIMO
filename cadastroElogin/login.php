<?php
require 'mysql.php'; 
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Preparar a consulta para evitar SQL Injection
    $stmt = $conn->prepare("SELECT  id_professor, senha, email FROM usuarios WHERE email = ?");
    if (!$stmt) {
    die("Erro na query: " . $conn->error);
}

    

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        // Verificar a senha (se foi salva com password_hash no cadastro)
        if (password_verify($senha, $usuario['senha'])) {
            // Login bem-sucedido
            $_SESSION['id_usuario'] = $usuario['id_professor'];
            $_SESSION['email'] = $email;
              header('Location: http://localhost/CalenafrontGABAS/home/home.php');
            exit;
  if ($usuario['tipo'] === 'administrador') {
                  header('');
                  exit;
              } else {
                  header('Location: /CalenafrontGABAS/home/home.php');
                  exit;
              }

          } else {
              $erro = "Senha incorreta!";
          }
      } else {
          $erro = "Usuário não encontrado!";
      }
  }
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Horários - Login</title>
</head>
<body>
      <div class="form-container">
    <h2>Login</h2>
  <form action="" method="POST">
    
  <div class="input-group">
    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" required>
  </div>
  <div class="input-group">
    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" required>
  </div>
  <button type="submit" class="submit-btn">Entrar</button>
  
</form>

</div>
<?php if (!empty($erro)) echo "<p style='color:red'>$erro</p>"; ?>

     
    
    
        <link rel="stylesheet" href="style.css">
</body>
</html>
