<?php
require '../cadastroElogin/mysql.php';
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: /TCC-LEGITIMO/cadastroElogin/login.php');
    exit;
}
$is_admin = $_SESSION['tipo_usuario'] === 'administrador';

// Buscar dados para a agenda
$dias_semana = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'];
$turnos = $conn->query("SELECT * FROM turnos ORDER BY id_turno")->fetch_all(MYSQLI_ASSOC);

// Estrutura para armazenar os horários
$horarios_grid = [];
foreach ($turnos as $turno) {
    foreach ($dias_semana as $dia) {
        $horarios_grid[$turno['nome_turno']][$dia] = [];
    }
}

// Buscar os horários já alocados
$sql = "
    SELECT 
        h.dia_semana,
        t.nome_turma,
        h.id_horario,
        tu.nome_turno,
        p.nome_professor
    FROM horarios h
    JOIN turmas t ON h.id_turma = t.id_turma
    JOIN turnos tu ON t.id_turno = tu.id_turno
    LEFT JOIN professores p ON h.id_professor_alocado = p.id_professor
    WHERE t.id_turno IN (SELECT id_turno FROM turnos) AND h.dia_semana IN ('segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado')
    ORDER BY tu.id_turno, t.nome_turma
";

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $horarios_grid[$row['nome_turno']][$row['dia_semana']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda de Horários</title>
    <link rel="stylesheet" href="/TCC-LEGITIMO/formulario/style.css">
    <link rel="stylesheet" href="agenda.css">
    <style>
        /* Estilos para o Modal de Edição */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .celula-horario { position: relative; }
        .btn-editar-horario { position: absolute; top: 5px; right: 5px; background: #eee; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 14px; display: none; align-items: center; justify-content: center; }
        .celula-horario:hover .btn-editar-horario { display: flex; }
    </style>
</head>
<body>
    <!-- NAVBAR PADRÃO DO SITE -->
    <div class="navbar">
        <div class="esquerda"></div>
        <div class="direita">
            <?php if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 'administrador'): ?>
                <a href="/TCC-LEGITIMO/cadastroElogin/crud_usuarios.php">Gerenciar Usuários</a>    
            <?php endif; ?>
            <a href="/TCC-LEGITIMO/home/home.php">Início</a>
            <a href="/TCC-LEGITIMO/cadastroElogin/login.php" class="btn-sair">Sair</a>
        </div>
    </div>

    <div class="container-main">
        <div class="header">
            <h1>Agenda de Horários</h1>
        </div>

        <?php if ($is_admin): ?>
        <div class="form-section">
            <h2>Controles da Agenda</h2>
            <p>Clique no botão abaixo para executar o algoritmo de alocação automática de professores. O sistema irá preencher os horários vagos com os professores mais aptos e disponíveis.</p>
            <form action="alocar.php" method="POST" onsubmit="return confirm('Isso irá redefinir e alocar todos os professores. Deseja continuar?');">
                <button type="submit" name="alocar" class="btn-primary">Alocar Professores</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="agenda-container">
            <?php if (empty($horarios_grid)): ?>
                <p>Nenhum turno cadastrado.</p>
            <?php else: ?>
                <?php foreach ($horarios_grid as $nome_turno => $dias): ?>
                    <div class="turno-section">
                        <h2><?php echo htmlspecialchars($nome_turno); ?></h2>
                        <div class="agenda-grid">
                            <div class="header-dia">Turma</div>
                            <?php foreach ($dias_semana as $dia): ?>
                                <div class="header-dia"><?php echo ucfirst($dia); ?></div>
                            <?php endforeach; ?>

                            <?php
                            // Agrupar por turma para criar as linhas
                            $turmas_no_turno = [];
                            foreach ($dias as $dia_key => $alocacoes) {
                                foreach ($alocacoes as $aloc) {
                                    $turmas_no_turno[$aloc['nome_turma']][$dia_key] = ['professor' => $aloc['nome_professor'], 'id_horario' => $aloc['id_horario']];
                                }
                            }
                            ?>

                            <?php if (empty($turmas_no_turno)): ?>
                                <div class="linha-vazia">Nenhuma turma para este turno. <a href="/TCC-LEGITIMO/formulario/crud_turmas.php">Cadastre uma turma</a>.</div>
                            <?php else: ?>
                                <?php foreach ($turmas_no_turno as $nome_turma => $horarios_turma): ?>
                                    <div class="nome-turma"><?php echo htmlspecialchars($nome_turma); ?></div>
                                    <?php foreach ($dias_semana as $dia): ?>
                                        <div class="celula-horario">
                                            <?php if (isset($horarios_turma[$dia]) && $horarios_turma[$dia]['id_horario']): ?>
                                                <span class="prof-nome"><?php echo htmlspecialchars($horarios_turma[$dia]['professor'] ?: 'Vago'); ?></span>
                                                <?php if ($is_admin): ?>
                                                    <button class="btn-editar-horario" title="Editar Horário" onclick="abrirModalEdicao(<?php echo $horarios_turma[$dia]['id_horario']; ?>)">&#9998;</button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="prof-nome vago"></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de Edição -->
    <?php if ($is_admin): ?>
    <div id="modalEdicao" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fecharModalEdicao()">&times;</span>
            <h2>Editar Alocação</h2>
            <form id="formEdicao" method="POST" action="alocar.php">
                <input type="hidden" name="acao" value="editar_horario">
                <input type="hidden" name="id_horario" id="id_horario_modal">
                
                <div class="form-group">
                    <label for="id_professor_modal">Selecione o Professor:</label>
                    <select name="id_professor" id="id_professor_modal" class="form-control" required>
                        <!-- Opções serão carregadas via JavaScript -->
                    </select>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="btn-primary">Salvar Alteração</button>
                    <button type="button" class="btn-secondary" onclick="fecharModalEdicao()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modalEdicao');
        const idHorarioInput = document.getElementById('id_horario_modal');
        const professorSelect = document.getElementById('id_professor_modal');

        function abrirModalEdicao(idHorario) {
            idHorarioInput.value = idHorario;
            professorSelect.innerHTML = '<option>Carregando...</option>';
            modal.style.display = 'block';

            // Usando fetch para buscar os professores disponíveis para este horário
            fetch(`get_professores_para_horario.php?id_horario=${idHorario}`)
                .then(response => response.json())
                .then(data => {
                    professorSelect.innerHTML = ''; // Limpa o select
                    
                    // Adiciona a opção para deixar vago
                    const optionVago = document.createElement('option');
                    optionVago.value = '0'; // Usamos 0 para indicar "sem professor"
                    optionVago.textContent = '--- Deixar Vago ---';
                    if (data.id_alocado === null) optionVago.selected = true;
                    professorSelect.appendChild(optionVago);

                    // Adiciona os professores
                    data.professores.forEach(prof => {
                        const option = document.createElement('option');
                        option.value = prof.id_professor;
                        option.textContent = prof.nome_professor;
                        if (prof.id_professor == data.id_alocado) {
                            option.selected = true;
                        }
                        professorSelect.appendChild(option);
                    });
                });
        }

        function fecharModalEdicao() {
            modal.style.display = 'none';
        }
    </script>
    <?php endif; ?>
</body>
</html>
