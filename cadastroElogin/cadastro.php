<?php
require 'mysql.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT); // senha segura

    $conn->begin_transaction();

    try {
        // Verifica se já existe algum usuário no banco
        $sql_check_users = "SELECT COUNT(*) as total FROM usuarios";
        $result = $conn->query($sql_check_users);
        $row = $result->fetch_assoc();

        $tipo = ($row['total'] == 0) ? "administrador" : "professor";
        $id_professor = null;

        // Se for um professor, cria o registro na tabela 'professores' primeiro
        if ($tipo == 'professor') {
            $stmt_prof = $conn->prepare("INSERT INTO professores (nome_professor, email_professor) VALUES (?, ?)");
            $stmt_prof->bind_param("ss", $nome, $email);
            if (!$stmt_prof->execute()) {
                throw new Exception("Erro ao criar o registro do professor: " . $stmt_prof->error);
            }
            $id_professor = $stmt_prof->insert_id;
            $stmt_prof->close();
        }

        // Depois insere na tabela usuarios
        $stmt_user = $conn->prepare("INSERT INTO usuarios (email, senha, tipo_usuario, id_professor) VALUES (?, ?, ?, ?)");
        $stmt_user->bind_param("sssi", $email, $senha, $tipo, $id_professor);
        if (!$stmt_user->execute()) {
            // Se a inserção do usuário falhar, remove o professor que acabamos de criar
            if ($id_professor) {
                $conn->query("DELETE FROM professores WHERE id_professor = $id_professor");
            }
            throw new Exception("Erro ao criar o usuário: " . $stmt_user->error);
        }
        $stmt_user->close();

        $conn->commit();
        session_start();
        $_SESSION['mensagem'] = "Usuário cadastrado com sucesso!";
        header('Location: login.php');
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        // Verifica se o erro é de e-mail duplicado para dar uma mensagem mais amigável
        if ($conn->errno == 1062) {
            $erro = "Este e-mail já está cadastrado. Por favor, utilize outro.";
        } else {
            $erro = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
    <link rel="stylesheet" href="/TCC-LEGITIMO/formulario/style.css">
</head>
<body>
    <div class="container" style="max-width: 600px;">
        <div class="header">
            <h1>Cadastro de Novo Usuário</h1>
        </div>

        <?php if (isset($erro)): ?>
            <div class="mensagem erro"><?php echo $erro; ?></div>
        <?php endif; ?>

        <div class="form-section">
            <form action="cadastro.php" method="POST">
                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;">Cadastrar</button>
            </form>
            <p style="text-align: center; margin-top: 20px;">
                Já tem uma conta? <a href="login.php">Faça login</a>
            </p>
        </div>
    </div>
</body>
</html>
