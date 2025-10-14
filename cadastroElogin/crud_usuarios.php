<?php
require 'mysql.php';
session_start();

// Verificar se está logado
if (!isset($_SESSION['email'])) {
    header('Location: /CalenafrontGABAS/cadastroElogin/login.php');
    exit;
}

// ===== OPERAÇÕES DO CRUD =====

// CRIAR USUÁRIO
if (isset($_POST['acao']) && $_POST['acao'] == 'criar') {
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $tipo = $_POST['tipo_usuario'];
    
    // Inicia a transação
    $conn->begin_transaction();
    
    try {
        $id_professor = "NULL";
        
        // Se for professor, criar entrada na tabela de professores
        if ($tipo == 'professor' && !empty($_POST['nome_professor'])) {
            $nome_professor = $conn->real_escape_string($_POST['nome_professor']);
            $email_professor = $conn->real_escape_string($email); // Usando o mesmo email do usuário
            $sql_professor = "INSERT INTO professores (nome_professor, email_professor) VALUES ('$nome_professor', '$email_professor')";
            
            if ($conn->query($sql_professor)) {
                $id_professor = $conn->insert_id;
            } else {
                throw new Exception("Erro ao criar professor: " . $conn->error);
            }
        }

        // Insere o usuário
        $sql = "INSERT INTO usuarios (email, senha, tipo_usuario, id_professor) 
                VALUES ('$email', '$senha', '$tipo', $id_professor)";

        if ($conn->query($sql)) {
            $conn->commit();
            $mensagem = "Usuário " . ($tipo == 'professor' ? "e professor " : "") . "criado com sucesso!";
            $tipo_mensagem = "sucesso";
        } else {
            throw new Exception("Erro ao criar usuário: " . $conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        $mensagem = $e->getMessage();
        $tipo_mensagem = "erro";
    }
}

// DELETAR USUÁRIO
if (isset($_GET['deletar'])) {
    $id = $_GET['deletar'];
    $sql = "DELETE FROM usuarios WHERE id_usuario = $id";

    if ($conn->query($sql) === TRUE) {
        $mensagem = "Usuário deletado com sucesso!";
        $tipo_mensagem = "sucesso";
    } else {
        $mensagem = "Erro ao deletar usuário: " . $conn->error;
        $tipo_mensagem = "erro";
    }
}

// EDITAR USUÁRIO
if (isset($_POST['acao']) && $_POST['acao'] == 'editar') {
    $id = $_POST['id_usuario'];
    $email = $_POST['email'];
    $tipo = $_POST['tipo_usuario'];
    
    // Inicia a transação
    $conn->begin_transaction();
    
    try {
        // Verificar se o usuário já tem um professor associado
        $sql_check = "SELECT id_professor, tipo_usuario FROM usuarios WHERE id_usuario = $id";
        $result_check = $conn->query($sql_check);
        $usuario_atual = $result_check->fetch_assoc();
        
        // Gerenciar o registro do professor
        if ($tipo == 'professor') {
            $nome_professor = $conn->real_escape_string($_POST['nome_professor']);
            
            if ($usuario_atual['id_professor']) {
                // Atualizar professor existente
                $sql_prof = "UPDATE professores SET 
                            nome_professor = '$nome_professor',
                            email_professor = '$email'
                            WHERE id_professor = {$usuario_atual['id_professor']}";
                if (!$conn->query($sql_prof)) {
                    throw new Exception("Erro ao atualizar professor: " . $conn->error);
                }
                $id_professor = $usuario_atual['id_professor'];
            } else {
                // Criar novo professor
                $sql_prof = "INSERT INTO professores (nome_professor, email_professor) 
                            VALUES ('$nome_professor', '$email')";
                if (!$conn->query($sql_prof)) {
                    throw new Exception("Erro ao criar professor: " . $conn->error);
                }
                $id_professor = $conn->insert_id;
            }
        } else {
            $id_professor = "NULL";
            
            // Se estava como professor e mudou para outro tipo, mantém o registro do professor
            // mas remove a associação com o usuário
        }

        // Atualizar usuário
        if (!empty($_POST['senha'])) {
            $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET 
                    email = '$email', 
                    senha = '$senha', 
                    tipo_usuario = '$tipo', 
                    id_professor = $id_professor 
                    WHERE id_usuario = $id";
        } else {
            $sql = "UPDATE usuarios SET 
                    email = '$email', 
                    tipo_usuario = '$tipo', 
                    id_professor = $id_professor 
                    WHERE id_usuario = $id";
        }

        if ($conn->query($sql)) {
            $conn->commit();
            $mensagem = "Usuário atualizado com sucesso!";
            $tipo_mensagem = "sucesso";
        } else {
            throw new Exception("Erro ao atualizar usuário: " . $conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        $mensagem = $e->getMessage();
        $tipo_mensagem = "erro";
    }
}

// BUSCAR USUÁRIO PARA EDITAR
$usuario_editar = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $sql = "SELECT * FROM usuarios WHERE id_usuario = $id";
    $resultado = $conn->query($sql);
    $usuario_editar = $resultado->fetch_assoc();
}

// LISTAR TODOS OS USUÁRIOS
$sql_listar = "SELECT * FROM usuarios";
$resultado_lista = $conn->query($sql_listar);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD de Usuários</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(90deg, rgba(235, 242, 245, 1) 0%, rgba(207, 236, 255, 1) 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background-color: #162c68;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
        }

        .btn-logout {
            background-color: #d32f2f;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-logout:hover {
            background-color: #b71c1c;
        }

        .mensagem {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: none;
        }

        .mensagem.sucesso {
            background-color: #4caf50;
            color: white;
            display: block;
        }

        .mensagem.erro {
            background-color: #f44336;
            color: white;
            display: block;
        }

        .form-section {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-section h2 {
            margin-bottom: 20px;
            color: #333;
            font-size: 18px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-size: 14px;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            max-width: 400px;
        }

        input:focus, select:focus {
            border-color: #5048be;
            outline: none;
        }

        .form-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .form-row .form-group {
            flex: 1;
            min-width: 200px;
        }

        .btn-primary {
            background-color: #5048be;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #2b599e;
        }

        .btn-secondary {
            background-color: #757575;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-secondary:hover {
            background-color: #616161;
        }

        .table-section {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .table-section h2 {
            margin-bottom: 20px;
            color: #333;
            font-size: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #f5f5f5;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            font-weight: bold;
            color: #333;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .btn-editar {
            background-color: #2196f3;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
        }

        .btn-editar:hover {
            background-color: #0b7dda;
        }

        .btn-deletar {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .btn-deletar:hover {
            background-color: #da190b;
        }

        .acao {
            display: flex;
            gap: 5px;
        }
    </style>
    <script>
        function toggleProfessorFields(tipo) {
            const professorFields = document.querySelectorAll('.professor-field');
            professorFields.forEach(field => {
                field.style.display = tipo === 'professor' ? 'block' : 'none';
                if (tipo === 'professor') {
                    field.querySelector('input').required = true;
                } else {
                    field.querySelector('input').required = false;
                }
            });
        }

        // Executar quando a página carrega para configurar o estado inicial
        document.addEventListener('DOMContentLoaded', function() {
            const tipoSelect = document.getElementById('tipo_usuario');
            if (tipoSelect) {
                toggleProfessorFields(tipoSelect.value);
            }
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gerenciamento de Usuários</h1>
            <a href="/CalenafrontGABAS/home/home.php" class="btn-logout">Sair</a>
        </div>

        <?php if (isset($mensagem)): ?>
            <div class="mensagem <?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <!-- FORMULÁRIO PARA CRIAR/EDITAR -->
        <div class="form-section">
            <h2><?php echo isset($usuario_editar) ? "Editar Usuário" : "Adicionar Novo Usuário"; ?></h2>
            
            <form method="POST" action="">
                <input type="hidden" name="acao" value="<?php echo isset($usuario_editar) ? 'editar' : 'criar'; ?>">
                
                <?php if (isset($usuario_editar)): ?>
                    <input type="hidden" name="id_usuario" value="<?php echo $usuario_editar['id_usuario']; ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" name="email" id="email" required 
                               value="<?php echo isset($usuario_editar) ? $usuario_editar['email'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="senha">Senha <?php echo isset($usuario_editar) ? "(deixe em branco para manter)" : ""; ?></label>
                        <input type="password" name="senha" id="senha" 
                               <?php echo !isset($usuario_editar) ? 'required' : ''; ?>>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo_usuario">Tipo de Usuário</label>
                        <select name="tipo_usuario" id="tipo_usuario" required onchange="toggleProfessorFields(this.value)">
                            <option value="">Selecione...</option>
                            <option value="administrador" <?php echo isset($usuario_editar) && $usuario_editar['tipo_usuario'] == 'administrador' ? 'selected' : ''; ?>>Administrador</option>
                            <option value="professor" <?php echo isset($usuario_editar) && $usuario_editar['tipo_usuario'] == 'professor' ? 'selected' : ''; ?>>Professor</option>
                        </select>
                    </div>

                    <div class="form-group professor-field" style="display: none;">
                        <label for="nome_professor">Nome do Professor</label>
                        <input type="text" name="nome_professor" id="nome_professor" 
                               value="<?php 
                                    if (isset($usuario_editar) && $usuario_editar['id_professor']) {
                                        $sql_prof = "SELECT nome_professor FROM professores WHERE id_professor = " . $usuario_editar['id_professor'];
                                        $result_prof = $conn->query($sql_prof);
                                        if ($prof = $result_prof->fetch_assoc()) {
                                            echo htmlspecialchars($prof['nome_professor']);
                                        }
                                    }
                               ?>"
                               <?php echo isset($usuario_editar) && $usuario_editar['tipo_usuario'] == 'professor' ? 'required' : ''; ?>
                        >>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn-primary">
                        <?php echo isset($usuario_editar) ? "Atualizar" : "Criar"; ?>
                    </button>
                    
                    <?php if (isset($usuario_editar)): ?>
                        <a href="crud_usuarios.php" class="btn-secondary" style="text-decoration: none; display: inline-block;">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- TABELA DE USUÁRIOS -->
        <div class="table-section">
            <h2>Usuários Cadastrados</h2>
            
            <?php if ($resultado_lista->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>E-mail</th>
                            <th>Tipo</th>
                            <th>ID Professor</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($usuario = $resultado_lista->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $usuario['id_usuario']; ?></td>
                                <td><?php echo $usuario['email']; ?></td>
                                <td><?php echo ucfirst($usuario['tipo_usuario']); ?></td>
                                <td><?php 
                                    if ($usuario['id_professor']) {
                                        $sql_prof = "SELECT nome_professor FROM professores WHERE id_professor = " . $usuario['id_professor'];
                                        $result_prof = $conn->query($sql_prof);
                                        if ($prof = $result_prof->fetch_assoc()) {
                                            echo $prof['nome_professor'] . " (ID: " . $usuario['id_professor'] . ")";
                                        } else {
                                            echo "ID: " . $usuario['id_professor'];
                                        }
                                    } else {
                                        echo '-';
                                    }
                                ?></td>
                                <td>
                                    <div class="acao">
                                        <a href="?editar=<?php echo $usuario['id_usuario']; ?>" class="btn-editar">Editar</a>
                                        <a href="?deletar=<?php echo $usuario['id_usuario']; ?>" class="btn-deletar" 
                                           onclick="return confirm('Tem certeza que deseja deletar?');">Deletar</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhum usuário cadastrado.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
