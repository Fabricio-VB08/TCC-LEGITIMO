<?php
require '../cadastroElogin/mysql.php';
session_start();

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    header('Location: /TCC-LEGITIMO/home/home.php');
    exit;
}

$mensagem = '';
$tipo_mensagem = '';
$turma_editar = null;

// --- OPERAÇÕES CRUD ---

// CRIAR ou EDITAR
if (isset($_POST['acao']) && ($_POST['acao'] == 'criar' || $_POST['acao'] == 'editar')) {
    $nome_turma = $_POST['nome_turma'];
    $id_uc = (int)$_POST['id_uc'];
    $id_turno = (int)$_POST['id_turno'];

    if ($_POST['acao'] == 'criar') {
        $stmt = $conn->prepare("INSERT INTO turmas (nome_turma, id_uc, id_turno) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $nome_turma, $id_uc, $id_turno);
        $mensagem = "Turma criada com sucesso!";
    } else { // Editar
        $id_turma = (int)$_POST['id_turma'];
        $stmt = $conn->prepare("UPDATE turmas SET nome_turma = ?, id_uc = ?, id_turno = ? WHERE id_turma = ?");
        $stmt->bind_param("siii", $nome_turma, $id_uc, $id_turno, $id_turma);
        $mensagem = "Turma atualizada com sucesso!";
    }

    if ($stmt->execute()) {
        $tipo_mensagem = "sucesso";
    } else {
        $mensagem = "Erro: " . $stmt->error;
        $tipo_mensagem = "erro";
    }
    $stmt->close();
}

// DELETAR
if (isset($_POST['acao']) && $_POST['acao'] == 'deletar') {
    $id_turma = (int)$_POST['id_turma'];
    $stmt = $conn->prepare("DELETE FROM turmas WHERE id_turma = ?");
    $stmt->bind_param("i", $id_turma);
    if ($stmt->execute()) {
        $mensagem = "Turma deletada com sucesso!";
        $tipo_mensagem = "sucesso";
    } else {
        $mensagem = "Erro ao deletar: " . $stmt->error;
        $tipo_mensagem = "erro";
    }
    $stmt->close();
}

// BUSCAR DADOS PARA FORMULÁRIO E LISTA
$unidades_curriculares = $conn->query("SELECT id_uc, nome_uc FROM unidades_curriculares ORDER BY nome_uc")->fetch_all(MYSQLI_ASSOC);
$turnos = $conn->query("SELECT id_turno, nome_turno FROM turnos ORDER BY id_turno")->fetch_all(MYSQLI_ASSOC);
$turmas = $conn->query("
    SELECT t.id_turma, t.nome_turma, uc.nome_uc, tu.nome_turno
    FROM turmas t
    JOIN unidades_curriculares uc ON t.id_uc = uc.id_uc
    JOIN turnos tu ON t.id_turno = tu.id_turno
    ORDER BY t.nome_turma
")->fetch_all(MYSQLI_ASSOC);

// Carregar dados para edição
if (isset($_GET['editar'])) {
    $id_turma = (int)$_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM turmas WHERE id_turma = ?");
    $stmt->bind_param("i", $id_turma);
    $stmt->execute();
    $turma_editar = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Turmas</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gerenciar Turmas</h1>
            <a href="/TCC-LEGITIMO/home/home.php" class="btn-secondary">Voltar para Home</a>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?php echo $tipo_mensagem; ?>"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <div class="form-section">
            <h2><?php echo $turma_editar ? 'Editar Turma' : 'Adicionar Nova Turma'; ?></h2>
            <form method="POST" action="crud_turmas.php">
                <input type="hidden" name="acao" value="<?php echo $turma_editar ? 'editar' : 'criar'; ?>">
                <?php if ($turma_editar): ?>
                    <input type="hidden" name="id_turma" value="<?php echo $turma_editar['id_turma']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nome_turma">Nome da Turma</label>
                    <input type="text" name="nome_turma" id="nome_turma" required value="<?php echo htmlspecialchars($turma_editar['nome_turma'] ?? ''); ?>">
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="id_uc">Matéria (Unidade Curricular)</label>
                        <select name="id_uc" id="id_uc" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($unidades_curriculares as $uc): ?>
                                <option value="<?php echo $uc['id_uc']; ?>" <?php echo (isset($turma_editar) && $turma_editar['id_uc'] == $uc['id_uc']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($uc['nome_uc']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_turno">Turno</label>
                        <select name="id_turno" id="id_turno" required>
                             <option value="">Selecione...</option>
                            <?php foreach ($turnos as $turno): ?>
                                <option value="<?php echo $turno['id_turno']; ?>" <?php echo (isset($turma_editar) && $turma_editar['id_turno'] == $turno['id_turno']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($turno['nome_turno']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="margin-top: 20px;"><?php echo $turma_editar ? 'Atualizar Turma' : 'Criar Turma'; ?></button>
                 <?php if ($turma_editar): ?>
                    <a href="crud_turmas.php" class="btn-secondary">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-section">
            <h2>Turmas Cadastradas</h2>
            <?php if (empty($turmas)): ?>
                <p>Nenhuma turma cadastrada.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr><th>Nome da Turma</th><th>Matéria</th><th>Turno</th><th>Ações</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($turmas as $turma): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($turma['nome_turma']); ?></td>
                                <td><?php echo htmlspecialchars($turma['nome_uc']); ?></td>
                                <td><?php echo htmlspecialchars($turma['nome_turno']); ?></td>
                                <td>
                                    <a href="?editar=<?php echo $turma['id_turma']; ?>" class="btn-editar">Editar</a>
                                    <form method="POST" action="crud_turmas.php" style="display:inline;" onsubmit="return confirm('Tem certeza?');">
                                        <input type="hidden" name="acao" value="deletar"><input type="hidden" name="id_turma" value="<?php echo $turma['id_turma']; ?>">
                                        <button type="submit" class="btn-deletar">Deletar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>