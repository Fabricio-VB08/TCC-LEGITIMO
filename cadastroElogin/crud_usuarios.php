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

// --- DADOS PARA O FORMULÁRIO ---
// Buscar turnos para o formulário de disponibilidade
$turnos_result = $conn->query("SELECT * FROM turnos ORDER BY id_turno");
$turnos = $turnos_result->fetch_all(MYSQLI_ASSOC);

// Dias da semana
$dias_semana = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'];


// ===== OPERAÇÕES DO CRUD =====

// CRIAR USUÁRIO
if (isset($_POST['acao']) && $_POST['acao'] == 'criar') {
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $tipo = $_POST['tipo_usuario'];
    $nome_professor = $_POST['nome_professor'] ?? null;
    $disponibilidade = $_POST['disponibilidade'] ?? [];

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

            // Salvar disponibilidade
            if (!empty($disponibilidade)) {
                $stmt_disp = $conn->prepare("INSERT INTO professores_disponibilidade (id_professor, dia_semana, id_turno) VALUES (?, ?, ?)");
                foreach ($disponibilidade as $dia => $turnos_selecionados) {
                    foreach ($turnos_selecionados as $id_turno) {
                        $stmt_disp->bind_param("isi", $id_professor, $dia, $id_turno);
                        $stmt_disp->execute();
                    }
                }
                $stmt_disp->close();
            }
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
if (isset($_POST['acao']) && $_POST['acao'] == 'deletar') {
    $id_usuario = (int)$_POST['id_usuario'];

    $conn->begin_transaction();
    try {
        // Primeiro, descobrir se este usuário é um professor para deletar da tabela de professores.
        $stmt_get_prof = $conn->prepare("SELECT id_professor FROM usuarios WHERE id_usuario = ?");
        $stmt_get_prof->bind_param("i", $id_usuario);
        $stmt_get_prof->execute();
        $result = $stmt_get_prof->get_result();
        $usuario = $result->fetch_assoc();
        $stmt_get_prof->close();

        // Se for um professor, deleta o registro da tabela 'professores'.
        // A constraint ON DELETE CASCADE cuidará de deletar o usuário associado.
        if ($usuario && $usuario['id_professor']) {
            $stmt_del_prof = $conn->prepare("DELETE FROM professores WHERE id_professor = ?");
            $stmt_del_prof->bind_param("i", $usuario['id_professor']);
            $stmt_del_prof->execute();
            $stmt_del_prof->close();
        } else {
            // Se não for professor (ex: admin), deleta diretamente da tabela 'usuarios'.
            $stmt_del_user = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
            $stmt_del_user->bind_param("i", $id_usuario);
            $stmt_del_user->execute();
            $stmt_del_user->close();
        }

        $conn->commit();
        $mensagem = "Usuário deletado com sucesso!";
        $tipo_mensagem = "sucesso";
    } catch (Exception $e) {
        $conn->rollback();
        $mensagem = "Erro ao deletar usuário: " . $e->getMessage();
        $tipo_mensagem = "erro";
    }
}

// EDITAR USUÁRIO
if (isset($_POST['acao']) && $_POST['acao'] == 'editar') {
    $id = (int)$_POST['id_usuario'];
    $email = $_POST['email'];
    $tipo = $_POST['tipo_usuario'];
    $nome_professor = $_POST['nome_professor'] ?? null;
    $disponibilidade = $_POST['disponibilidade'] ?? [];

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

            // Atualizar disponibilidade
            // 1. Deletar a disponibilidade antiga
            $stmt_del_disp = $conn->prepare("DELETE FROM professores_disponibilidade WHERE id_professor = ?");
            $stmt_del_disp->bind_param("i", $id_professor_final);
            $stmt_del_disp->execute();
            $stmt_del_disp->close();
            // 2. Inserir a nova
            $stmt_add_disp = $conn->prepare("INSERT INTO professores_disponibilidade (id_professor, dia_semana, id_turno) VALUES (?, ?, ?)");
            foreach ($disponibilidade as $dia => $turnos_selecionados) {
                foreach ($turnos_selecionados as $id_turno) {
                    $stmt_add_disp->bind_param("isi", $id_professor_final, $dia, $id_turno);
                    $stmt_add_disp->execute();
                }
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
$disponibilidade_atual = [];
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar']; // GET para edição é aceitável, pois não modifica dados.
    $stmt = $conn->prepare("SELECT u.*, p.nome_professor FROM usuarios u LEFT JOIN professores p ON u.id_professor = p.id_professor WHERE u.id_usuario = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows > 0) {
        $usuario_editar = $resultado->fetch_assoc();
        // Se for um professor, buscar sua disponibilidade
        if ($usuario_editar['id_professor']) {
            $stmt_disp = $conn->prepare("SELECT dia_semana, id_turno FROM professores_disponibilidade WHERE id_professor = ?");
            $stmt_disp->bind_param("i", $usuario_editar['id_professor']);
            $stmt_disp->execute();
            $result_disp = $stmt_disp->get_result();
            while ($row = $result_disp->fetch_assoc()) {
                $disponibilidade_atual[$row['dia_semana']][] = $row['id_turno'];
            }
            $stmt_disp->close();
        }
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
    <style>
        .disponibilidade-section { display: none; }
        .disponibilidade-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-top: 10px; }
        .dia-bloco { background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 5px; padding: 15px; }
        .dia-bloco h4 { margin-top: 0; margin-bottom: 10px; font-size: 1em; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .turno-group label { display: block; margin-bottom: 5px; font-weight: normal; cursor: pointer; }
        .turno-group input { margin-right: 8px; }
        .professor-fields-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            display: none; /* Começa escondido */
        }
    </style>
    <script>
        function toggleProfessorFields(tipo) {
            const nomeField = document.getElementById('professor-nome-field');
            const disponibilidadeSection = document.getElementById('disponibilidade-section');
            const nomeInput = document.getElementById('nome_professor');

            if (tipo === 'professor') {
                nomeField.style.display = 'block';
                disponibilidadeSection.style.display = 'block';
                nomeInput.required = true;
            } else {
                nomeField.style.display = 'none';
                disponibilidadeSection.style.display = 'none';
                nomeInput.required = false;
            }
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

                    <div class="form-group" id="professor-nome-field" style="display: none;">
                        <label for="nome_professor">Nome do Professor</label>
                        <input type="text" name="nome_professor" id="nome_professor" 
                               value="<?php echo isset($usuario_editar['nome_professor']) ? htmlspecialchars($usuario_editar['nome_professor']) : ''; ?>">
                    </div>
                </div>

                <!-- Seção de Disponibilidade para Professores -->
                <div id="disponibilidade-section" style="display: none; margin-top: 20px;">
                    <h3>Disponibilidade de Horários</h3>
                    <div class="disponibilidade-grid">
                        <?php foreach ($dias_semana as $dia): ?>
                            <div class="dia-bloco">
                                <h4><?php echo ucfirst($dia); ?></h4>
                                <div class="turno-group">
                                    <?php foreach ($turnos as $turno): 
                                        $checked = isset($disponibilidade_atual[$dia]) && in_array($turno['id_turno'], $disponibilidade_atual[$dia]) ? 'checked' : '';
                                    ?>
                                        <label><input type="checkbox" name="disponibilidade[<?php echo $dia; ?>][]" value="<?php echo $turno['id_turno']; ?>" <?php echo $checked; ?>> <?php echo $turno['nome_turno']; ?></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
                                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja deletar este usuário? A ação não pode ser desfeita.');">
                                            <input type="hidden" name="acao" value="deletar">
                                            <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">
                                            <button type="submit" class="btn-deletar">Deletar</button>
                                        </form>
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
