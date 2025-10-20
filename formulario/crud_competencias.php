<?php
require '../cadastroElogin/mysql.php';
session_start();

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    header('Location: /TCC-LEGITIMO/home/home.php');
    exit;
}

$mensagem = '';
$tipo_mensagem = '';

// ===== OPERAÇÕES DO CRUD =====

// CRIAR/EDITAR UNIDADE CURRICULAR (UC)
if (isset($_POST['acao_uc'])) {
    $nome_uc = $_POST['nome_uc'];
    if ($_POST['acao_uc'] == 'criar') {
        $stmt = $conn->prepare("INSERT INTO unidades_curriculares (nome_uc) VALUES (?)");
        $stmt->bind_param("s", $nome_uc);
    } else { // editar
        $id_uc = $_POST['id_uc'];
        $stmt = $conn->prepare("UPDATE unidades_curriculares SET nome_uc = ? WHERE id_uc = ?");
        $stmt->bind_param("si", $nome_uc, $id_uc);
    }
    if ($stmt->execute()) {
        $mensagem = "Unidade Curricular salva com sucesso!";
        $tipo_mensagem = "sucesso";
    } else {
        $mensagem = "Erro: " . $stmt->error;
        $tipo_mensagem = "erro";
    }
    $stmt->close();
}

// CRIAR COMPETÊNCIA
if (isset($_POST['acao_competencia']) && $_POST['acao_competencia'] == 'criar') {
    $id_uc = $_POST['id_uc'];
    $nome_competencia = $_POST['nome_competencia'];
    $stmt = $conn->prepare("INSERT INTO competencias (id_uc, nome_competencia) VALUES (?, ?)");
    $stmt->bind_param("is", $id_uc, $nome_competencia);
    if ($stmt->execute()) {
        $mensagem = "Competência adicionada com sucesso!";
        $tipo_mensagem = "sucesso";
    } else {
        $mensagem = "Erro: " . $stmt->error;
        $tipo_mensagem = "erro";
    }
    $stmt->close();
}

// DELETAR
if (isset($_POST['acao_delete'])) {
    if ($_POST['acao_delete'] == 'deletar_uc' && isset($_POST['id_uc'])) {
        $id = $_POST['id_uc'];
        // É uma boa prática usar transações ao deletar uma UC para garantir que suas competências também sejam removidas atomicamente.
        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM competencias WHERE id_uc = $id"); // Deleta competências primeiro
            $conn->query("DELETE FROM unidades_curriculares WHERE id_uc = $id"); // Depois a UC
            $conn->commit();
            $mensagem = "Matéria e suas competências foram deletadas.";
            $tipo_mensagem = "sucesso";
        } catch (Exception $e) {
            $conn->rollback();
            $mensagem = "Erro ao deletar matéria: " . $e->getMessage();
            $tipo_mensagem = "erro";
        }
    } elseif ($_POST['acao_delete'] == 'deletar_competencia' && isset($_POST['id_competencia'])) {
        $id = $_POST['id_competencia'];
        $stmt = $conn->prepare("DELETE FROM competencias WHERE id_competencia = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}

// BUSCAR DADOS PARA EXIBIÇÃO
$unidades_curriculares = [];
$result_uc = $conn->query("SELECT * FROM unidades_curriculares ORDER BY nome_uc");
while ($uc = $result_uc->fetch_assoc()) {
    $unidades_curriculares[$uc['id_uc']] = [
        'id_uc' => $uc['id_uc'],
        'nome_uc' => $uc['nome_uc'],
        'competencias' => []
    ];
}

$result_comp = $conn->query("SELECT * FROM competencias ORDER BY nome_competencia");
while ($comp = $result_comp->fetch_assoc()) {
    if (isset($unidades_curriculares[$comp['id_uc']])) {
        $unidades_curriculares[$comp['id_uc']]['competencias'][] = $comp;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Matérias e Competências</title>
    <!-- Usando o mesmo estilo das outras páginas de formulário -->
    <link rel="stylesheet" href="style.css">
    <style>
        /* Estilos específicos para a lista de competências dentro dos cards */
        .uc-card {
            margin-bottom: 20px;
        }
        .uc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .uc-header h3 { margin: 0; font-size: 1.1em; color: #333; }
        .competencia-list { list-style: none; padding: 0; margin-top: 15px; }
        .competencia-item { display: flex; justify-content: space-between; align-items: center; padding: 8px; border-radius: 4px; background-color: #f9f9f9; margin-bottom: 5px; }
        .competencia-item:last-child { border-bottom: none; }
        .form-add-competencia { display: flex; gap: 10px; align-items: flex-end; margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px; }
        .form-add-competencia .form-group { flex-grow: 1; margin-bottom: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gerenciar Matérias e Competências</h1>
            <a href="/TCC-LEGITIMO/home/home.php" class="btn-secondary">Voltar para Home</a>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="mensagem <?php echo $tipo_mensagem; ?>"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <!-- FORMULÁRIO PARA CRIAR UNIDADE CURRICULAR -->
        <div class="form-section">
            <h2>Adicionar Nova Matéria (Unidade Curricular)</h2>
            <form method="POST" action="crud_competencias.php">
                <input type="hidden" name="acao_uc" value="criar">
                <div class="form-group">
                    <label for="nome_uc">Nome da Matéria</label>
                    <input type="text" name="nome_uc" required>
                </div>
                <button type="submit" class="btn-primary">Criar Matéria</button>
            </form>
        </div>

        <!-- LISTA DE UNIDADES CURRICULARES E SUAS COMPETÊNCIAS -->
        <div class="table-section">
            <h2>Matérias e Competências Cadastradas</h2>
            <?php if (empty($unidades_curriculares)): ?>
                <p>Nenhuma matéria cadastrada.</p>
            <?php else: ?>
                <?php foreach ($unidades_curriculares as $uc): ?>
                    <div class="form-section uc-card">
                        <div class="uc-header">
                            <h3><?php echo htmlspecialchars($uc['nome_uc']); ?></h3>
                            <form method="POST" action="crud_competencias.php" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja deletar esta matéria e todas as suas competências?');">
                                <input type="hidden" name="acao_delete" value="deletar_uc">
                                <input type="hidden" name="id_uc" value="<?php echo $uc['id_uc']; ?>">
                                <button type="submit" class="btn-deletar">Excluir Matéria</button>
                            </form>
                        </div>

                        <!-- Lista de competências existentes -->
                        <?php if (empty($uc['competencias'])): ?>
                            <p>Nenhuma competência cadastrada para esta matéria.</p>
                        <?php else: ?>
                            <ul class="competencia-list">
                                <?php foreach ($uc['competencias'] as $comp): ?>
                                    <li class="competencia-item">
                                        <span><?php echo htmlspecialchars($comp['nome_competencia']); ?></span>
                                        <form method="POST" action="crud_competencias.php" style="display: inline;" onsubmit="return confirm('Tem certeza?');">
                                            <input type="hidden" name="acao_delete" value="deletar_competencia">
                                            <input type="hidden" name="id_competencia" value="<?php echo $comp['id_competencia']; ?>">
                                            <button type="submit" class="btn-deletar">Excluir</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <!-- Formulário para adicionar nova competência -->
                        <form method="POST" action="crud_competencias.php" class="form-add-competencia">
                            <input type="hidden" name="acao_competencia" value="criar">
                            <input type="hidden" name="id_uc" value="<?php echo $uc['id_uc']; ?>">
                            <div class="form-group">
                                <label for="nome_competencia_<?php echo $uc['id_uc']; ?>">Adicionar Nova Competência</label>
                                <input type="text" name="nome_competencia" id="nome_competencia_<?php echo $uc['id_uc']; ?>" required>
                            </div>
                            <button type="submit" class="btn-primary">Adicionar</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>