<?php
require '../cadastroElogin/mysql.php';
session_start();

// Apenas administradores podem ver esta página
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    header('Location: /TCC-LEGITIMO/home/home.php');
    exit;
}

// ===== LÓGICA PARA BUSCAR E ESTRUTURAR OS DADOS =====

$professores_data = [];

// 1. Buscar todos os professores e inicializar a estrutura de dados
$result_prof = $conn->query("SELECT id_professor, nome_professor, email_professor FROM professores ORDER BY nome_professor");
while ($prof = $result_prof->fetch_assoc()) {
    $professores_data[$prof['id_professor']] = [
        'nome' => $prof['nome_professor'],
        'email' => $prof['email_professor'],
        'competencias' => [],
        'disponibilidade' => []
    ];
}

// 2. Buscar todas as competências dos professores e agrupar por matéria (UC)
$sql_competencias = "
    SELECT 
        pc.id_professor,
        uc.nome_uc,
        c.nome_competencia,
        pc.nivel_competencia
    FROM professores_competencias pc
    JOIN competencias c ON pc.id_competencia = c.id_competencia
    JOIN unidades_curriculares uc ON c.id_uc = uc.id_uc
    ORDER BY uc.nome_uc, c.nome_competencia
";
$result_comp = $conn->query($sql_competencias);
while ($comp = $result_comp->fetch_assoc()) {
    if (isset($professores_data[$comp['id_professor']])) {
        $professores_data[$comp['id_professor']]['competencias'][$comp['nome_uc']][] = [
            'nome' => $comp['nome_competencia'],
            'nivel' => $comp['nivel_competencia']
        ];
    }
}

// 3. Buscar a disponibilidade de todos os professores
$dias_ordem = "FIELD(pd.dia_semana, 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado')";
$sql_disp = "
    SELECT 
        pd.id_professor,
        pd.dia_semana,
        t.nome_turno
    FROM professores_disponibilidade pd
    JOIN turnos t ON pd.id_turno = t.id_turno
    ORDER BY $dias_ordem, t.id_turno
";
$result_disp = $conn->query($sql_disp);
while ($disp = $result_disp->fetch_assoc()) {
    if (isset($professores_data[$disp['id_professor']])) {
        $professores_data[$disp['id_professor']]['disponibilidade'][ucfirst($disp['dia_semana'])][] = $disp['nome_turno'];
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Professores</title>
    <link rel="stylesheet" href="style.css"> <!-- Estilo geral dos formulários -->
    <style>
        body { background-color: #f4f7f6; }
        .container { max-width: 1200px; }
        .grid-professores {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }
        .professor-card {
            background-color: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .professor-card h3 {
            margin-top: 0;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 10px;
            color: #0056b3;
        }
        .professor-card h4 {
            margin-top: 15px;
            margin-bottom: 8px;
            color: #333;
            font-size: 1em;
        }
        .info-section { margin-bottom: 15px; }
        .competencia-grupo, .disponibilidade-grupo {
            font-size: 0.9em;
            padding-left: 15px;
            border-left: 3px solid #f0f0f0;
        }
        .competencia-item, .disponibilidade-item { margin-bottom: 5px; }
        .competencia-item .nivel {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8em;
            margin-left: 8px;
        }
        .no-data { color: #888; font-style: italic; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Relatório de Professores</h1>
            <a href="/TCC-LEGITIMO/home/home.php" class="btn-secondary">Voltar para Home</a>
        </div>

        <?php if (empty($professores_data)): ?>
            <p>Nenhum professor cadastrado no sistema.</p>
        <?php else: ?>
            <div class="grid-professores">
                <?php foreach ($professores_data as $id => $data): ?>
                    <div class="professor-card">
                        <h3><?php echo htmlspecialchars($data['nome']); ?></h3>
                        
                        <div class="info-section">
                            <h4>Competências</h4>
                            <?php if (empty($data['competencias'])): ?>
                                <p class="no-data">Nenhuma competência registrada.</p>
                            <?php else: ?>
                                <?php foreach ($data['competencias'] as $uc => $comps): ?>
                                    <div class="competencia-grupo">
                                        <strong><?php echo htmlspecialchars($uc); ?></strong>
                                        <ul>
                                            <?php foreach ($comps as $c): ?>
                                                <li class="competencia-item"><?php echo htmlspecialchars($c['nome']); ?> <span class="nivel"><?php echo $c['nivel']; ?></span></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="info-section">
                            <h4>Disponibilidade</h4>
                            <?php if (empty($data['disponibilidade'])): ?>
                                <p class="no-data">Nenhuma disponibilidade registrada.</p>
                            <?php else: ?>
                                <div class="disponibilidade-grupo">
                                    <ul>
                                    <?php foreach ($data['disponibilidade'] as $dia => $turnos): ?>
                                        <li class="disponibilidade-item"><strong><?php echo $dia; ?>:</strong> <?php echo implode(', ', $turnos); ?></li>
                                    <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>