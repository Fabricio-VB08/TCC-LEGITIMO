<?php
require 'mysql.php'; 
session_start();

// Define as credenciais do administrador inicial que só funcionarão uma vez
define('ADMIN_EMAIL_INICIAL', 'admin@sistema.com');
define('ADMIN_SENHA_INICIAL', 'admin123');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // 1. Verificar se já existe algum usuário no banco
    $sql_check_users = "SELECT COUNT(*) as total FROM usuarios";
    $result_check = $conn->query($sql_check_users);
    $total_users = $result_check->fetch_assoc()['total'];

    // 2. Se não houver usuários, é o primeiro login do sistema
    if ($total_users == 0) {
        if ($email === ADMIN_EMAIL_INICIAL && $senha === ADMIN_SENHA_INICIAL) {
            // Credenciais iniciais corretas. Criar o usuário administrador no banco.
            $senha_hash = password_hash(ADMIN_SENHA_INICIAL, PASSWORD_DEFAULT);
            $tipo = 'administrador';

            $stmt_create = $conn->prepare("INSERT INTO usuarios (email, senha, tipo_usuario) VALUES (?, ?, ?)");
            $stmt_create->bind_param("sss", $email, $senha_hash, $tipo);
            
            if ($stmt_create->execute()) {
                // Usuário criado, agora faz o login
                $_SESSION['id_usuario'] = $stmt_create->insert_id;
                $_SESSION['email'] = $email;
                $_SESSION['tipo_usuario'] = $tipo;
                header('Location: http://localhost/TCC-LEGITIMO/home/home.php');
                exit;
            } else {
                $erro = "Erro crítico ao criar o administrador inicial.";
            }
            $stmt_create->close();
        } else {
            $erro = "Credenciais de administrador inicial incorretas ou o sistema já possui usuários.";
        }
    } else {
        // 3. Se já existem usuários, proceder com o login normal
        $stmt = $conn->prepare("SELECT id_usuario, senha, tipo_usuario FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();
            if (password_verify($senha, $usuario['senha'])) {
                // Login bem-sucedido
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['email'] = $email;
                $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];

                header('Location: http://localhost/TCC-LEGITIMO/home/home.php');
                exit;
            }
        }
        // Se chegou até aqui, o login falhou
        $erro = "E-mail ou senha incorretos!";
        $stmt->close();
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
  <form action="login.php" method="POST">
    
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
<?php if (!empty($erro)): ?>
    <p style="color:red; text-align:center; margin-top: 10px;">
        <?php echo $erro; ?>
    </p>
<?php endif; ?>

     
    
    
        <link rel="stylesheet" href="style.css">
</body>
</html>
