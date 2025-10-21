-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 20/10/2025 às 17:28
-- Versão do servidor: 8.0.43-34
-- Versão do PHP: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `joaoco37_pedidos`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int NOT NULL,
  `tipo_cliente_id` int NOT NULL,
  `codigo_cliente` varchar(50) NOT NULL,
  `nome_cliente` varchar(150) NOT NULL,
  `cnpj` varchar(20) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `telefone_contato` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `endereco_entrega` text,
  `data_cadastro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id`, `tipo_cliente_id`, `codigo_cliente`, `nome_cliente`, `cnpj`, `cidade`, `estado`, `telefone_contato`, `email`, `endereco_entrega`, `data_cadastro`) VALUES
(1, 1, '5473', 'COMERCIAL TICAZO HIRATA SA', '55615538000283', 'Lins', 'SP', NULL, '', 'RUA TENENTE GOMES RIBEIRO,66  - Centro', '2025-10-13 17:24:17'),
(2, 1, '8552', 'CLAUDIO HENRIQUE HONORATO DA SILVA', '35573298000103', 'Lins', 'SP', NULL, '', 'AV. SÃO PAULO, 1410 - JARDIM GUANABARA', '2025-10-14 09:40:59'),
(3, 4, '1999', 'RUBENS APARECIDO CAMARA', '71165525887', 'Lins', 'SP', NULL, '', 'AV. JOSE ARIANO RODRIGUES, 535 - JARDIM ARIANO', '2025-10-14 09:50:57'),
(4, 4, '147', 'RUBENS APARECIDO CAMARA JUNIOR', '31203420846', 'Lins', 'SP', NULL, '', 'AV.PROFESSOR EUGENIO MARTINS RAMON, 58 - RESD.Fortaleza', '2025-10-14 09:50:16'),
(5, 3, '8805', 'PADARIA DELÍCIA DE LINS', '45482742000139', 'Lins', 'SP', NULL, '', 'RUA COMANDANTE SALGADO, 260 - VILA ALTA', '2025-10-14 09:52:03'),
(6, 3, '7212', 'SANCINETTI & SOZO LTDA', '12131424000174', 'Lins', 'SP', NULL, '', 'RUA LUIZ GEFFERSON MONTEIRO DA SILVA, 425 - JD BOM VIVER I', '2025-10-14 09:51:30'),
(7, 1, '8570', 'JULIANA DE OLIVEIRA MALAFAIA', '56805099000191', 'Lins', 'SP', NULL, '', 'RUA TOMAZ ANTONIO GONZAGA, 891 - JD. ARIANO', '2025-10-14 09:41:57'),
(8, 1, '1505', 'SUPERMERCADO CASTILHO DE CAFELANDIA LTDA', '04755895000125', 'Cafelândia', 'SP', NULL, '', 'RUA SÃO PAULO, 500 - CENTRO', '2025-10-14 09:42:29'),
(9, 1, '8598', 'EDIMAR GALVÃO DE OLIVEIRA', '47211009000105', 'Cafelândia', 'SP', NULL, '', 'AV. DILECTA, 284 - CENTRO SIMÕES', '2025-10-14 09:43:05'),
(10, 1, '8589', 'CONVENIENCIA REBUCCI LTDA', '53206614000190', 'Guaiçara', 'SP', NULL, '', 'VIA ACESSO HERMINIO PAIZAN, 1440 - CENTRO', '2025-10-14 09:43:39'),
(11, 1, '8592', 'CONVENIENCIA FORTALEZA LTDA', '53208663000162', 'Lins', 'SP', NULL, '', 'RUA PADRE EDUARDO REBOUÇAS DE CARVALHO, 60 - RESD. FORTALEZA', '2025-10-14 09:44:09'),
(12, 1, '8613', 'SUPERMERCADO NOVA CAFELANDIA LTDA', '01105237000108', 'Cafelândia', 'SP', NULL, '', 'AV. MARIA M TORRES, 169 - JD. N CAF', '2025-10-14 09:44:38'),
(13, 1, '8622', 'SUPERMERCADO CONFIANÇA LINS LT', '62004890000167', 'Lins', 'SP', NULL, '', 'RUA HIPOLITO ALVES DE NORONHA, 245 - PQ ALTO DE FÁTIMA', '2025-10-14 09:45:17'),
(14, 1, '1367', 'SUPERMERCADO BOM VIVER', '67673087000139', 'Lins', 'SP', NULL, '', 'RUA PROFESSOR JOAQUIM B. RODRIGUES, 82 - BOM VIVER II', '2025-10-14 09:45:48'),
(15, 1, '8643', 'JL SUPERMERCADO LTDA', '21148568000100', 'Lins', 'SP', NULL, '', 'RUA JOSE LINS DO REGO, 314 - VILA PERIN', '2025-10-14 09:46:19'),
(16, 1, '8661', 'SORVETERIA BRANCA DE NEVE DE LINS LTDA', '51663508000100', 'Lins', 'SP', NULL, '', 'RUA TREZE DE MAIO, 30 - CENTRO', '2025-10-14 09:46:59'),
(17, 1, '8696', 'M.G.C SUPERMERCADO LTDA', '50027197000120', 'Lins', 'SP', NULL, '', 'AV. DA SAUDADE , 751 - RIBEIRO', '2025-10-14 09:47:36'),
(18, 1, '8698', 'AMG SUPERMERCADO LDTA', '49885134000126', 'Lins', 'SP', NULL, '', 'AV. JOSE ARIANO RODRIGUES, 736 - JD. ARIANO', '2025-10-14 09:48:05'),
(19, 1, '8659', 'EMPORIO NATA DO CAMPO LTDA', '62352258000104', 'Lins', 'SP', NULL, '', 'RUA PROFESSOR EUGENIO MARTINS RAMON, 58 - RESD. FORTALEZA', '2025-10-14 09:48:32'),
(20, 1, '8730', 'CONVENIENCIA NSQP LTDA', '54387993000125', 'Lins', 'SP', NULL, '', 'RUA RODRIGUES ALVES, 240 - CENTRO', '2025-10-14 09:48:59'),
(21, 1, '8884', 'BISPO DISTRIBUIÇÃO DE PRODUTOS', '06880979000116', 'São José do Rio Preto', 'SP', NULL, '', 'ESTRADA MUNICIPAL JBF, S/N - BOA VISTA CASTILHO', '2025-10-14 09:49:43');

-- --------------------------------------------------------

--
-- Estrutura para tabela `clube_nata_membros`
--

CREATE TABLE `clube_nata_membros` (
  `id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `indicado_por_id` int DEFAULT NULL,
  `pontuacao` decimal(10,2) NOT NULL DEFAULT '0.00',
  `data_adesao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `clube_nata_recompensas`
--

CREATE TABLE `clube_nata_recompensas` (
  `id` int NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text,
  `custo_pontos` int NOT NULL,
  `status` enum('Ativo','Inativo') NOT NULL DEFAULT 'Ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `clube_nata_recompensas`
--

INSERT INTO `clube_nata_recompensas` (`id`, `nome`, `descricao`, `custo_pontos`, `status`) VALUES
(1, 'Desconto Fixo de 5% na Próxima Fatura', 'Válido para resgate por clientes que possuam mais de 100 pontos. O resgate gera um cupom de 5% de desconto que será aplicado na fatura de compra subsequente ao resgate, limitado a um valor total de compra de R$ 5.000,00. Excelente para grandes clientes!\r\n\r\nExportar para as Planilhas', 100, 'Ativo');

-- --------------------------------------------------------

--
-- Estrutura para tabela `clube_nata_resgates`
--

CREATE TABLE `clube_nata_resgates` (
  `id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `recompensa_id` int NOT NULL,
  `pontos_utilizados` int NOT NULL,
  `data_resgate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedores`
--

CREATE TABLE `fornecedores` (
  `id` int NOT NULL,
  `nome_fantasia` varchar(255) NOT NULL,
  `razao_social` varchar(255) DEFAULT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `contato` varchar(100) DEFAULT NULL,
  `data_cadastro` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `fornecedores`
--

INSERT INTO `fornecedores` (`id`, `nome_fantasia`, `razao_social`, `cnpj`, `contato`, `data_cadastro`) VALUES
(1, 'Nata do Campo', NULL, NULL, NULL, '2025-10-13 15:16:30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_venda`
--

CREATE TABLE `itens_venda` (
  `id` int NOT NULL,
  `venda_id` int NOT NULL,
  `produto_id` int NOT NULL,
  `quantidade` int NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `num_lote` varchar(50) DEFAULT NULL,
  `status_lote` enum('Liberado VDI','Liberado Todos','Bloqueado') DEFAULT 'Liberado Todos',
  `data_vencimento` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `itens_venda`
--

INSERT INTO `itens_venda` (`id`, `venda_id`, `produto_id`, `quantidade`, `preco_unitario`, `num_lote`, `status_lote`, `data_vencimento`) VALUES
(34, 19, 4, 10, 5.90, NULL, 'Liberado Todos', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `lotes_estoque`
--

CREATE TABLE `lotes_estoque` (
  `id` int NOT NULL,
  `produto_id` int NOT NULL,
  `fornecedor_id` int NOT NULL,
  `data_entrada` date NOT NULL,
  `num_lote` varchar(50) NOT NULL,
  `status_lote` varchar(50) NOT NULL,
  `data_vencimento` date DEFAULT NULL,
  `quantidade` decimal(10,3) NOT NULL,
  `saldo_atual` decimal(10,3) NOT NULL,
  `usuario_id` int NOT NULL,
  `observacao` text,
  `data_registro` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `lotes_estoque`
--

INSERT INTO `lotes_estoque` (`id`, `produto_id`, `fornecedor_id`, `data_entrada`, `num_lote`, `status_lote`, `data_vencimento`, `quantidade`, `saldo_atual`, `usuario_id`, `observacao`, `data_registro`) VALUES
(33, 4, 1, '2025-10-10', '10102025', 'Liberado Todos', '2025-10-20', 100.000, 100.000, 11, '', '2025-10-20 16:43:05');

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacao_estoque`
--

CREATE TABLE `movimentacao_estoque` (
  `id` int NOT NULL,
  `lote_id` int DEFAULT NULL,
  `produto_id` int NOT NULL,
  `tipo` enum('ENTRADA','SAIDA','AJUSTE') NOT NULL,
  `quantidade` decimal(10,3) NOT NULL,
  `data_movimentacao` date NOT NULL,
  `usuario_id` int NOT NULL,
  `observacao` text,
  `data_registro` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `movimentacao_estoque`
--

INSERT INTO `movimentacao_estoque` (`id`, `lote_id`, `produto_id`, `tipo`, `quantidade`, `data_movimentacao`, `usuario_id`, `observacao`, `data_registro`) VALUES
(27, 33, 4, 'ENTRADA', 100.000, '2025-10-10', 11, '', '2025-10-20 16:43:05');

-- --------------------------------------------------------

--
-- Estrutura para tabela `precos`
--

CREATE TABLE `precos` (
  `id` int NOT NULL,
  `produto_id` int NOT NULL,
  `tipo_cliente_id` int NOT NULL,
  `preco` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `precos`
--

INSERT INTO `precos` (`id`, `produto_id`, `tipo_cliente_id`, `preco`) VALUES
(4, 4, 2, 7.90),
(5, 4, 4, 7.50),
(6, 4, 1, 5.90),
(7, 4, 3, 6.90),
(8, 5, 2, 7.90),
(9, 5, 4, 7.50),
(10, 5, 1, 5.90),
(11, 5, 3, 6.90),
(12, 6, 2, 8.50),
(13, 6, 4, 7.20),
(14, 6, 1, 6.90),
(15, 6, 3, 7.20),
(16, 7, 2, 8.80),
(17, 7, 4, 7.50),
(18, 7, 1, 6.90),
(19, 7, 3, 7.50),
(20, 8, 2, 8.50),
(21, 8, 4, 7.20),
(22, 8, 1, 6.90),
(23, 8, 3, 7.20);

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int NOT NULL,
  `sku` varchar(20) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `unidade_medida` varchar(10) DEFAULT NULL,
  `limite_alerta` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `sku`, `nome`, `unidade_medida`, `limite_alerta`) VALUES
(4, '25016', 'Iogurte Integral', 'Un', 800.00),
(5, '25018', 'Iogurte Integral Morango', 'Un', 800.00),
(6, '22770', 'Leite Integral Tipo A', 'L', 400.00),
(7, '22774', 'Leite Integral Tipo A A2', 'L', 400.00),
(8, '22772', 'Leite Tipo A Semidesnatado', 'L', 200.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_cliente`
--

CREATE TABLE `tipos_cliente` (
  `id` int NOT NULL,
  `nome` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `tipos_cliente`
--

INSERT INTO `tipos_cliente` (`id`, `nome`) VALUES
(3, 'Emp Conveniência'),
(4, 'Emp Funcionário'),
(2, 'Empório'),
(1, 'PDV Indústria');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `privilegio` enum('Admin','Gestor','Vendedor','Industria') NOT NULL,
  `ativo` tinyint DEFAULT '1',
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `privilegio`, `ativo`, `data_criacao`) VALUES
(1, 'Joao Coelho', 'admin@natadocampo.com.br', '$2a$10$86bz.Wnuzo/5UJChhKriUeD5iT/rZwWLznU7V/1AkhBJ27xjNjOWK', 'Admin', 1, '2025-10-09 20:16:32'),
(2, 'Luciana', 'luciana@natadocampo.com.br', '$2y$10$L1lIOsAO243siMd71jJt.uBbujcnEH1oGul1/PywIaqHp9jxoeERa', 'Vendedor', 1, '2025-10-09 17:17:16'),
(3, 'Controladoria', 'controladoria@natadocampo.com.br', '$2y$10$cKo5MdS1YljvELk/t9TW/O6p2Mdqhe3aYiPohHLA.KtoYydQa3ZGu', 'Gestor', 1, '2025-10-09 17:17:16'),
(11, 'Tarlis Gregório', 'tarlis@natadocampo.com.br', '$2y$10$lpMkK9qn4SZUohNYF32GGemdibIZh78uuQ94oIt6mgzbV98TalpZW', 'Industria', 1, '2025-10-13 13:05:30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `vendas`
--

CREATE TABLE `vendas` (
  `id` int NOT NULL,
  `cliente_id` int NOT NULL,
  `tipo_transacao` enum('Venda','Bonificacao','Troca') NOT NULL DEFAULT 'Venda',
  `usuario_vendedor` int DEFAULT NULL,
  `data_venda` datetime DEFAULT CURRENT_TIMESTAMP,
  `data_entrega` date NOT NULL,
  `forma_pagamento` varchar(50) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `valor_custo_base` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` varchar(10) NOT NULL DEFAULT 'Pendente',
  `usuario_validador` int DEFAULT NULL,
  `data_validacao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `vendas`
--

INSERT INTO `vendas` (`id`, `cliente_id`, `tipo_transacao`, `usuario_vendedor`, `data_venda`, `data_entrega`, `forma_pagamento`, `valor_total`, `valor_custo_base`, `status`, `usuario_validador`, `data_validacao`) VALUES
(19, 1, 'Venda', 2, '2025-10-20 16:44:23', '2025-10-20', 'Faturamento', 59.00, 0.00, 'Liberado', 3, '2025-10-20 17:13:09');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_cliente` (`codigo_cliente`),
  ADD UNIQUE KEY `cnpj` (`cnpj`),
  ADD KEY `tipo_cliente_id` (`tipo_cliente_id`);

--
-- Índices de tabela `clube_nata_membros`
--
ALTER TABLE `clube_nata_membros`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cliente_id` (`cliente_id`),
  ADD KEY `indicado_por_id` (`indicado_por_id`);

--
-- Índices de tabela `clube_nata_recompensas`
--
ALTER TABLE `clube_nata_recompensas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `clube_nata_resgates`
--
ALTER TABLE `clube_nata_resgates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `recompensa_id` (`recompensa_id`);

--
-- Índices de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `itens_venda`
--
ALTER TABLE `itens_venda`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venda_id` (`venda_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `lotes_estoque`
--
ALTER TABLE `lotes_estoque`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `movimentacao_estoque`
--
ALTER TABLE `movimentacao_estoque`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movimentacao_estoque_ibfk_1` (`lote_id`);

--
-- Índices de tabela `precos`
--
ALTER TABLE `precos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_price` (`produto_id`,`tipo_cliente_id`),
  ADD KEY `tipo_cliente_id` (`tipo_cliente_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Índices de tabela `tipos_cliente`
--
ALTER TABLE `tipos_cliente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `vendas`
--
ALTER TABLE `vendas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `clube_nata_membros`
--
ALTER TABLE `clube_nata_membros`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `clube_nata_recompensas`
--
ALTER TABLE `clube_nata_recompensas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `clube_nata_resgates`
--
ALTER TABLE `clube_nata_resgates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `itens_venda`
--
ALTER TABLE `itens_venda`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de tabela `lotes_estoque`
--
ALTER TABLE `lotes_estoque`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de tabela `movimentacao_estoque`
--
ALTER TABLE `movimentacao_estoque`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de tabela `precos`
--
ALTER TABLE `precos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `tipos_cliente`
--
ALTER TABLE `tipos_cliente`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `vendas`
--
ALTER TABLE `vendas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`tipo_cliente_id`) REFERENCES `tipos_cliente` (`id`);

--
-- Restrições para tabelas `clube_nata_membros`
--
ALTER TABLE `clube_nata_membros`
  ADD CONSTRAINT `clube_nata_membros_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clube_nata_membros_ibfk_2` FOREIGN KEY (`indicado_por_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `clube_nata_resgates`
--
ALTER TABLE `clube_nata_resgates`
  ADD CONSTRAINT `clube_nata_resgates_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clube_nata_resgates_ibfk_2` FOREIGN KEY (`recompensa_id`) REFERENCES `clube_nata_recompensas` (`id`) ON DELETE RESTRICT;

--
-- Restrições para tabelas `itens_venda`
--
ALTER TABLE `itens_venda`
  ADD CONSTRAINT `itens_venda_ibfk_1` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `itens_venda_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);

--
-- Restrições para tabelas `movimentacao_estoque`
--
ALTER TABLE `movimentacao_estoque`
  ADD CONSTRAINT `movimentacao_estoque_ibfk_1` FOREIGN KEY (`lote_id`) REFERENCES `lotes_estoque` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `precos`
--
ALTER TABLE `precos`
  ADD CONSTRAINT `precos_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`),
  ADD CONSTRAINT `precos_ibfk_2` FOREIGN KEY (`tipo_cliente_id`) REFERENCES `tipos_cliente` (`id`);

--
-- Restrições para tabelas `vendas`
--
ALTER TABLE `vendas`
  ADD CONSTRAINT `vendas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
