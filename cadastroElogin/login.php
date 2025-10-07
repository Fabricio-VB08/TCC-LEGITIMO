<?php
require 'mysql.php';
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
        <form action="#" method="GET">
          <div class="input-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="Digite seu e-mail" required>
          </div>
          <div class="input-group">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" placeholder="Crie uma senha" required>
          </div>
          <button type="submit" class="submit-btn">Cadastrar</button>
          <div class="form-footer">
            Não possui login? <a href="cadastro.html">cadastre-se</a>
        </div>
        </form>
      </div>
     
    
    
        <link rel="stylesheet" href="style.css">
</body>
</html>