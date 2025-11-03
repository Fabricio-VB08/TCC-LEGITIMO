<?php
require '../cadastroElogin/mysql.php';
session_start();

// 1. VERIFICAÇÃO DE ACESSO
// Apenas professores logados podem acessar.
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'professor') {
    header('Location: /TCC-LEGITIMO/home/home.php');
    exit;
}

// 2. BUSCAR O ID DO PROFESSOR ASSOCIADO AO USUÁRIO
$id_usuario = $_SESSION['id_usuario'];
$stmt_prof_id = $conn->prepare("SELECT id_professor FROM usuarios WHERE id_usuario = ?");
$stmt_prof_id->bind_param("i", $id_usuario);
$stmt_prof_id->execute();
$result_prof_id = $stmt_prof_id->get_result();
$professor_info = $result_prof_id->fetch_assoc();
$stmt_prof_id->close();

if (!$professor_info || !$professor_info['id_professor']) {
    // Caso o usuário seja professor mas não tenha um registro na tabela professores (improvável)
    die("Erro: Perfil de professor não encontrado.");
}
$id_professor = $professor_info['id_professor'];

$mensagem = '';
$tipo_mensagem = '';
$atividade_editar = null;

// 3. OPERAÇÕES CRUD

// AÇÃO: CRIAR ou EDITAR
if (isset($_POST['acao']) && ($_POST['acao'] == 'criar' || $_POST['acao'] == 'editar')) {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $tipo = $_POST['tipo'];
    $data_entrega = !empty($_POST['data_entrega']) ? $_POST['data_entrega'] : null;

    if ($_POST['acao'] == 'criar') {
        $stmt = $conn->prepare("INSERT INTO atividades_professor (id_professor, titulo, descricao, tipo, data_entrega) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $id_professor, $titulo, $descricao, $tipo, $data_entrega);
        $mensagem = "Atividade criada com sucesso!";
    } else { // Editar
        $id_atividade = (int)$_POST['id_atividade'];
        $stmt = $conn->prepare("UPDATE atividades_professor SET titulo = ?, descricao = ?, tipo = ?, data_entrega = ? WHERE id_atividade = ? AND id_professor = ?");
        $stmt->bind_param("ssssii", $titulo, $descricao, $tipo, $data_entrega, $id_atividade, $id_professor);
        $mensagem = "Atividade atualizada com sucesso!";
    }

    if ($stmt->execute()) {
        $tipo_mensagem = "sucesso";
    } else {
        $mensagem = "Erro: " . $stmt->error;
        $tipo_mensagem = "erro";
    }
    $stmt->close();
}

// AÇÃO: DELETAR
if (isset($_POST['acao']) && $_POST['acao'] == 'deletar') {
    $id_atividade = (int)$_POST['id_atividade'];
    $stmt = $conn->prepare("DELETE FROM atividades_professor WHERE id_atividade = ? AND id_professor = ?");
    $stmt->bind_param("ii", $id_atividade, $id_professor);
    if ($stmt->execute()) {
        $mensagem = "Atividade deletada com sucesso!";
        $tipo_mensagem = "sucesso";
    } else {
        $mensagem = "Erro ao deletar: " . $stmt->error;
        $tipo_mensagem = "erro";
    }
    $stmt->close();
}

// AÇÃO: MARCAR COMO CONCLUÍDA/PENDENTE
if (isset($_GET['toggle_concluida'])) {
    $id_atividade = (int)$_GET['toggle_concluida'];
    $estado_atual = (int)$_GET['estado'];
    $novo_estado = $estado_atual ? 0 : 1; // Inverte o estado

    $stmt = $conn->prepare("UPDATE atividades_professor SET concluida = ? WHERE id_atividade = ? AND id_professor = ?");
    $stmt->bind_param("iii", $novo_estado, $id_atividade, $id_professor);
    $stmt->execute();
    $stmt->close();
    // Redireciona para limpar os parâmetros GET da URL
    header('Location: area_atividades.php');
    exit;
}

// AÇÃO: CARREGAR DADOS PARA EDIÇÃO
if (isset($_GET['editar'])) {
    $id_atividade = (int)$_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM atividades_professor WHERE id_atividade = ? AND id_professor = ?");
    $stmt->bind_param("ii", $id_atividade, $id_professor);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $atividade_editar = $result->fetch_assoc();
    }
    $stmt->close();
}

// 4. BUSCAR TODAS AS ATIVIDADES PARA LISTAGEM
$atividades = [];
$sql_listar = "SELECT * FROM atividades_professor WHERE id_professor = ? ORDER BY concluida ASC, data_entrega ASC, data_criacao DESC";
$stmt_listar = $conn->prepare($sql_listar);
$stmt_listar->bind_param("i", $id_professor);
$stmt_listar->execute();
$resultado_lista = $stmt_listar->get_result();
while ($row = $resultado_lista->fetch_assoc()) {
    $atividades[] = $row;
}
$stmt_listar->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área de Atividades</title>
    <link rel="stylesheet" href="/TCC-LEGITIMO/formulario/style.css">
    <link rel="stylesheet" href="/TCC-LEGITIMO/home/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Minhas Atividades</h1>
            <a href="/TCC-LEGITIMO/home/home.php" class="btn-secondary">Voltar para Home</a>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?php echo $tipo_mensagem; ?>"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <!-- FORMULÁRIO PARA CRIAR/EDITAR -->
        <div class="form-section">
            <h2><?php echo isset($atividade_editar) ? "Editar Atividade" : "Adicionar Nova Atividade"; ?></h2>
            
            <form method="POST" action="area_atividades.php">
                <input type="hidden" name="acao" value="<?php echo isset($atividade_editar) ? 'editar' : 'criar'; ?>">
                <?php if (isset($atividade_editar)): ?>
                    <input type="hidden" name="id_atividade" value="<?php echo $atividade_editar['id_atividade']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="titulo">Título</label>
                    <input type="text" name="titulo" id="titulo" required value="<?php echo htmlspecialchars($atividade_editar['titulo'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição (Opcional)</label>
                    <textarea name="descricao" id="descricao" rows="3"><?php echo htmlspecialchars($atividade_editar['descricao'] ?? ''); ?></textarea>
                </div>

                <div class="form-row" style="grid-template-columns: 1fr 1fr; display: grid; gap: 20px;">
                    <div class="form-group">
                        <label for="tipo">Tipo</label>
                        <select name="tipo" id="tipo" required>
                            <option value="tarefa" <?php echo (isset($atividade_editar) && $atividade_editar['tipo'] == 'tarefa') ? 'selected' : ''; ?>>Tarefa</option>
                            <option value="prova" <?php echo (isset($atividade_editar) && $atividade_editar['tipo'] == 'prova') ? 'selected' : ''; ?>>Prova</option>
                            <option value="trabalho" <?php echo (isset($atividade_editar) && $atividade_editar['tipo'] == 'trabalho') ? 'selected' : ''; ?>>Trabalho</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="data_entrega">Data de Entrega (Opcional)</label>
                        <input type="date" name="data_entrega" id="data_entrega" value="<?php echo htmlspecialchars($atividade_editar['data_entrega'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="btn-primary">
                        <?php echo isset($atividade_editar) ? "Atualizar Atividade" : "Salvar Atividade"; ?>
                    </button>
                    <?php if (isset($atividade_editar)): ?>
                        <a href="area_atividades.php" class="btn-secondary">Cancelar Edição</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- LISTA DE ATIVIDADES -->
        <div class="atividades-container">
            <h2>Minha Lista</h2>
            <?php if (empty($atividades)): ?>
                <p>Nenhuma atividade registrada ainda. Use o formulário acima para começar!</p>
            <?php else: ?>
                <div class="atividades-grid">
                    <?php foreach ($atividades as $atividade): ?>
                        <div class="atividade-card <?php echo $atividade['concluida'] ? 'concluida' : ''; ?>">
                            <div class="card-header">
                                <span class="tipo-tag tipo-<?php echo $atividade['tipo']; ?>"><?php echo ucfirst($atividade['tipo']); ?></span>
                                <div class="card-acoes">
                                    <!-- Marcar como Concluída -->
                                    <a href="?toggle_concluida=<?php echo $atividade['id_atividade']; ?>&estado=<?php echo $atividade['concluida']; ?>" 
                                       class="btn-acao btn-concluir" 
                                       title="<?php echo $atividade['concluida'] ? 'Marcar como pendente' : 'Marcar como concluída'; ?>">
                                       <?php echo $atividade['concluida'] ? '&#x21A9;' : '&#x2713;'; // Seta para desfazer ou checkmark ?>
                                    </a>
                                    <!-- Editar -->
                                    <a href="?editar=<?php echo $atividade['id_atividade']; ?>" class="btn-acao btn-editar" title="Editar">&#9998;</a>
                                    <!-- Deletar -->
                                    <form method="POST" action="area_atividades.php" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja deletar esta atividade?');">
                                        <input type="hidden" name="acao" value="deletar">
                                        <input type="hidden" name="id_atividade" value="<?php echo $atividade['id_atividade']; ?>">
                                        <button type="submit" class="btn-acao btn-deletar" title="Deletar">&times;</button>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body">
                                <h3><?php echo htmlspecialchars($atividade['titulo']); ?></h3>
                                <?php if (!empty($atividade['descricao'])): ?>
                                    <p><?php echo nl2br(htmlspecialchars($atividade['descricao'])); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <?php if ($atividade['data_entrega']): ?>
                                    <span><strong>Entrega:</strong> <?php echo date('d/m/Y', strtotime($atividade['data_entrega'])); ?></span>
                                <?php else: ?>
                                    <span>Sem data de entrega</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>