-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 02/09/2025 às 15:38
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `patrimonio_cultural`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias_patrimonio`
--

CREATE TABLE `categorias_patrimonio` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categorias_patrimonio`
--

INSERT INTO `categorias_patrimonio` (`id`, `nome`, `descricao`, `data_criacao`) VALUES
(1, 'Monumentos Históricos', 'Construções e estruturas de valor histórico', '2025-08-22 13:30:47'),
(2, 'Arte e Artesanato', 'Obras de arte e produtos artesanais tradicionais', '2025-08-22 13:30:47'),
(3, 'Música e Dança', 'Expressões musicais e dancísticas tradicionais', '2025-08-22 13:30:47'),
(4, 'Línguas e Literatura', 'Idiomas locais e obras literárias', '2025-08-22 13:30:47'),
(5, 'Tradições Orais', 'Contos, lendas e tradições passadas oralmente', '2025-08-22 13:30:47'),
(6, 'Arquitectura Tradicional', 'Habitações e construções tradicionais moçambicanas', '2025-08-23 16:54:48'),
(7, 'Instrumentos Musicais', 'Instrumentos musicais tradicionais e étnicos', '2025-08-23 16:54:48'),
(8, 'Rituais e Cerimónias', 'Práticas rituais e cerimónias tradicionais', '2025-08-23 16:54:48'),
(9, 'Gastronomia Tradicional', 'Pratos e técnicas culinárias tradicionais', '2025-08-23 16:54:48'),
(10, 'Medicina Tradicional', 'Conhecimentos e práticas de medicina tradicional', '2025-08-23 16:54:48'),
(11, 'Jogos e Desportos Tradicionais', 'Jogos e modalidades desportivas tradicionais', '2025-08-23 16:54:48'),
(12, 'Festivais e Celebrações', 'Festivais comunitários e celebrações tradicionais', '2025-08-23 16:54:48'),
(13, 'Sítios Arqueológicos', 'Locais de importância arqueológica', '2025-08-23 16:54:48'),
(14, 'Património Colonial', 'Edifícios e estruturas do período colonial', '2025-08-23 16:54:48'),
(15, 'Arte Rupestre', 'Pinturas e gravações em rochas e cavernas', '2025-08-23 16:54:48');

-- --------------------------------------------------------

--
-- Estrutura para tabela `patrimonio`
--

CREATE TABLE `patrimonio` (
  `id` int(11) NOT NULL,
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
  `status` enum('ativo','inativo','em_analise','pendente') DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `patrimonio`
--

INSERT INTO `patrimonio` (`id`, `codigo_registo`, `nome`, `categoria_id`, `tipo_patrimonio`, `descricao`, `provincia`, `distrito`, `localidade`, `coordenadas_gps`, `periodo_historico`, `data_criacao_aproximada`, `origem`, `estado_conservacao`, `observacoes_estado`, `significado_cultural`, `valor_historico`, `relevancia_comunitaria`, `materiais_construcao`, `tecnicas_utilizadas`, `dimensoes`, `peso`, `praticantes`, `frequencia_pratica`, `rituais_associados`, `conhecimentos_tradicionais`, `foto_principal`, `fotografias`, `videos`, `documentos`, `gravacoes_audio`, `proprietario`, `gestor_responsavel`, `contacto_responsavel`, `acesso_publico`, `horario_visita`, `restricoes_acesso`, `ameacas_identificadas`, `nivel_risco`, `medidas_protecao`, `classificacao_oficial`, `data_classificacao`, `entidade_classificadora`, `registado_por`, `data_registo`, `data_ultima_atualizacao`, `status`) VALUES
(1, 'PCM-001-2024', 'Fortaleza de Maputo', 1, 'material', 'Fortaleza histórica construída pelos portugueses no século XVIII, importante marco da história colonial de Moçambique.', 'Maputo', 'Maputo', NULL, NULL, NULL, NULL, NULL, 'bom', NULL, 'Símbolo da resistência e da história de Maputo, importante ponto turístico e cultural da cidade.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'baixo', NULL, 'sem_classificacao', NULL, NULL, 1, '2025-08-23 16:54:48', '2025-08-23 16:54:48', 'ativo'),
(2, 'PCM-002-2024', 'Dança Tradicional Makhuwa', 3, 'imaterial', 'Dança tradicional da etnia Makhuwa, praticada em cerimónias de iniciação e festivais comunitários.', 'Nampula', 'Nampula', NULL, NULL, NULL, NULL, NULL, 'bom', NULL, 'Expressão cultural fundamental da identidade Makhuwa, transmitindo valores e tradições ancestrais.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'baixo', NULL, 'sem_classificacao', NULL, NULL, 1, '2025-08-23 16:54:48', '2025-08-23 16:54:48', 'ativo'),
(3, 'PCM-003-2024', 'Casa de Ferro (Casa do Ferro)', 1, 'material', 'Edifício histórico em Maputo, projetado por Gustave Eiffel, exemplo único da arquitetura em ferro na região.', 'Maputo', 'Maputo', NULL, NULL, NULL, NULL, NULL, 'regular', NULL, 'Símbolo da arquitetura colonial e da engenharia do século XIX em Moçambique.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'baixo', NULL, 'sem_classificacao', NULL, NULL, 1, '2025-08-23 16:54:48', '2025-08-23 16:54:48', 'ativo'),
(4, 'PCM-004-2025', 'The GOAT Show', 3, 'imaterial', 'O primeiro show a solo do cantor Hernâni da Ssilva', 'Maputo', 'Maputo', 'Campo do Maxaque', '55248121128448', 'XXI', '2025', NULL, 'excelente', NULL, 'Impulsionar o Rap moçambicano', 'De extrema importância para o HIP HOP Moçambicano', 'Rap Moz', NULL, NULL, NULL, NULL, 'Artistas', 'Diariamente', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Hernani da Silva Mudanisse', 'CVS', '865121136', 1, NULL, NULL, NULL, 'baixo', NULL, 'bem_interesse_cultural', '2025-08-23', 'Povo Moçmabicano', 5, '2025-08-28 19:51:01', '2025-08-28 19:51:01', 'ativo'),
(5, 'PCM-005-2025', 'Igreja Católica de Lichinga', 1, 'material', 'Diocese da Igreja Católica em Lichinga', 'Niassa', 'Lichinga', NULL, NULL, NULL, NULL, NULL, 'bom', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'patrimonio_68b6ae8588b02.jpeg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '24/24', NULL, NULL, 'baixo', NULL, 'monumento_nacional', '2025-09-02', NULL, 5, '2025-09-02 08:44:53', '2025-09-02 08:44:53', 'ativo');

-- --------------------------------------------------------

--
-- Estrutura para tabela `patrimonio_historico`
--

CREATE TABLE `patrimonio_historico` (
  `id` int(11) NOT NULL,
  `patrimonio_id` int(11) NOT NULL,
  `campo_alterado` varchar(100) NOT NULL,
  `valor_anterior` text DEFAULT NULL,
  `valor_novo` text DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_alteracao` timestamp NOT NULL DEFAULT current_timestamp(),
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `patrimonio_pessoas`
--

CREATE TABLE `patrimonio_pessoas` (
  `id` int(11) NOT NULL,
  `patrimonio_id` int(11) NOT NULL,
  `nome` varchar(200) NOT NULL,
  `tipo_relacao` enum('criador','guardião','praticante','conhecedor','proprietario','outro') NOT NULL,
  `contacto` varchar(200) DEFAULT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `sessoes`
--

CREATE TABLE `sessoes` (
  `id` varchar(128) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `data_inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_fim` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo_usuario` enum('admin','funcionario') NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1,
  `status` enum('ativo','desativado') DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `tipo_usuario`, `data_criacao`, `data_atualizacao`, `ativo`, `status`) VALUES
(1, 'Administrador do Sistema', 'admin@patrimonio.gov.mz', 'e10adc3949ba59abbe56e057f20f883e', 'admin', '2025-08-22 13:30:44', '2025-08-22 13:30:44', 1, 'ativo'),
(5, 'Augusto Junior', 'augustojunior178@gmail.com', 'e10adc3949ba59abbe56e057f20f883e', 'admin', '2025-08-22 14:38:30', '2025-08-22 14:38:30', 1, 'ativo'),
(6, 'Manuel Vene', 'manuelvene@gmail.com', 'e10adc3949ba59abbe56e057f20f883e', 'funcionario', '2025-08-22 14:39:12', '2025-08-28 20:01:27', 1, 'ativo'),
(7, 'admin', 'admin@gamil.com', '$2y$10$NFCQ/SuBCjHf49zPedZxquN1I5gpAXFRP8I6q8B4IR77zcazYYbq6', 'admin', '2025-08-28 19:58:21', '2025-08-28 19:58:21', 1, 'ativo');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categorias_patrimonio`
--
ALTER TABLE `categorias_patrimonio`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `patrimonio`
--
ALTER TABLE `patrimonio`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_registo` (`codigo_registo`),
  ADD KEY `registado_por` (`registado_por`),
  ADD KEY `idx_codigo_registo` (`codigo_registo`),
  ADD KEY `idx_categoria` (`categoria_id`),
  ADD KEY `idx_provincia` (`provincia`),
  ADD KEY `idx_tipo` (`tipo_patrimonio`),
  ADD KEY `idx_estado` (`estado_conservacao`),
  ADD KEY `idx_classificacao` (`classificacao_oficial`);

--
-- Índices de tabela `patrimonio_historico`
--
ALTER TABLE `patrimonio_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_patrimonio` (`patrimonio_id`),
  ADD KEY `idx_data` (`data_alteracao`);

--
-- Índices de tabela `patrimonio_pessoas`
--
ALTER TABLE `patrimonio_pessoas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patrimonio` (`patrimonio_id`);

--
-- Índices de tabela `sessoes`
--
ALTER TABLE `sessoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_activity` (`user_id`,`created_at`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categorias_patrimonio`
--
ALTER TABLE `categorias_patrimonio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `patrimonio`
--
ALTER TABLE `patrimonio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `patrimonio_historico`
--
ALTER TABLE `patrimonio_historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `patrimonio_pessoas`
--
ALTER TABLE `patrimonio_pessoas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `patrimonio`
--
ALTER TABLE `patrimonio`
  ADD CONSTRAINT `patrimonio_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_patrimonio` (`id`),
  ADD CONSTRAINT `patrimonio_ibfk_2` FOREIGN KEY (`registado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `patrimonio_historico`
--
ALTER TABLE `patrimonio_historico`
  ADD CONSTRAINT `patrimonio_historico_ibfk_1` FOREIGN KEY (`patrimonio_id`) REFERENCES `patrimonio` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patrimonio_historico_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `patrimonio_pessoas`
--
ALTER TABLE `patrimonio_pessoas`
  ADD CONSTRAINT `patrimonio_pessoas_ibfk_1` FOREIGN KEY (`patrimonio_id`) REFERENCES `patrimonio` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `sessoes`
--
ALTER TABLE `sessoes`
  ADD CONSTRAINT `sessoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
