<?php 
require "../cadastroElogin/mysql.php"; // Ajuste o caminho se necessário

// Lógica para salvar as competências
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['salvar_competencias'])) {
    $id_professor = $_POST['id_professor'];
    $competencias = $_POST['competencias'] ?? [];

    if (!empty($id_professor) && !empty($competencias)) {
        $conn->begin_transaction();
        try {
            // 1. Deleta as competências antigas do professor para depois inserir as novas.
            // Isso simplifica a lógica de ter que verificar o que mudou.
            $stmt_delete = $conn->prepare("DELETE FROM professores_competencias WHERE id_professor = ?");
            $stmt_delete->bind_param("i", $id_professor);
            $stmt_delete->execute();
            $stmt_delete->close();

            // 2. Insere as novas competências
            $stmt_insert = $conn->prepare("INSERT INTO professores_competencias (id_professor, id_competencia, nivel_competencia) VALUES (?, ?, ?)");
            
            foreach ($competencias as $id_competencia => $nivel) {
                if (!empty($nivel)) { // Apenas insere se um nível foi selecionado
                    $stmt_insert->bind_param("iis", $id_professor, $id_competencia, $nivel);
                    $stmt_insert->execute();
                }
            }
            $stmt_insert->close();

            $conn->commit();
            $mensagem = "Competências salvas com sucesso!";
        } catch (Exception $e) {
            $conn->rollback();
            $mensagem = "Erro ao salvar competências: " . $e->getMessage();
        }
    }
}

// --- Lógica para buscar dados para o formulário ---

// Buscar todos os professores
$professores_result = $conn->query("SELECT id_professor, nome_professor FROM professores ORDER BY nome_professor");
$professores = $professores_result->fetch_all(MYSQLI_ASSOC);

// Buscar todas as UCs e suas competências
$unidades_com_competencias = [];
$sql = "SELECT uc.id_uc, uc.nome_uc, c.id_competencia, c.nome_competencia 
        FROM unidades_curriculares uc
        LEFT JOIN competencias c ON uc.id_uc = c.id_uc
        ORDER BY uc.nome_uc, c.nome_competencia";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    $unidades_com_competencias[$row['id_uc']]['nome_uc'] = $row['nome_uc'];
    if ($row['id_competencia']) { // Apenas adiciona se houver competência
        $unidades_com_competencias[$row['id_uc']]['competencias'][] = [
            'id_competencia' => $row['id_competencia'],
            'nome_competencia' => $row['nome_competencia']
        ];
    }
}

// Verificar se um professor foi selecionado (via GET ou POST) para carregar suas competências
$selected_professor_id = $_REQUEST['id_professor'] ?? null;
$competencias_atuais = [];

if ($selected_professor_id) {
    $stmt = $conn->prepare("SELECT id_competencia, nivel_competencia FROM professores_competencias WHERE id_professor = ?");
    $stmt->bind_param("i", $selected_professor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $competencias_atuais[$row['id_competencia']] = $row['nivel_competencia'];
    }
    $stmt->close();
}

$niveis = ['N0', 'N1', 'N2', 'N3'];

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de Competências</title>
    <link rel="stylesheet" href="style.css"> <!-- Estilo principal do formulário -->
    <link rel="stylesheet" href="index.css"> <!-- Estilos específicos desta página -->
    <!-- Select2 para pesquisa no dropdown -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Formulário de Competências do Professor</h1>
            <a href="/TCC-LEGITIMO/home/home.php" class="btn-secondary">Voltar para Home</a>
        </div>
        
        <?php if (isset($mensagem)): ?>
            <p class="mensagem"><?php echo $mensagem; ?></p>
        <?php endif; ?>

        <div class="form-section">
            <h2>1. Selecione o Professor</h2>
            <?php if (empty($professores)): ?>
                <div class="info-box">
                    <p>Nenhum professor encontrado.</p>
                    <p>Para registrar competências, primeiro você precisa cadastrar um professor no sistema.</p>
                    <br>
                    <a href="/TCC-LEGITIMO/cadastroElogin/crud_usuarios.php" class="btn-primary">Cadastrar Novo Professor</a>
                </div>
            <?php else: ?>
                <!-- Formulário para selecionar o professor -->
                <form action="index.php" method="GET" class="form-group">
                    <label for="professor">Professor:</label>
                    <select name="id_professor" id="professor" required style="width: 100%; max-width: 400px;">
                        <option value="">-- Escolha um professor --</option>
                        <?php foreach ($professores as $professor): ?>
                            <option value="<?php echo $professor['id_professor']; ?>" <?php echo ($selected_professor_id == $professor['id_professor']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($professor['nome_professor']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <!-- Este formulário será submetido pelo JS ao trocar o professor -->
                </form>
            <?php endif; ?>
        </div>

        <!-- Formulário para definir as competências (só aparece se um professor for selecionado) -->
        <?php if ($selected_professor_id): ?>
            <div class="form-section">
                <form action="index.php" method="POST">
                    <input type="hidden" name="id_professor" value="<?php echo $selected_professor_id; ?>">
                    
                    <h2>2. Defina os Níveis de Competência</h2>
                    <div id="materias-container">
                        <?php if (empty($unidades_com_competencias)): ?>
                            <p>Nenhuma matéria cadastrada. <a href="crud_competencias.php">Cadastre aqui</a>.</p>
                        <?php else: ?>
                            <?php foreach ($unidades_com_competencias as $uc): ?>
                                <fieldset class="materia-group">
                                    <legend><?php echo htmlspecialchars($uc['nome_uc']); ?></legend>
                                    <?php if (empty($uc['competencias'])): ?>
                                        <p style="font-size: 14px; color: #777; padding: 10px;">Nenhuma competência específica cadastrada para esta matéria.</p>
                                    <?php else: ?>
                                        <?php foreach ($uc['competencias'] as $competencia): 
                                            $id_competencia = $competencia['id_competencia'];
                                            $nivel_atual = $competencias_atuais[$id_competencia] ?? '';
                                        ?>
                                            <div class="competencia-row">
                                                <span class="competencia-nome"><?php echo htmlspecialchars($competencia['nome_competencia']); ?></span>
                                                <div class="niveis-group">
                                                    <?php foreach ($niveis as $nivel): ?>
                                                        <label><input type="radio" name="competencias[<?php echo $id_competencia; ?>]" value="<?php echo $nivel; ?>" <?php echo ($nivel_atual == $nivel) ? 'checked' : ''; ?>> <?php echo $nivel; ?></label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </fieldset>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <br>
                    <button type="submit" name="salvar_competencias" class="btn-primary">SALVAR COMPETÊNCIAS</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="index.js"></script>
</body>
</html>
