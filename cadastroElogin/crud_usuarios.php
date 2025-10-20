<?php
require 'mysql.php';
session_start();

// Verificar se está logado
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    // Se não for admin, redireciona para a home com uma mensagem de erro (opcional)
    // $_SESSION['mensagem_erro'] = "Acesso negado!";
    header('Location: /TCC-LEGITIMO/home/home.php');
    exit;
}

// ===== OPERAÇÕES DO CRUD =====

// CRIAR USUÁRIO
if (isset($_POST['acao']) && $_POST['acao'] == 'criar') {
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $tipo = $_POST['tipo_usuario'];
    $nome_professor = $_POST['nome_professor'] ?? null;

    $conn->begin_transaction();

    try {
        $id_professor = null;

        if ($tipo == 'professor' && !empty($nome_professor)) {
            $stmt_prof = $conn->prepare("INSERT INTO professores (nome_professor, email_professor) VALUES (?, ?)");
            $stmt_prof->bind_param("ss", $nome_professor, $email);
            if (!$stmt_prof->execute()) {
                throw new Exception("Erro ao criar professor: " . $stmt_prof->error);
            }
            $id_professor = $stmt_prof->insert_id;
            $stmt_prof->close();
        }

        $stmt_user = $conn->prepare("INSERT INTO usuarios (email, senha, tipo_usuario, id_professor) VALUES (?, ?, ?, ?)");
        $stmt_user->bind_param("sssi", $email, $senha, $tipo, $id_professor);
        if (!$stmt_user->execute()) {
            throw new Exception("Erro ao criar usuário: " . $stmt_user->error);
        }
        $stmt_user->close();

        $conn->commit();
        $mensagem = "Usuário criado com sucesso!";
        $tipo_mensagem = "sucesso";
    } catch (Exception $e) {
        $conn->rollback();
        $mensagem = $e->getMessage();
        $tipo_mensagem = "erro";
    }
}
// DELETAR USUÁRIO
if (isset($_GET['deletar'])) {
    $id = (int)$_GET['deletar'];
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $mensagem = "Usuário deletado com sucesso!";
        $tipo_mensagem = "sucesso";
    } else {
        $mensagem = "Erro ao deletar usuário: " . $stmt->error;
        $tipo_mensagem = "erro";
    }
    $stmt->close();
}

// EDITAR USUÁRIO
if (isset($_POST['acao']) && $_POST['acao'] == 'editar') {
    $id = (int)$_POST['id_usuario'];
    $email = $_POST['email'];
    $tipo = $_POST['tipo_usuario'];
    $nome_professor = $_POST['nome_professor'] ?? null;

    $conn->begin_transaction();

    try {
        $stmt_check = $conn->prepare("SELECT id_professor FROM usuarios WHERE id_usuario = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $usuario_atual = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        $id_professor_final = null;

        if ($tipo == 'professor') {
            if ($usuario_atual['id_professor']) {
                $stmt_prof = $conn->prepare("UPDATE professores SET nome_professor = ?, email_professor = ? WHERE id_professor = ?");
                $stmt_prof->bind_param("ssi", $nome_professor, $email, $usuario_atual['id_professor']);
                if (!$stmt_prof->execute()) {
                    throw new Exception("Erro ao atualizar professor: " . $stmt_prof->error);
                }
                $id_professor_final = $usuario_atual['id_professor'];
                $stmt_prof->close();
            } else {
                $stmt_prof = $conn->prepare("INSERT INTO professores (nome_professor, email_professor) VALUES (?, ?)");
                $stmt_prof->bind_param("ss", $nome_professor, $email);
                if (!$stmt_prof->execute()) {
                    throw new Exception("Erro ao criar novo professor: " . $stmt_prof->error);
                }
                $id_professor_final = $stmt_prof->insert_id;
                $stmt_prof->close();
            }
        }

        if (!empty($_POST['senha'])) {
            $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            $stmt_user = $conn->prepare("UPDATE usuarios SET email = ?, senha = ?, tipo_usuario = ?, id_professor = ? WHERE id_usuario = ?");
            $stmt_user->bind_param("sssis", $email, $senha, $tipo, $id_professor_final, $id);
        } else {
            $stmt_user = $conn->prepare("UPDATE usuarios SET email = ?, tipo_usuario = ?, id_professor = ? WHERE id_usuario = ?");
            $stmt_user->bind_param("ssis", $email, $tipo, $id_professor_final, $id);
        }

        if (!$stmt_user->execute()) {
            throw new Exception("Erro ao atualizar usuário: " . $stmt_user->error);
        }
        $stmt_user->close();

        $conn->commit();
        $mensagem = "Usuário atualizado com sucesso!";
        $tipo_mensagem = "sucesso";
    } catch (Exception $e) {
        $conn->rollback();
        $mensagem = $e->getMessage();
        $tipo_mensagem = "erro";
    }
}
// BUSCAR USUÁRIO PARA EDITAR
$usuario_editar = null;
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $stmt = $conn->prepare("SELECT u.*, p.nome_professor FROM usuarios u LEFT JOIN professores p ON u.id_professor = p.id_professor WHERE u.id_usuario = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows > 0) {
        $usuario_editar = $resultado->fetch_assoc();
    }
    $stmt->close();
}

// LISTAR TODOS OS USUÁRIOS
$sql_listar = "SELECT u.id_usuario, u.email, u.tipo_usuario, u.id_professor, p.nome_professor 
               FROM usuarios u 
               LEFT JOIN professores p ON u.id_professor = p.id_professor 
               ORDER BY u.id_usuario";
$resultado_lista = $conn->query($sql_listar); // Para listagem, query direta é aceitável pois não há input do usuário.
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD de Usuários</title>
    <link rel="stylesheet" href="/TCC-LEGITIMO/assets/css/style.css">
    <!-- Estilo principal para formulários e páginas de gerenciamento -->
    <link rel="stylesheet" href="/TCC-LEGITIMO/formulario/style.css">
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
            <!-- Botão para sair, mantendo o padrão das outras telas -->
            <a href="/TCC-LEGITIMO/home/home.php" class="btn-secondary">Voltar para Home</a>
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

                <div class="form-row" style="grid-template-columns: 1fr 1fr; display: grid; gap: 20px;">
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

                <div class="form-row" style="grid-template-columns: 1fr 1fr; display: grid; gap: 20px;">
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
                               value="<?php echo isset($usuario_editar['nome_professor']) ? htmlspecialchars($usuario_editar['nome_professor']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="btn-primary">
                        <?php echo isset($usuario_editar) ? "Atualizar" : "Criar"; ?>
                    </button>
                    
                    <?php if (isset($usuario_editar)): ?>
                        <a href="crud_usuarios.php" class="btn-secondary">Cancelar</a>
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
                            <th>Nome Associado</th>
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
                                        echo htmlspecialchars($usuario['nome_professor']);
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
