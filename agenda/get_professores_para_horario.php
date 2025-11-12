<?php
require '../cadastroElogin/mysql.php';
session_start();

// Apenas administradores podem acessar
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

header('Content-Type: application/json');

$id_horario = isset($_GET['id_horario']) ? (int)$_GET['id_horario'] : 0;

if ($id_horario === 0) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID do horário não fornecido']);
    exit;
}

// 1. Buscar informações do horário (dia, turno, UC)
$stmt_horario = $conn->prepare("
    SELECT h.dia_semana, t.id_turno, t.id_uc, h.id_professor_alocado
    FROM horarios h
    JOIN turmas t ON h.id_turma = t.id_turma
    WHERE h.id_horario = ?
");
$stmt_horario->bind_param("i", $id_horario);
$stmt_horario->execute();
$horario_info = $stmt_horario->get_result()->fetch_assoc();
$stmt_horario->close();

if (!$horario_info) {
    http_response_code(404);
    echo json_encode(['erro' => 'Horário não encontrado']);
    exit;
}

// 2. Buscar professores qualificados e disponíveis
$sql_professores = "
    SELECT DISTINCT p.id_professor, p.nome_professor
    FROM professores p
    JOIN professores_competencias pc ON p.id_professor = pc.id_professor
    JOIN professores_disponibilidade pd ON p.id_professor = pd.id_professor
    WHERE pc.id_competencia IN (SELECT id_competencia FROM competencias WHERE id_uc = ?)
      AND pd.dia_semana = ?
      AND pd.id_turno = ?
    ORDER BY p.nome_professor
";
$stmt_professores = $conn->prepare($sql_professores);
$stmt_professores->bind_param("isi", $horario_info['id_uc'], $horario_info['dia_semana'], $horario_info['id_turno']);
$stmt_professores->execute();
$professores = $stmt_professores->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_professores->close();

echo json_encode(['professores' => $professores, 'id_alocado' => $horario_info['id_professor_alocado']]);
?>