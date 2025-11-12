<?php
require '../cadastroElogin/mysql.php';
session_start();

// Apenas administradores podem executar estas ações
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    header('Location: /TCC-LEGITIMO/home/home.php');
    exit;
}

// AÇÃO: Editar um único horário (vinda do modal)
if (isset($_POST['acao']) && $_POST['acao'] === 'editar_horario') {
    $id_horario = (int)$_POST['id_horario'];
    // Se o valor for '0', significa "deixar vago", então salvamos NULL no banco.
    $id_professor = $_POST['id_professor'] === '0' ? null : (int)$_POST['id_professor'];

    $stmt = $conn->prepare("UPDATE horarios SET id_professor_alocado = ? WHERE id_horario = ?");
    $stmt->bind_param("ii", $id_professor, $id_horario);
    $stmt->execute();
    $stmt->close();

    // Redireciona de volta para a agenda
    header('Location: agenda.php');
    exit;
}

function alocarProfessores($conn) {
    $conn->begin_transaction();
    try {
        // 1. Limpa a tabela de horários para recomeçar a alocação
        $conn->query("DELETE FROM horarios");

        // 2. Pega todas as turmas e cria os slots de horário para cada dia da semana
        $turmas = $conn->query("SELECT * FROM turmas")->fetch_all(MYSQLI_ASSOC);
        $dias_semana = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'];
        $stmt_horario = $conn->prepare("INSERT INTO horarios (id_turma, dia_semana) VALUES (?, ?)");

        foreach ($turmas as $turma) {
            foreach ($dias_semana as $dia) {
                $stmt_horario->bind_param("is", $turma['id_turma'], $dia);
                $stmt_horario->execute();
            }
        }
        $stmt_horario->close();

        // 3. Inicia o processo de alocação
        $horarios_vagos = $conn->query("
            SELECT h.id_horario, h.dia_semana, t.id_turno, t.id_uc
            FROM horarios h
            JOIN turmas t ON h.id_turma = t.id_turma
            WHERE h.id_professor_alocado IS NULL
            ORDER BY t.id_turno, h.dia_semana
        ")->fetch_all(MYSQLI_ASSOC);

        $professores_alocados_por_turno_dia = []; // Controla conflitos: [dia][turno][id_professor] = true

        $stmt_update_horario = $conn->prepare("UPDATE horarios SET id_professor_alocado = ? WHERE id_horario = ?");

        foreach ($horarios_vagos as $horario) {
            $id_horario = $horario['id_horario'];
            $dia = $horario['dia_semana'];
            $id_turno = $horario['id_turno'];
            $id_uc = $horario['id_uc'];

            // Inicializa o array de controle de conflito para o dia, se não existir
            if (!isset($professores_alocados_por_turno_dia[$dia])) {
                $professores_alocados_por_turno_dia[$dia] = [];
            }
            // E também para o turno dentro do dia
            if (!isset($professores_alocados_por_turno_dia[$dia][$id_turno])) {
                $professores_alocados_por_turno_dia[$dia][$id_turno] = [];
            }

            // 4. Encontra o melhor professor para este horário
            // O melhor é quem tem a competência para a UC, está disponível no dia/turno e ainda não foi alocado neste dia.
            // Ordena por nível de competência (N3 > N2 > N1 > N0) para pegar o mais qualificado.
            $sql_find_prof = "
                SELECT p.id_professor
                FROM professores p
                -- Join para verificar competência na UC
                JOIN professores_competencias pc ON p.id_professor = pc.id_professor
                JOIN competencias c ON pc.id_competencia = c.id_competencia
                -- Join para verificar disponibilidade no dia e turno
                JOIN professores_disponibilidade pd ON p.id_professor = pd.id_professor
                WHERE c.id_uc = ? 
                  AND pd.dia_semana = ?
                  AND pd.id_turno = ?
                ORDER BY FIELD(pc.nivel_competencia, 'N3', 'N2', 'N1', 'N0')
            ";
            
            $stmt_find_prof = $conn->prepare($sql_find_prof);
            $stmt_find_prof->bind_param("isi", $id_uc, $dia, $id_turno);
            $stmt_find_prof->execute();
            $candidatos = $stmt_find_prof->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_find_prof->close();

            // 5. Aloca o primeiro candidato que não tenha conflito
            foreach ($candidatos as $candidato) {
                $id_professor_candidato = $candidato['id_professor'];
                // Se o professor ainda não foi alocado neste dia, aloca ele.
                if (!isset($professores_alocados_por_turno_dia[$dia][$id_turno][$id_professor_candidato])) {
                    $stmt_update_horario->bind_param("ii", $id_professor_candidato, $id_horario);
                    $stmt_update_horario->execute();
                    
                    // Marca o professor como ocupado para este dia para evitar dupla alocação
                    $professores_alocados_por_turno_dia[$dia][$id_turno][$id_professor_candidato] = true;
                    break; // Passa para o próximo horário vago
                }
            }
        }
        $stmt_update_horario->close();

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        // Você pode adicionar uma mensagem de erro aqui se desejar
    }
}

// AÇÃO: Alocar todos os professores
if (isset($_POST['alocar'])) {
    alocarProfessores($conn);
}

// Redireciona de volta para a agenda para ver o resultado
header('Location: agenda.php');
exit;