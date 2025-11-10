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
        tu.nome_turno,
        p.nome_professor
    FROM horarios h
    JOIN turmas t ON h.id_turma = t.id_turma
    JOIN turnos tu ON t.id_turno = tu.id_turno
    LEFT JOIN professores p ON h.id_professor_alocado = p.id_professor
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
                                    $turmas_no_turno[$aloc['nome_turma']][$dia_key] = $aloc['nome_professor'];
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
                                            <?php if (isset($horarios_turma[$dia])): ?>
                                                <span class="prof-nome"><?php echo htmlspecialchars($horarios_turma[$dia] ?: 'Vago'); ?></span>
                                            <?php else: ?>
                                                <span class="prof-nome vago">--</span>
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
</body>
</html>
