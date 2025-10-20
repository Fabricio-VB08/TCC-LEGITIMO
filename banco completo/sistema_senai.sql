-- Desativa a verificação de chaves estrangeiras para evitar erros ao apagar tabelas na ordem errada.
SET FOREIGN_KEY_CHECKS=0;

-- Reativa a verificação de chaves estrangeiras.
SET FOREIGN_KEY_CHECKS=1;

--
-- Estrutura para tabela `turnos`
--
CREATE TABLE `turnos` (
  `id_turno` int(11) NOT NULL AUTO_INCREMENT,
  `nome_turno` varchar(50) NOT NULL,
  PRIMARY KEY (`id_turno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Inserindo dados padrão para os turnos
INSERT INTO `turnos` (`id_turno`, `nome_turno`) VALUES (1, 'Manhã'), (2, 'Tarde'), (3, 'Noite');

--
-- Estrutura para tabela `professores`
--
CREATE TABLE `professores` (
  `id_professor` int(11) NOT NULL AUTO_INCREMENT,
  `nome_professor` varchar(255) NOT NULL,
  `email_professor` varchar(100) NOT NULL,
  PRIMARY KEY (`id_professor`),
  UNIQUE KEY `email_professor` (`email_professor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Estrutura para tabela `usuarios`
--
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo_usuario` enum('professor','administrador') NOT NULL,
  `id_professor` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  KEY `id_professor` (`id_professor`),
  CONSTRAINT `fk_usuario_professor` FOREIGN KEY (`id_professor`) REFERENCES `professores` (`id_professor`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Estrutura para tabela `unidades_curriculares` (Matérias)
--
CREATE TABLE `unidades_curriculares` (
  `id_uc` int(11) NOT NULL AUTO_INCREMENT,
  `nome_uc` varchar(255) NOT NULL,
  PRIMARY KEY (`id_uc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Estrutura para tabela `competencias`
--
CREATE TABLE `competencias` (
  `id_competencia` int(11) NOT NULL AUTO_INCREMENT,
  `nome_competencia` varchar(255) NOT NULL,
  `id_uc` int(11) NOT NULL,
  PRIMARY KEY (`id_competencia`),
  KEY `id_uc` (`id_uc`),
  CONSTRAINT `fk_competencia_uc` FOREIGN KEY (`id_uc`) REFERENCES `unidades_curriculares` (`id_uc`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Estrutura para tabela `professores_competencias`
--
CREATE TABLE `professores_competencias` (
  `id_professor_competencia` int(11) NOT NULL AUTO_INCREMENT,
  `id_professor` int(11) NOT NULL,
  `id_competencia` int(11) NOT NULL,
  `nivel_competencia` enum('N0','N1','N2','N3') NOT NULL,
  PRIMARY KEY (`id_professor_competencia`),
  UNIQUE KEY `idx_prof_comp` (`id_professor`,`id_competencia`),
  KEY `id_competencia` (`id_competencia`),
  CONSTRAINT `fk_pc_competencia` FOREIGN KEY (`id_competencia`) REFERENCES `competencias` (`id_competencia`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pc_professor` FOREIGN KEY (`id_professor`) REFERENCES `professores` (`id_professor`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Estrutura para tabela `professores_disponibilidade`
--
CREATE TABLE `professores_disponibilidade` (
  `id_disponibilidade` int(11) NOT NULL AUTO_INCREMENT,
  `id_professor` int(11) NOT NULL,
  `dia_semana` enum('segunda','terca','quarta','quinta','sexta','sabado') NOT NULL,
  `id_turno` int(11) NOT NULL,
  PRIMARY KEY (`id_disponibilidade`),
  UNIQUE KEY `idx_prof_dia_turno` (`id_professor`,`dia_semana`,`id_turno`),
  KEY `id_turno` (`id_turno`),
  CONSTRAINT `fk_disp_professor` FOREIGN KEY (`id_professor`) REFERENCES `professores` (`id_professor`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_disp_turno` FOREIGN KEY (`id_turno`) REFERENCES `turnos` (`id_turno`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
