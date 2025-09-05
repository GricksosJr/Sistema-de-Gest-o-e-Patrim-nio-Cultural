-- Backup gerado em: 2025-09-05 13:25:02
-- Gerado por: Sistema de Gestão de Patrimônio
SET FOREIGN_KEY_CHECKS=0;

--
-- Estrutura para tabela `categorias_patrimonio`
--
CREATE TABLE `categorias_patrimonio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para tabela `categorias_patrimonio`
--
INSERT INTO `categorias_patrimonio` (`id`, `nome`, `descricao`, `data_criacao`) VALUES ('1', 'Monumentos Históricos', 'Construções e estruturas de valor histórico', '2025-08-22 15:30:47');
INSERT INTO `categorias_patrimonio` (`id`, `nome`, `descricao`, `data_criacao`) VALUES ('2', 'Arte e Artesanato', 'Obras de arte e produtos artesanais tradicionais', '2025-08-22 15:30:47');
INSERT INTO `categorias_patrimonio` (`id`, `nome`, `descricao`, `data_criacao`) VALUES ('3', 'Música e Dança', 'Expressões musicais e dancísticas tradicionais', '2025-08-22 15:30:47');
INSERT INTO `categorias_patrimonio` (`id`, `nome`, `descricao`, `data_criacao`) VALUES ('4', 'Línguas e Literatura', 'Idiomas locais e obras literárias', '2025-08-22 15:30:47');
INSERT INTO `categorias_patrimonio` (`id`, `nome`, `descricao`, `data_criacao`) VALUES ('5', 'Tradições Orais', 'Contos, lendas e tradições passadas oralmente', '2025-08-22 15:30:47');
INSERT INTO `categorias_patrimonio` (`id`, `nome`, `descricao`, `data_criacao`) VALUES ('6', 'Arquitectura Tradicional', 'Habitações e construções tradicionais moçambicanas', '2025-08-23 18:54:48');
INSERT INTO `categorias_patrimonio` (`id`, `nome`, `descricao`, `data_criacao`) VALUES ('7', 'Instrumentos Musicais', 'Instrumentos musicais tradicionais e étnicos', '2025-08-23 18:54:48');
INSERT INTO `categorias_patrimonio` (`id`, `nome`, `descricao`, `data_criacao`) VALUES ('8', 'Rituais e Cerimónias', 'Práticas rituais e cerimónias tradicionais', '2025-08-23 18:54:48');
INSERT INTO `categorias_patrimonio` (`id`, `nome`, `descricao`, `data_criacao`) VALUES ('9', 'Gastronomia Tradicional', 'Pratos e técnicas culinárias tradicionais', '2025-08-23 18:54:48');
INSERT INTO `categorias_patrimonio` (`id`, `nome`, `descricao`, `data_criacao`) VALUES ('10', 'Medicina Tradicional', 'Conhecimentos e práticas de medicina tradicional', '2025-08-23 18:54:48');
INSERT INTO `categorias_patrimonio` (`id`, `nome`, `descricao`, `data_criacao`) VALUES ('11', 'Jogos e Desportos Tradicionais', 'Jogos e modalidades desportivas tradicionais', '2025-08-23 18:54:48');
INSERT INTO `categorias_patrimonio` (`id`, `nome`, `descricao`, `data_criacao`) VALUES ('12', 'Festivais e Celebrações', 'Festivais comunitários e celebrações tradicionais', '2025-08-23 18:54:48');
INSERT INTO `categorias_patrimonio` (`id`, `nome`, `descricao`, `data_criacao`) VALUES ('13', 'Sítios Arqueológicos', 'Locais de importância arqueológica', '2025-08-23 18:54:48');
INSERT INTO `categorias_patrimonio` (`id`, `nome`, `descricao`, `data_criacao`) VALUES ('14', 'Património Colonial', 'Edifícios e estruturas do período colonial', '2025-08-23 18:54:48');
INSERT INTO `categorias_patrimonio` (`id`, `nome`, `descricao`, `data_criacao`) VALUES ('15', 'Arte Rupestre', 'Pinturas e gravações em rochas e cavernas', '2025-08-23 18:54:48');

--
-- Estrutura para tabela `patrimonio`
--
CREATE TABLE `patrimonio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_registo` varchar(50) NOT NULL,
  `nome` varchar(200) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `tipo_patrimonio` enum('material','imaterial') NOT NULL,
  `descricao` text NOT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `distrito` varchar(100) DEFAULT NULL,
  `localidade` varchar(100) DEFAULT NULL,
  `coordenadas_gps` varchar(100) DEFAULT NULL,
  `periodo_historico` varchar(100) DEFAULT NULL,
  `data_criacao_aproximada` varchar(100) DEFAULT NULL,
  `origem` varchar(200) DEFAULT NULL,
  `estado_conservacao` enum('excelente','bom','regular','mau','critico') NOT NULL,
  `observacoes_estado` text DEFAULT NULL,
  `significado_cultural` text DEFAULT NULL,
  `valor_historico` text DEFAULT NULL,
  `relevancia_comunitaria` text DEFAULT NULL,
  `materiais_construcao` text DEFAULT NULL,
  `tecnicas_utilizadas` text DEFAULT NULL,
  `dimensoes` varchar(200) DEFAULT NULL,
  `peso` varchar(50) DEFAULT NULL,
  `praticantes` text DEFAULT NULL,
  `frequencia_pratica` varchar(100) DEFAULT NULL,
  `rituais_associados` text DEFAULT NULL,
  `conhecimentos_tradicionais` text DEFAULT NULL,
  `foto_principal` varchar(255) DEFAULT NULL,
  `fotografias` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`fotografias`)),
  `videos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`videos`)),
  `documentos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documentos`)),
  `gravacoes_audio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gravacoes_audio`)),
  `proprietario` varchar(200) DEFAULT NULL,
  `gestor_responsavel` varchar(200) DEFAULT NULL,
  `contacto_responsavel` varchar(200) DEFAULT NULL,
  `acesso_publico` tinyint(1) DEFAULT 0,
  `horario_visita` varchar(200) DEFAULT NULL,
  `restricoes_acesso` text DEFAULT NULL,
  `ameacas_identificadas` text DEFAULT NULL,
  `nivel_risco` enum('baixo','medio','alto','critico') DEFAULT 'baixo',
  `medidas_protecao` text DEFAULT NULL,
  `classificacao_oficial` enum('monumento_nacional','bem_interesse_cultural','bem_relevante','sem_classificacao') DEFAULT 'sem_classificacao',
  `data_classificacao` date DEFAULT NULL,
  `entidade_classificadora` varchar(200) DEFAULT NULL,
  `registado_por` int(11) NOT NULL,
  `data_registo` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_ultima_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('ativo','inativo','em_analise','pendente') DEFAULT 'ativo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_registo` (`codigo_registo`),
  KEY `registado_por` (`registado_por`),
  KEY `idx_codigo_registo` (`codigo_registo`),
  KEY `idx_categoria` (`categoria_id`),
  KEY `idx_provincia` (`provincia`),
  KEY `idx_tipo` (`tipo_patrimonio`),
  KEY `idx_estado` (`estado_conservacao`),
  KEY `idx_classificacao` (`classificacao_oficial`),
  CONSTRAINT `patrimonio_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_patrimonio` (`id`),
  CONSTRAINT `patrimonio_ibfk_2` FOREIGN KEY (`registado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para tabela `patrimonio`
--
INSERT INTO `patrimonio` (`id`, `codigo_registo`, `nome`, `categoria_id`, `tipo_patrimonio`, `descricao`, `provincia`, `distrito`, `localidade`, `coordenadas_gps`, `periodo_historico`, `data_criacao_aproximada`, `origem`, `estado_conservacao`, `observacoes_estado`, `significado_cultural`, `valor_historico`, `relevancia_comunitaria`, `materiais_construcao`, `tecnicas_utilizadas`, `dimensoes`, `peso`, `praticantes`, `frequencia_pratica`, `rituais_associados`, `conhecimentos_tradicionais`, `foto_principal`, `fotografias`, `videos`, `documentos`, `gravacoes_audio`, `proprietario`, `gestor_responsavel`, `contacto_responsavel`, `acesso_publico`, `horario_visita`, `restricoes_acesso`, `ameacas_identificadas`, `nivel_risco`, `medidas_protecao`, `classificacao_oficial`, `data_classificacao`, `entidade_classificadora`, `registado_por`, `data_registo`, `data_ultima_atualizacao`, `status`) VALUES ('1', 'PCM-001-2024', 'Fortaleza de Maputo', '1', 'material', 'Fortaleza histórica construída pelos portugueses no século XVIII, importante marco da história colonial de Moçambique.', 'Maputo', 'Maputo', NULL, NULL, NULL, NULL, NULL, 'bom', NULL, 'Símbolo da resistência e da história de Maputo, importante ponto turístico e cultural da cidade.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, 'baixo', NULL, 'sem_classificacao', NULL, NULL, '1', '2025-08-23 18:54:48', '2025-08-23 18:54:48', 'ativo');
INSERT INTO `patrimonio` (`id`, `codigo_registo`, `nome`, `categoria_id`, `tipo_patrimonio`, `descricao`, `provincia`, `distrito`, `localidade`, `coordenadas_gps`, `periodo_historico`, `data_criacao_aproximada`, `origem`, `estado_conservacao`, `observacoes_estado`, `significado_cultural`, `valor_historico`, `relevancia_comunitaria`, `materiais_construcao`, `tecnicas_utilizadas`, `dimensoes`, `peso`, `praticantes`, `frequencia_pratica`, `rituais_associados`, `conhecimentos_tradicionais`, `foto_principal`, `fotografias`, `videos`, `documentos`, `gravacoes_audio`, `proprietario`, `gestor_responsavel`, `contacto_responsavel`, `acesso_publico`, `horario_visita`, `restricoes_acesso`, `ameacas_identificadas`, `nivel_risco`, `medidas_protecao`, `classificacao_oficial`, `data_classificacao`, `entidade_classificadora`, `registado_por`, `data_registo`, `data_ultima_atualizacao`, `status`) VALUES ('2', 'PCM-002-2024', 'Dança Tradicional Makhuwa', '3', 'imaterial', 'Dança tradicional da etnia Makhuwa, praticada em cerimónias de iniciação e festivais comunitários.', 'Nampula', 'Nampula', NULL, NULL, NULL, NULL, NULL, 'bom', NULL, 'Expressão cultural fundamental da identidade Makhuwa, transmitindo valores e tradições ancestrais.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, 'baixo', NULL, 'sem_classificacao', NULL, NULL, '1', '2025-08-23 18:54:48', '2025-08-23 18:54:48', 'ativo');
INSERT INTO `patrimonio` (`id`, `codigo_registo`, `nome`, `categoria_id`, `tipo_patrimonio`, `descricao`, `provincia`, `distrito`, `localidade`, `coordenadas_gps`, `periodo_historico`, `data_criacao_aproximada`, `origem`, `estado_conservacao`, `observacoes_estado`, `significado_cultural`, `valor_historico`, `relevancia_comunitaria`, `materiais_construcao`, `tecnicas_utilizadas`, `dimensoes`, `peso`, `praticantes`, `frequencia_pratica`, `rituais_associados`, `conhecimentos_tradicionais`, `foto_principal`, `fotografias`, `videos`, `documentos`, `gravacoes_audio`, `proprietario`, `gestor_responsavel`, `contacto_responsavel`, `acesso_publico`, `horario_visita`, `restricoes_acesso`, `ameacas_identificadas`, `nivel_risco`, `medidas_protecao`, `classificacao_oficial`, `data_classificacao`, `entidade_classificadora`, `registado_por`, `data_registo`, `data_ultima_atualizacao`, `status`) VALUES ('3', 'PCM-003-2024', 'Casa de Ferro (Casa do Ferro)', '1', 'material', 'Edifício histórico em Maputo, projetado por Gustave Eiffel, exemplo único da arquitetura em ferro na região.', 'Maputo', 'Maputo', NULL, NULL, NULL, NULL, NULL, 'regular', NULL, 'Símbolo da arquitetura colonial e da engenharia do século XIX em Moçambique.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[{\"nome_original\":\"Gricksos Jr.png\",\"nome_arquivo\":\"foto_3_68b7ffa4d88f7.png\",\"caminho\":\"uploads\\/fotos\\/foto_3_68b7ffa4d88f7.png\",\"tamanho\":1083409,\"tipo\":\"png\",\"descricao\":\"\",\"data_upload\":\"2025-09-03 10:43:16\",\"usuario_upload\":6},{\"nome_original\":\"Captura de Tela (60).png\",\"nome_arquivo\":\"foto_3_68b803377c384.png\",\"caminho\":\"uploads\\/fotos\\/foto_3_68b803377c384.png\",\"tamanho\":146924,\"tipo\":\"png\",\"descricao\":\"\",\"data_upload\":\"2025-09-03 10:58:31\",\"usuario_upload\":6}]', NULL, '[{\"nome_original\":\"De GricksosJr para Marginal.pdf\",\"nome_arquivo\":\"documento_3_68b7ff899adb7.pdf\",\"caminho\":\"uploads\\/documentos\\/documento_3_68b7ff899adb7.pdf\",\"tamanho\":423444,\"tipo\":\"pdf\",\"descricao\":\"\",\"data_upload\":\"2025-09-03 10:42:49\",\"usuario_upload\":6}]', NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL, 'baixo', NULL, 'sem_classificacao', NULL, NULL, '1', '2025-08-23 18:54:48', '2025-09-03 10:58:31', 'ativo');
INSERT INTO `patrimonio` (`id`, `codigo_registo`, `nome`, `categoria_id`, `tipo_patrimonio`, `descricao`, `provincia`, `distrito`, `localidade`, `coordenadas_gps`, `periodo_historico`, `data_criacao_aproximada`, `origem`, `estado_conservacao`, `observacoes_estado`, `significado_cultural`, `valor_historico`, `relevancia_comunitaria`, `materiais_construcao`, `tecnicas_utilizadas`, `dimensoes`, `peso`, `praticantes`, `frequencia_pratica`, `rituais_associados`, `conhecimentos_tradicionais`, `foto_principal`, `fotografias`, `videos`, `documentos`, `gravacoes_audio`, `proprietario`, `gestor_responsavel`, `contacto_responsavel`, `acesso_publico`, `horario_visita`, `restricoes_acesso`, `ameacas_identificadas`, `nivel_risco`, `medidas_protecao`, `classificacao_oficial`, `data_classificacao`, `entidade_classificadora`, `registado_por`, `data_registo`, `data_ultima_atualizacao`, `status`) VALUES ('4', 'PCM-004-2025', 'The GOAT Show', '1', 'imaterial', 'O primeiro show a solo do cantor Hernâni da Ssilva', 'Maputo', 'Maputo', 'Campo do Maxaque', '55248121128448', 'XXI', '2025', '', 'excelente', '', 'Impulsionar o Rap moçambicano', 'De extrema importância para o HIP HOP Moçambicano', 'Rap Moz', '', '', '', '', 'Artistas', 'Diariamente', '', '', NULL, NULL, NULL, NULL, NULL, 'Hernani da Silva Mudanisse', 'CVS', '865121136', '1', '', '', '', 'baixo', '', 'bem_interesse_cultural', '2025-08-23', 'Povo Moçmabicano', '5', '2025-08-28 21:51:01', '2025-09-03 12:57:54', 'ativo');
INSERT INTO `patrimonio` (`id`, `codigo_registo`, `nome`, `categoria_id`, `tipo_patrimonio`, `descricao`, `provincia`, `distrito`, `localidade`, `coordenadas_gps`, `periodo_historico`, `data_criacao_aproximada`, `origem`, `estado_conservacao`, `observacoes_estado`, `significado_cultural`, `valor_historico`, `relevancia_comunitaria`, `materiais_construcao`, `tecnicas_utilizadas`, `dimensoes`, `peso`, `praticantes`, `frequencia_pratica`, `rituais_associados`, `conhecimentos_tradicionais`, `foto_principal`, `fotografias`, `videos`, `documentos`, `gravacoes_audio`, `proprietario`, `gestor_responsavel`, `contacto_responsavel`, `acesso_publico`, `horario_visita`, `restricoes_acesso`, `ameacas_identificadas`, `nivel_risco`, `medidas_protecao`, `classificacao_oficial`, `data_classificacao`, `entidade_classificadora`, `registado_por`, `data_registo`, `data_ultima_atualizacao`, `status`) VALUES ('5', 'PCM-005-2025', 'Igreja Católica de Lichinga', '1', 'material', 'Diocese da Igreja Católica em Lichinga', 'Niassa', 'Lichinga', '', '', '', '', '', 'bom', '', '', '', '', '', '', '', '', '', '', '', '', 'patrimonio_68b6ae8588b02.jpeg', NULL, NULL, NULL, NULL, '', '', '833695552', '1', '24/24', 'Nhenhuma restrição', '', 'baixo', '', 'monumento_nacional', '2025-09-02', '', '5', '2025-09-02 10:44:53', '2025-09-03 12:39:37', 'ativo');
INSERT INTO `patrimonio` (`id`, `codigo_registo`, `nome`, `categoria_id`, `tipo_patrimonio`, `descricao`, `provincia`, `distrito`, `localidade`, `coordenadas_gps`, `periodo_historico`, `data_criacao_aproximada`, `origem`, `estado_conservacao`, `observacoes_estado`, `significado_cultural`, `valor_historico`, `relevancia_comunitaria`, `materiais_construcao`, `tecnicas_utilizadas`, `dimensoes`, `peso`, `praticantes`, `frequencia_pratica`, `rituais_associados`, `conhecimentos_tradicionais`, `foto_principal`, `fotografias`, `videos`, `documentos`, `gravacoes_audio`, `proprietario`, `gestor_responsavel`, `contacto_responsavel`, `acesso_publico`, `horario_visita`, `restricoes_acesso`, `ameacas_identificadas`, `nivel_risco`, `medidas_protecao`, `classificacao_oficial`, `data_classificacao`, `entidade_classificadora`, `registado_por`, `data_registo`, `data_ultima_atualizacao`, `status`) VALUES ('6', 'PCM-006-2025', 'Liderança Feminina no Estado Mataaka', '4', 'material', 'Mitos e poderes da Rainha Acivaanjila de Majuuni', 'Niassa', 'Lichinga', NULL, NULL, 'XIX-XX', NULL, NULL, 'excelente', NULL, 'Importante para a literatura moçambicana', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'patrimonio_68b95999dc4da.PNG', '[{\"nome_original\":\"Gricksos Jr.png\",\"nome_arquivo\":\"foto_6_68b9afb4bf8c3.png\",\"caminho\":\"uploads\\/fotos\\/foto_6_68b9afb4bf8c3.png\",\"tamanho\":1083409,\"tipo\":\"png\",\"descricao\":\"\",\"data_upload\":\"2025-09-04 17:26:44\",\"usuario_upload\":5},{\"nome_original\":\"Capturar.PNG\",\"nome_arquivo\":\"foto_6_68b9afc2e3294.png\",\"caminho\":\"uploads\\/fotos\\/foto_6_68b9afc2e3294.png\",\"tamanho\":25056,\"tipo\":\"png\",\"descricao\":\"\",\"data_upload\":\"2025-09-04 17:26:58\",\"usuario_upload\":5},{\"nome_original\":\"111.JPG\",\"nome_arquivo\":\"foto_6_68b9afd39e059.jpg\",\"caminho\":\"uploads\\/fotos\\/foto_6_68b9afd39e059.jpg\",\"tamanho\":22108,\"tipo\":\"jpg\",\"descricao\":\"\",\"data_upload\":\"2025-09-04 17:27:15\",\"usuario_upload\":5}]', '[{\"nome_original\":\"MASSUKOS - NIASSA II.mp4\",\"nome_arquivo\":\"video_6_68b9afefcb840.mp4\",\"caminho\":\"uploads\\/videos\\/video_6_68b9afefcb840.mp4\",\"tamanho\":9838075,\"tipo\":\"mp4\",\"descricao\":\"\",\"data_upload\":\"2025-09-04 17:27:43\",\"usuario_upload\":5}]', '[{\"nome_original\":\"Lista para Aquisi\\u00e7\\u00e3o.docx\",\"nome_arquivo\":\"documento_6_68b9b0026fff1.docx\",\"caminho\":\"uploads\\/documentos\\/documento_6_68b9b0026fff1.docx\",\"tamanho\":51919,\"tipo\":\"docx\",\"descricao\":\"\",\"data_upload\":\"2025-09-04 17:28:02\",\"usuario_upload\":5}]', '[{\"nome_original\":\"dont_cry_no_more_._supernatural_mp3_76503.mp3\",\"nome_arquivo\":\"audio_6_68b9b02b0c789.mp3\",\"caminho\":\"uploads\\/audios\\/audio_6_68b9b02b0c789.mp3\",\"tamanho\":5176885,\"tipo\":\"mp3\",\"descricao\":\"\",\"data_upload\":\"2025-09-04 17:28:43\",\"usuario_upload\":5}]', 'Manuel Vene', 'Mapeta Editora', '865121136', '1', NULL, NULL, NULL, 'baixo', NULL, 'bem_interesse_cultural', '2025-09-04', NULL, '5', '2025-09-04 11:19:21', '2025-09-04 17:28:43', 'ativo');

--
-- Estrutura para tabela `patrimonio_historico`
--
CREATE TABLE `patrimonio_historico` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patrimonio_id` int(11) NOT NULL,
  `campo_alterado` varchar(100) NOT NULL,
  `valor_anterior` text DEFAULT NULL,
  `valor_novo` text DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_alteracao` timestamp NOT NULL DEFAULT current_timestamp(),
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_patrimonio` (`patrimonio_id`),
  KEY `idx_data` (`data_alteracao`),
  CONSTRAINT `patrimonio_historico_ibfk_1` FOREIGN KEY (`patrimonio_id`) REFERENCES `patrimonio` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patrimonio_historico_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para tabela `patrimonio_historico`
--
INSERT INTO `patrimonio_historico` (`id`, `patrimonio_id`, `campo_alterado`, `valor_anterior`, `valor_novo`, `usuario_id`, `data_alteracao`, `observacoes`) VALUES ('1', '5', 'atualizacao_geral', 'Dados anteriores', 'Dados atualizados', '6', '2025-09-03 10:31:31', 'Atualização completa do registo');
INSERT INTO `patrimonio_historico` (`id`, `patrimonio_id`, `campo_alterado`, `valor_anterior`, `valor_novo`, `usuario_id`, `data_alteracao`, `observacoes`) VALUES ('2', '3', 'documentos', 'Arquivo adicionado', 'De GricksosJr para Marginal.pdf', '6', '2025-09-03 10:42:49', 'Upload de documento: De GricksosJr para Marginal.pdf');
INSERT INTO `patrimonio_historico` (`id`, `patrimonio_id`, `campo_alterado`, `valor_anterior`, `valor_novo`, `usuario_id`, `data_alteracao`, `observacoes`) VALUES ('3', '3', 'fotografias', 'Arquivo adicionado', 'Gricksos Jr.png', '6', '2025-09-03 10:43:16', 'Upload de foto: Gricksos Jr.png');
INSERT INTO `patrimonio_historico` (`id`, `patrimonio_id`, `campo_alterado`, `valor_anterior`, `valor_novo`, `usuario_id`, `data_alteracao`, `observacoes`) VALUES ('4', '3', 'fotografias', 'Arquivo adicionado', 'Captura de Tela (60).png', '6', '2025-09-03 10:58:31', 'Upload de foto: Captura de Tela (60).png');
INSERT INTO `patrimonio_historico` (`id`, `patrimonio_id`, `campo_alterado`, `valor_anterior`, `valor_novo`, `usuario_id`, `data_alteracao`, `observacoes`) VALUES ('5', '5', 'atualizacao_geral', 'Dados anteriores', 'Dados atualizados', '5', '2025-09-03 12:39:37', 'Atualização completa do registo');
INSERT INTO `patrimonio_historico` (`id`, `patrimonio_id`, `campo_alterado`, `valor_anterior`, `valor_novo`, `usuario_id`, `data_alteracao`, `observacoes`) VALUES ('6', '4', 'atualizacao_geral', 'Dados anteriores', 'Dados atualizados', '5', '2025-09-03 12:57:54', 'Atualização completa do registo');
INSERT INTO `patrimonio_historico` (`id`, `patrimonio_id`, `campo_alterado`, `valor_anterior`, `valor_novo`, `usuario_id`, `data_alteracao`, `observacoes`) VALUES ('7', '6', 'fotografias', 'Arquivo adicionado', 'Gricksos Jr.png', '5', '2025-09-04 17:26:44', 'Upload de foto: Gricksos Jr.png');
INSERT INTO `patrimonio_historico` (`id`, `patrimonio_id`, `campo_alterado`, `valor_anterior`, `valor_novo`, `usuario_id`, `data_alteracao`, `observacoes`) VALUES ('8', '6', 'fotografias', 'Arquivo adicionado', 'Capturar.PNG', '5', '2025-09-04 17:26:59', 'Upload de foto: Capturar.PNG');
INSERT INTO `patrimonio_historico` (`id`, `patrimonio_id`, `campo_alterado`, `valor_anterior`, `valor_novo`, `usuario_id`, `data_alteracao`, `observacoes`) VALUES ('9', '6', 'fotografias', 'Arquivo adicionado', '111.JPG', '5', '2025-09-04 17:27:15', 'Upload de foto: 111.JPG');
INSERT INTO `patrimonio_historico` (`id`, `patrimonio_id`, `campo_alterado`, `valor_anterior`, `valor_novo`, `usuario_id`, `data_alteracao`, `observacoes`) VALUES ('10', '6', 'videos', 'Arquivo adicionado', 'MASSUKOS - NIASSA II.mp4', '5', '2025-09-04 17:27:43', 'Upload de video: MASSUKOS - NIASSA II.mp4');
INSERT INTO `patrimonio_historico` (`id`, `patrimonio_id`, `campo_alterado`, `valor_anterior`, `valor_novo`, `usuario_id`, `data_alteracao`, `observacoes`) VALUES ('11', '6', 'documentos', 'Arquivo adicionado', 'Lista para Aquisição.docx', '5', '2025-09-04 17:28:02', 'Upload de documento: Lista para Aquisição.docx');
INSERT INTO `patrimonio_historico` (`id`, `patrimonio_id`, `campo_alterado`, `valor_anterior`, `valor_novo`, `usuario_id`, `data_alteracao`, `observacoes`) VALUES ('12', '6', 'gravacoes_audio', 'Arquivo adicionado', 'dont_cry_no_more_._supernatural_mp3_76503.mp3', '5', '2025-09-04 17:28:43', 'Upload de audio: dont_cry_no_more_._supernatural_mp3_76503.mp3');

--
-- Estrutura para tabela `patrimonio_pessoas`
--
CREATE TABLE `patrimonio_pessoas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patrimonio_id` int(11) NOT NULL,
  `nome` varchar(200) NOT NULL,
  `tipo_relacao` enum('criador','guardião','praticante','conhecedor','proprietario','outro') NOT NULL,
  `contacto` varchar(200) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_patrimonio` (`patrimonio_id`),
  CONSTRAINT `patrimonio_pessoas_ibfk_1` FOREIGN KEY (`patrimonio_id`) REFERENCES `patrimonio` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para tabela `patrimonio_pessoas`
--

--
-- Estrutura para tabela `sessoes`
--
CREATE TABLE `sessoes` (
  `id` varchar(128) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `data_inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_fim` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `sessoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para tabela `sessoes`
--

--
-- Estrutura para tabela `user_activity_log`
--
CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_activity` (`user_id`,`created_at`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para tabela `user_activity_log`
--

--
-- Estrutura para tabela `usuarios`
--
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo_usuario` enum('admin','funcionario') NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1,
  `status` enum('ativo','desativado') DEFAULT 'ativo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para tabela `usuarios`
--
INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `tipo_usuario`, `data_criacao`, `data_atualizacao`, `ativo`, `status`) VALUES ('1', 'Administrador do Sistema', 'admin@patrimonio.gov.mz', 'e10adc3949ba59abbe56e057f20f883e', 'admin', '2025-08-22 15:30:44', '2025-08-22 15:30:44', '1', 'ativo');
INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `tipo_usuario`, `data_criacao`, `data_atualizacao`, `ativo`, `status`) VALUES ('5', 'Augusto Junior', 'augustojunior178@gmail.com', 'e10adc3949ba59abbe56e057f20f883e', 'admin', '2025-08-22 16:38:30', '2025-08-22 16:38:30', '1', 'ativo');
INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `tipo_usuario`, `data_criacao`, `data_atualizacao`, `ativo`, `status`) VALUES ('6', 'Manuel Vene', 'manuelvene@gmail.com', 'e10adc3949ba59abbe56e057f20f883e', 'funcionario', '2025-08-22 16:39:12', '2025-08-28 22:01:27', '1', 'ativo');
INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `tipo_usuario`, `data_criacao`, `data_atualizacao`, `ativo`, `status`) VALUES ('7', 'admin', 'admin@gamil.com', '$2y$10$NFCQ/SuBCjHf49zPedZxquN1I5gpAXFRP8I6q8B4IR77zcazYYbq6', 'admin', '2025-08-28 21:58:21', '2025-08-28 21:58:21', '1', 'ativo');

SET FOREIGN_KEY_CHECKS=1;
