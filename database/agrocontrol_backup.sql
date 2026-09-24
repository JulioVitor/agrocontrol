/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.16-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: agrocontrol
-- ------------------------------------------------------
-- Server version	10.11.16-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `aplicacoes_vacinas`
--

DROP TABLE IF EXISTS `aplicacoes_vacinas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `aplicacoes_vacinas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_bovino` int(11) NOT NULL,
  `id_vacina` int(11) NOT NULL,
  `data_aplicacao` date NOT NULL,
  `dose_aplicada` decimal(5,2) DEFAULT NULL,
  `lote` varchar(50) DEFAULT NULL,
  `via_aplicacao` varchar(50) DEFAULT NULL,
  `responsavel` varchar(100) DEFAULT NULL,
  `proxima_dose` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_vacina` (`id_vacina`),
  KEY `idx_aplicacoes_bovino` (`id_bovino`),
  CONSTRAINT `aplicacoes_vacinas_ibfk_1` FOREIGN KEY (`id_bovino`) REFERENCES `bovinos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `aplicacoes_vacinas_ibfk_2` FOREIGN KEY (`id_vacina`) REFERENCES `vacinas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aplicacoes_vacinas`
--

LOCK TABLES `aplicacoes_vacinas` WRITE;
/*!40000 ALTER TABLE `aplicacoes_vacinas` DISABLE KEYS */;
/*!40000 ALTER TABLE `aplicacoes_vacinas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assinaturas`
--

DROP TABLE IF EXISTS `assinaturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assinaturas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `id_plano` int(11) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `ciclo` enum('mensal','anual') DEFAULT 'mensal',
  `status` enum('ativa','cancelada','expirada','trial') DEFAULT 'ativa',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_details` text DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_fazenda` (`id_fazenda`),
  KEY `id_plano` (`id_plano`),
  CONSTRAINT `assinaturas_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`),
  CONSTRAINT `assinaturas_ibfk_2` FOREIGN KEY (`id_plano`) REFERENCES `planos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assinaturas`
--

LOCK TABLES `assinaturas` WRITE;
/*!40000 ALTER TABLE `assinaturas` DISABLE KEYS */;
/*!40000 ALTER TABLE `assinaturas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bovinos`
--

DROP TABLE IF EXISTS `bovinos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bovinos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `id_usuario_cadastro` int(11) DEFAULT NULL,
  `id_raca` int(11) DEFAULT NULL,
  `id_situacao` int(11) DEFAULT NULL,
  `id_piquete_atual` int(11) DEFAULT NULL,
  `id_pai` int(11) DEFAULT NULL,
  `id_mae` int(11) DEFAULT NULL,
  `brinco` varchar(50) NOT NULL,
  `brinco_eletronico` varchar(50) DEFAULT NULL,
  `brinco_pai` varchar(50) DEFAULT NULL,
  `brinco_mae` varchar(50) DEFAULT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `registro_abcrh` varchar(50) DEFAULT NULL,
  `registro_anc` varchar(50) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `sexo` enum('M','F') NOT NULL,
  `peso_nascimento` decimal(7,2) DEFAULT NULL,
  `peso_atual` decimal(7,2) DEFAULT NULL,
  `condicao_corporal` decimal(3,2) DEFAULT NULL,
  `cor_pelagem` varchar(50) DEFAULT NULL,
  `origem` enum('nascido','comprado','doacao','troca') DEFAULT 'nascido',
  `data_entrada` date NOT NULL,
  `valor_compra` decimal(10,2) DEFAULT NULL,
  `comprado_de` varchar(255) DEFAULT NULL,
  `nome_pai` varchar(100) DEFAULT NULL,
  `raca_pai` varchar(100) DEFAULT NULL,
  `nome_mae` varchar(100) DEFAULT NULL,
  `raca_mae` varchar(100) DEFAULT NULL,
  `data_saida` date DEFAULT NULL,
  `motivo_saida` varchar(100) DEFAULT NULL,
  `valor_venda` decimal(10,2) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_brinco_fazenda` (`id_fazenda`,`brinco`),
  KEY `id_raca` (`id_raca`),
  KEY `id_pai` (`id_pai`),
  KEY `id_mae` (`id_mae`),
  KEY `idx_bovinos_fazenda` (`id_fazenda`),
  KEY `idx_bovinos_situacao` (`id_situacao`),
  KEY `idx_bovinos_piquete` (`id_piquete_atual`),
  KEY `id_usuario_cadastro` (`id_usuario_cadastro`),
  CONSTRAINT `bovinos_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bovinos_ibfk_2` FOREIGN KEY (`id_raca`) REFERENCES `racas` (`id`),
  CONSTRAINT `bovinos_ibfk_3` FOREIGN KEY (`id_situacao`) REFERENCES `situacoes` (`id`),
  CONSTRAINT `bovinos_ibfk_4` FOREIGN KEY (`id_piquete_atual`) REFERENCES `piquetes` (`id`),
  CONSTRAINT `bovinos_ibfk_5` FOREIGN KEY (`id_pai`) REFERENCES `bovinos` (`id`),
  CONSTRAINT `bovinos_ibfk_6` FOREIGN KEY (`id_mae`) REFERENCES `bovinos` (`id`),
  CONSTRAINT `bovinos_ibfk_7` FOREIGN KEY (`id_usuario_cadastro`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bovinos`
--

LOCK TABLES `bovinos` WRITE;
/*!40000 ALTER TABLE `bovinos` DISABLE KEYS */;
INSERT INTO `bovinos` VALUES
(1,3,NULL,17,1,1,NULL,NULL,'661133',NULL,NULL,NULL,'Bisao',NULL,NULL,'2024-01-01','M',6.00,45.00,NULL,'Branco','nascido','2026-03-09',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-03-10 00:30:54','2026-03-11 00:04:42'),
(2,3,NULL,17,1,1,NULL,3,'123',NULL,NULL,NULL,'Perola',NULL,NULL,'2024-07-30','F',12.00,35.00,NULL,'Branca','nascido','2026-03-09',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-03-10 00:32:39','2026-03-11 12:58:26'),
(3,3,NULL,19,1,1,NULL,NULL,'225',NULL,NULL,NULL,'Mimosa','99997',NULL,'2019-05-26','F',8.00,60.00,NULL,'Malhada','nascido','2026-03-11',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-03-11 12:53:04','2026-03-11 12:57:47'),
(4,3,NULL,20,1,1,NULL,NULL,'788',NULL,NULL,NULL,'Malacacheta',NULL,'455','2023-02-11','F',7.00,60.00,NULL,'Vermelha','comprado','2026-03-11',500.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-03-11 13:00:43',NULL),
(5,3,NULL,19,1,1,NULL,4,'63',NULL,NULL,NULL,'Alo',NULL,NULL,'2026-03-11','M',7.00,7.00,NULL,'Vermelha','nascido','2026-03-11',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-03-11 13:05:13',NULL);
/*!40000 ALTER TABLE `bovinos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias_financeiras`
--

DROP TABLE IF EXISTS `categorias_financeiras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias_financeiras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `nome_categoria` varchar(100) NOT NULL,
  `tipo` enum('receita','despesa') NOT NULL,
  `codigo` varchar(30) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `cor` varchar(20) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_categoria_fazenda` (`id_fazenda`,`nome_categoria`,`tipo`),
  CONSTRAINT `categorias_financeiras_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias_financeiras`
--

LOCK TABLES `categorias_financeiras` WRITE;
/*!40000 ALTER TABLE `categorias_financeiras` DISABLE KEYS */;
INSERT INTO `categorias_financeiras` VALUES
(1,3,'Venda de Gado','receita',NULL,NULL,'success',1),
(2,4,'Venda de Gado','receita',NULL,NULL,'success',1),
(5,3,'Venda de Leite','receita',NULL,NULL,'success',1),
(6,4,'Venda de Leite','receita',NULL,NULL,'success',1),
(8,3,'Compra de Gado','despesa',NULL,NULL,'danger',1),
(9,4,'Compra de Gado','despesa',NULL,NULL,'danger',1),
(11,3,'Ração e Suplementos','despesa',NULL,NULL,'danger',1),
(12,4,'Ração e Suplementos','despesa',NULL,NULL,'danger',1),
(14,3,'Medicamentos','despesa',NULL,NULL,'warning',1),
(15,4,'Medicamentos','despesa',NULL,NULL,'warning',1);
/*!40000 ALTER TABLE `categorias_financeiras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contas_recorrentes`
--

DROP TABLE IF EXISTS `contas_recorrentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contas_recorrentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `tipo` enum('receita','despesa') NOT NULL,
  `periodicidade` enum('mensal','trimestral','semestral','anual') NOT NULL,
  `dia_vencimento` int(11) NOT NULL,
  `proximo_vencimento` date DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `observacoes` text DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_fazenda` (`id_fazenda`),
  KEY `id_categoria` (`id_categoria`),
  CONSTRAINT `contas_recorrentes_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contas_recorrentes_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorias_financeiras` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contas_recorrentes`
--

LOCK TABLES `contas_recorrentes` WRITE;
/*!40000 ALTER TABLE `contas_recorrentes` DISABLE KEYS */;
/*!40000 ALTER TABLE `contas_recorrentes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documentos`
--

DROP TABLE IF EXISTS `documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_referencia` int(11) DEFAULT NULL,
  `tipo_referencia` varchar(50) DEFAULT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `caminho_arquivo` varchar(500) NOT NULL,
  `tamanho_bytes` int(11) DEFAULT NULL,
  `tipo_arquivo` varchar(100) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `data_upload` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_fazenda` (`id_fazenda`),
  KEY `id_usuario` (`id_usuario`),
  KEY `idx_documentos_referencia` (`tipo_referencia`,`id_referencia`),
  CONSTRAINT `documentos_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documentos_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documentos`
--

LOCK TABLES `documentos` WRITE;
/*!40000 ALTER TABLE `documentos` DISABLE KEYS */;
/*!40000 ALTER TABLE `documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estoque_categorias`
--

DROP TABLE IF EXISTS `estoque_categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `estoque_categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `nome_categoria` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `cor` varchar(20) DEFAULT '#6c757d',
  `ativo` tinyint(1) DEFAULT 1,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_categoria_fazenda` (`id_fazenda`,`nome_categoria`),
  CONSTRAINT `estoque_categorias_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estoque_categorias`
--

LOCK TABLES `estoque_categorias` WRITE;
/*!40000 ALTER TABLE `estoque_categorias` DISABLE KEYS */;
/*!40000 ALTER TABLE `estoque_categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estoque_fornecedores`
--

DROP TABLE IF EXISTS `estoque_fornecedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `estoque_fornecedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `nome_fornecedor` varchar(255) NOT NULL,
  `cnpj_cpf` varchar(20) DEFAULT NULL,
  `contato` varchar(100) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_fazenda` (`id_fazenda`),
  CONSTRAINT `estoque_fornecedores_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estoque_fornecedores`
--

LOCK TABLES `estoque_fornecedores` WRITE;
/*!40000 ALTER TABLE `estoque_fornecedores` DISABLE KEYS */;
/*!40000 ALTER TABLE `estoque_fornecedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estoque_lotes`
--

DROP TABLE IF EXISTS `estoque_lotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `estoque_lotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_produto` int(11) NOT NULL,
  `numero_lote` varchar(100) NOT NULL,
  `data_fabricacao` date DEFAULT NULL,
  `data_validade` date NOT NULL,
  `quantidade_inicial` decimal(10,2) NOT NULL,
  `quantidade_atual` decimal(10,2) NOT NULL,
  `id_fornecedor` int(11) DEFAULT NULL,
  `preco_custo` decimal(10,2) DEFAULT NULL,
  `data_entrada` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `id_produto` (`id_produto`),
  KEY `id_fornecedor` (`id_fornecedor`),
  KEY `idx_lote_validade` (`data_validade`),
  CONSTRAINT `estoque_lotes_ibfk_1` FOREIGN KEY (`id_produto`) REFERENCES `estoque_produtos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `estoque_lotes_ibfk_2` FOREIGN KEY (`id_fornecedor`) REFERENCES `estoque_fornecedores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estoque_lotes`
--

LOCK TABLES `estoque_lotes` WRITE;
/*!40000 ALTER TABLE `estoque_lotes` DISABLE KEYS */;
/*!40000 ALTER TABLE `estoque_lotes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estoque_movimentacoes`
--

DROP TABLE IF EXISTS `estoque_movimentacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `estoque_movimentacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `tipo_movimento` enum('entrada','saida','ajuste','perda') NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `quantidade_anterior` decimal(10,2) DEFAULT NULL,
  `quantidade_posterior` decimal(10,2) DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `documento` varchar(100) DEFAULT NULL,
  `id_bovino` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `data_movimento` datetime DEFAULT current_timestamp(),
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_fazenda` (`id_fazenda`),
  KEY `id_produto` (`id_produto`),
  KEY `id_bovino` (`id_bovino`),
  KEY `id_usuario` (`id_usuario`),
  KEY `idx_movimento_data` (`data_movimento`),
  KEY `idx_movimento_tipo` (`tipo_movimento`),
  CONSTRAINT `estoque_movimentacoes_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `estoque_movimentacoes_ibfk_2` FOREIGN KEY (`id_produto`) REFERENCES `estoque_produtos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `estoque_movimentacoes_ibfk_3` FOREIGN KEY (`id_bovino`) REFERENCES `bovinos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `estoque_movimentacoes_ibfk_4` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estoque_movimentacoes`
--

LOCK TABLES `estoque_movimentacoes` WRITE;
/*!40000 ALTER TABLE `estoque_movimentacoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `estoque_movimentacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estoque_produtos`
--

DROP TABLE IF EXISTS `estoque_produtos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `estoque_produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nome_produto` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `unidade` enum('kg','g','L','ml','un','cx','sc') DEFAULT 'un',
  `quantidade_atual` decimal(10,2) DEFAULT 0.00,
  `quantidade_minima` decimal(10,2) DEFAULT 0.00,
  `quantidade_maxima` decimal(10,2) DEFAULT 0.00,
  `localizacao` varchar(100) DEFAULT NULL,
  `fabricante` varchar(255) DEFAULT NULL,
  `lote` varchar(100) DEFAULT NULL,
  `data_fabricacao` date DEFAULT NULL,
  `data_validade` date DEFAULT NULL,
  `preco_custo` decimal(10,2) DEFAULT NULL,
  `preco_venda` decimal(10,2) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `id_fazenda` (`id_fazenda`),
  KEY `id_categoria` (`id_categoria`),
  KEY `idx_produto_codigo` (`codigo`),
  KEY `idx_produto_validade` (`data_validade`),
  CONSTRAINT `estoque_produtos_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `estoque_produtos_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `estoque_categorias` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estoque_produtos`
--

LOCK TABLES `estoque_produtos` WRITE;
/*!40000 ALTER TABLE `estoque_produtos` DISABLE KEYS */;
/*!40000 ALTER TABLE `estoque_produtos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eventos`
--

DROP TABLE IF EXISTS `eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eventos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_bovino` int(11) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo` enum('vacina','parto','inseminacao','desmama','venda','compra','manutencao','consulta','outro') DEFAULT 'outro',
  `data_inicio` datetime NOT NULL,
  `data_fim` datetime DEFAULT NULL,
  `dia_inteiro` tinyint(1) DEFAULT 0,
  `local` varchar(255) DEFAULT NULL,
  `cor` varchar(20) DEFAULT NULL,
  `notificar` tinyint(1) DEFAULT 0,
  `notificar_antecedencia` int(11) DEFAULT NULL,
  `concluido` tinyint(1) DEFAULT 0,
  `data_conclusao` datetime DEFAULT NULL,
  `recorrente` tinyint(1) DEFAULT 0,
  `recorrencia_config` text DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_fazenda` (`id_fazenda`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_bovino` (`id_bovino`),
  KEY `idx_eventos_datas` (`data_inicio`),
  CONSTRAINT `eventos_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eventos_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `eventos_ibfk_3` FOREIGN KEY (`id_bovino`) REFERENCES `bovinos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eventos`
--

LOCK TABLES `eventos` WRITE;
/*!40000 ALTER TABLE `eventos` DISABLE KEYS */;
INSERT INTO `eventos` VALUES
(7,3,1,NULL,'Vacinação Febre Aftosa',NULL,'vacina','2026-03-15 22:19:35',NULL,0,NULL,'#28a745',0,NULL,0,NULL,0,NULL,'2026-03-11 01:19:35'),
(8,3,1,NULL,'Parto - vaca 123',NULL,'parto','2026-03-22 22:19:35',NULL,0,NULL,'#dc3545',0,NULL,0,NULL,0,NULL,'2026-03-11 01:19:35'),
(9,3,1,NULL,'Inseminação - matriz 456',NULL,'inseminacao','2026-03-13 22:19:35',NULL,0,NULL,'#ffc107',0,NULL,0,NULL,0,NULL,'2026-03-11 01:19:35'),
(10,3,1,NULL,'Desmama dos bezerros',NULL,'desmama','2026-03-30 22:19:35',NULL,0,NULL,'#17a2b8',0,NULL,0,NULL,0,NULL,'2026-03-11 01:19:35'),
(11,3,1,NULL,'Manutenção do pasto',NULL,'manutencao','2026-03-17 22:19:35',NULL,0,NULL,'#6c757d',0,NULL,0,NULL,0,NULL,'2026-03-11 01:19:35'),
(12,3,1,NULL,'Carimbar novos bezerros',NULL,'outro','2026-03-13 08:00:00',NULL,1,'Curral','#28a745',0,30,1,'2026-03-11 16:34:15',0,NULL,'2026-03-11 11:10:36'),
(14,3,1,NULL,'Vermifucar Vacas corte',NULL,'outro','2026-03-11 08:00:00',NULL,1,'Curral','#28a745',0,30,0,NULL,0,NULL,'2026-03-11 11:13:48');
/*!40000 ALTER TABLE `eventos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fazenda_configuracoes`
--

DROP TABLE IF EXISTS `fazenda_configuracoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fazenda_configuracoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `chave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` varchar(20) DEFAULT 'texto',
  `descricao` text DEFAULT NULL,
  `data_atualizacao` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_fazenda_chave` (`id_fazenda`,`chave`),
  CONSTRAINT `fazenda_configuracoes_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fazenda_configuracoes`
--

LOCK TABLES `fazenda_configuracoes` WRITE;
/*!40000 ALTER TABLE `fazenda_configuracoes` DISABLE KEYS */;
INSERT INTO `fazenda_configuracoes` VALUES
(1,4,'idioma','pt_BR','texto',NULL,NULL),
(2,4,'timezone','America/Sao_Paulo','texto',NULL,NULL),
(3,4,'moeda','BRL','texto',NULL,NULL),
(4,4,'formato_data','d/m/Y','texto',NULL,NULL),
(5,3,'idioma','pt_BR','texto',NULL,NULL),
(6,3,'formato_data','d/m/Y','texto',NULL,NULL),
(7,3,'moeda','BRL','texto',NULL,NULL);
/*!40000 ALTER TABLE `fazenda_configuracoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fazenda_usuarios`
--

DROP TABLE IF EXISTS `fazenda_usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fazenda_usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `papel` enum('proprietario','administrador','gerente','veterinario','funcionario','consultor') DEFAULT 'funcionario',
  `setor` varchar(100) DEFAULT NULL,
  `permissões` text DEFAULT NULL,
  `recebe_notificacoes` tinyint(1) DEFAULT 1,
  `data_atribuicao` timestamp NULL DEFAULT current_timestamp(),
  `data_remocao` timestamp NULL DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_usuario_fazenda` (`id_fazenda`,`id_usuario`,`ativo`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `fazenda_usuarios_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fazenda_usuarios_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fazenda_usuarios`
--

LOCK TABLES `fazenda_usuarios` WRITE;
/*!40000 ALTER TABLE `fazenda_usuarios` DISABLE KEYS */;
INSERT INTO `fazenda_usuarios` VALUES
(3,3,1,'proprietario',NULL,NULL,1,'2026-03-10 00:14:13',NULL,1),
(4,4,2,'proprietario',NULL,NULL,1,'2026-03-10 00:46:54',NULL,1);
/*!40000 ALTER TABLE `fazenda_usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fazendas`
--

DROP TABLE IF EXISTS `fazendas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fazendas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_proprietario` int(11) NOT NULL,
  `id_plano` int(11) NOT NULL,
  `nome_fazenda` varchar(150) NOT NULL,
  `nome_fantasia` varchar(150) DEFAULT NULL,
  `cnpj` varchar(20) DEFAULT NULL,
  `inscricao_estadual` varchar(20) DEFAULT NULL,
  `email_principal` varchar(100) DEFAULT NULL,
  `telefone_principal` varchar(20) DEFAULT NULL,
  `telefone_secundario` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `site` varchar(255) DEFAULT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `pais` varchar(50) DEFAULT 'Brasil',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `area_total_hectares` decimal(10,2) DEFAULT NULL,
  `area_agricola_hectares` decimal(10,2) DEFAULT NULL,
  `area_pastagem_hectares` decimal(10,2) DEFAULT NULL,
  `area_preservacao_hectares` decimal(10,2) DEFAULT NULL,
  `bioma` varchar(100) DEFAULT NULL,
  `idioma` varchar(10) DEFAULT 'pt_BR',
  `timezone` varchar(50) DEFAULT 'America/Sao_Paulo',
  `moeda` varchar(3) DEFAULT 'BRL',
  `formato_data` varchar(20) DEFAULT 'd/m/Y',
  `logo` varchar(255) DEFAULT NULL,
  `cor_primaria` varchar(7) DEFAULT '#28a745',
  `cor_secundaria` varchar(7) DEFAULT '#6c757d',
  `status` enum('trial','ativo','inadimplente','suspenso','cancelado') DEFAULT 'trial',
  `ativo` tinyint(1) DEFAULT 1,
  `data_ativacao` date DEFAULT NULL,
  `data_expiracao` date DEFAULT NULL,
  `data_cancelamento` date DEFAULT NULL,
  `motivo_cancelamento` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_plano` (`id_plano`),
  KEY `idx_status` (`status`),
  KEY `idx_proprietario` (`id_proprietario`),
  CONSTRAINT `fazendas_ibfk_1` FOREIGN KEY (`id_proprietario`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fazendas_ibfk_2` FOREIGN KEY (`id_plano`) REFERENCES `planos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fazendas`
--

LOCK TABLES `fazendas` WRITE;
/*!40000 ALTER TABLE `fazendas` DISABLE KEYS */;
INSERT INTO `fazendas` VALUES
(3,1,1,'Fazenda Ceu Aberto',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Franciscopolis','MG',NULL,'Brasil',NULL,NULL,10.00,NULL,NULL,NULL,NULL,'pt_BR','America/Sao_Paulo','BRL','d/m/Y',NULL,'#28a745','#6c757d','ativo',1,'2026-03-09',NULL,NULL,NULL,NULL,'2026-03-10 00:14:13',NULL),
(4,2,1,'Fazenda Esperança',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Campinas','SP',NULL,'Brasil',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'pt_BR','America/Sao_Paulo','BRL','d/m/Y',NULL,'#28a745','#6c757d','trial',1,'2026-03-10','2026-04-09',NULL,NULL,NULL,'2026-03-10 00:46:54',NULL);
/*!40000 ALTER TABLE `fazendas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historico_piquete`
--

DROP TABLE IF EXISTS `historico_piquete`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `historico_piquete` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_piquete` int(11) NOT NULL,
  `id_bovino` int(11) NOT NULL,
  `data_entrada` date NOT NULL,
  `data_saida` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_historico_piquete` (`id_piquete`),
  KEY `idx_historico_bovino` (`id_bovino`),
  KEY `idx_historico_datas` (`data_entrada`,`data_saida`),
  CONSTRAINT `historico_piquete_ibfk_1` FOREIGN KEY (`id_piquete`) REFERENCES `piquetes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historico_piquete_ibfk_2` FOREIGN KEY (`id_bovino`) REFERENCES `bovinos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historico_piquete`
--

LOCK TABLES `historico_piquete` WRITE;
/*!40000 ALTER TABLE `historico_piquete` DISABLE KEYS */;
INSERT INTO `historico_piquete` VALUES
(1,1,2,'2026-03-10',NULL,NULL,'2026-03-11 00:04:42'),
(2,1,1,'2026-03-10',NULL,NULL,'2026-03-11 00:04:42');
/*!40000 ALTER TABLE `historico_piquete` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lancamentos_financeiros`
--

DROP TABLE IF EXISTS `lancamentos_financeiros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lancamentos_financeiros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `id_bovino` int(11) DEFAULT NULL,
  `numero_documento` varchar(50) DEFAULT NULL,
  `data_emissao` date NOT NULL,
  `data_vencimento` date DEFAULT NULL,
  `data_pagamento` date DEFAULT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `valor_pago` decimal(10,2) DEFAULT NULL,
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `status` enum('pendente','pago','atrasado','cancelado') DEFAULT 'pendente',
  `recorrente` tinyint(1) DEFAULT 0,
  `periodicidade` varchar(20) DEFAULT NULL,
  `anexo` varchar(255) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_fazenda` (`id_fazenda`),
  KEY `id_categoria` (`id_categoria`),
  KEY `id_bovino` (`id_bovino`),
  KEY `idx_financeiro_status` (`status`),
  KEY `idx_financeiro_datas` (`data_vencimento`),
  CONSTRAINT `lancamentos_financeiros_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lancamentos_financeiros_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorias_financeiras` (`id`),
  CONSTRAINT `lancamentos_financeiros_ibfk_3` FOREIGN KEY (`id_bovino`) REFERENCES `bovinos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lancamentos_financeiros`
--

LOCK TABLES `lancamentos_financeiros` WRITE;
/*!40000 ALTER TABLE `lancamentos_financeiros` DISABLE KEYS */;
INSERT INTO `lancamentos_financeiros` VALUES
(1,3,11,NULL,NULL,'2026-03-11','2026-03-11','2026-03-11','Compra de ração',200.00,200.00,'Dinheiro','pago',0,NULL,NULL,NULL,'2026-03-11 11:20:41');
/*!40000 ALTER TABLE `lancamentos_financeiros` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `acao` varchar(100) NOT NULL,
  `modulo` varchar(50) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `dados_anteriores` text DEFAULT NULL,
  `dados_novos` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `data` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_fazenda` (`id_fazenda`),
  KEY `id_usuario` (`usuario_id`),
  KEY `idx_logs_data` (`data`),
  CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
INSERT INTO `logs` VALUES
(1,3,1,'logout',NULL,NULL,NULL,NULL,'::1',NULL,'2026-03-10 00:42:52'),
(2,4,2,'logout',NULL,NULL,NULL,NULL,'::1',NULL,'2026-03-10 00:52:11'),
(3,NULL,1,'logout',NULL,NULL,NULL,NULL,'::1',NULL,'2026-03-10 10:54:48'),
(4,NULL,1,'login',NULL,NULL,NULL,NULL,'::1',NULL,'2026-03-10 10:55:00'),
(5,NULL,1,'login',NULL,NULL,NULL,NULL,'::1',NULL,'2026-03-10 20:56:35'),
(6,NULL,1,'login',NULL,NULL,NULL,NULL,'::1',NULL,'2026-03-10 22:15:58'),
(7,NULL,1,'logout',NULL,NULL,NULL,NULL,'::1',NULL,'2026-03-11 02:17:02'),
(8,NULL,1,'login',NULL,NULL,NULL,NULL,'::1',NULL,'2026-03-11 11:07:40'),
(9,NULL,1,'login',NULL,NULL,NULL,NULL,'::1',NULL,'2026-03-11 18:29:29');
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `manutencao_piquete`
--

DROP TABLE IF EXISTS `manutencao_piquete`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `manutencao_piquete` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_piquete` int(11) NOT NULL,
  `tipo_manutencao` enum('adubacao','calagem','roçada','plantio','limpeza') NOT NULL,
  `data_manutencao` date NOT NULL,
  `descricao` text DEFAULT NULL,
  `custo` decimal(10,2) DEFAULT NULL,
  `responsavel` varchar(100) DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_manutencao_piquete` (`id_piquete`),
  KEY `idx_manutencao_data` (`data_manutencao`),
  CONSTRAINT `manutencao_piquete_ibfk_1` FOREIGN KEY (`id_piquete`) REFERENCES `piquetes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `manutencao_piquete`
--

LOCK TABLES `manutencao_piquete` WRITE;
/*!40000 ALTER TABLE `manutencao_piquete` DISABLE KEYS */;
/*!40000 ALTER TABLE `manutencao_piquete` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `matrizes`
--

DROP TABLE IF EXISTS `matrizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `matrizes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_bovino` int(11) NOT NULL,
  `data_ultimo_parto` date DEFAULT NULL,
  `data_ultima_inseminacao` date DEFAULT NULL,
  `data_ultimo_cio` date DEFAULT NULL,
  `data_secagem` date DEFAULT NULL,
  `status_reprodutivo` enum('vazia','inseminada','prenhe','lactacao','seca') DEFAULT 'vazia',
  `numero_partos` int(11) DEFAULT 0,
  `total_crias` int(11) DEFAULT 0,
  `observacoes` text DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_matriz` (`id_bovino`),
  CONSTRAINT `matrizes_ibfk_1` FOREIGN KEY (`id_bovino`) REFERENCES `bovinos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `matrizes`
--

LOCK TABLES `matrizes` WRITE;
/*!40000 ALTER TABLE `matrizes` DISABLE KEYS */;
/*!40000 ALTER TABLE `matrizes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modulos`
--

DROP TABLE IF EXISTS `modulos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `modulos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_modulo` varchar(100) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `descricao` text DEFAULT NULL,
  `icone` varchar(50) DEFAULT NULL,
  `preco_mensal` decimal(10,2) DEFAULT 0.00,
  `disponivel` tinyint(1) DEFAULT 1,
  `ordem` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modulos`
--

LOCK TABLES `modulos` WRITE;
/*!40000 ALTER TABLE `modulos` DISABLE KEYS */;
INSERT INTO `modulos` VALUES
(1,'Bovinos Corte','bovinos_corte',NULL,'bi-tree',0.00,1,1),
(2,'Bovinos Leite','bovinos_leite',NULL,'bi-cup',0.00,1,2),
(3,'Pastagens','pastagens',NULL,'bi-map',0.00,1,3),
(4,'Financeiro','financeiro',NULL,'bi-cash-coin',0.00,1,4),
(5,'Reprodução','reproducao',NULL,'bi-heart',0.00,1,5),
(6,'Relatórios','relatorios',NULL,'bi-file-text',0.00,1,6),
(7,'Calendário','calendario',NULL,'bi-calendar',0.00,1,7);
/*!40000 ALTER TABLE `modulos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pesagens`
--

DROP TABLE IF EXISTS `pesagens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pesagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_bovino` int(11) NOT NULL,
  `data_pesagem` date NOT NULL,
  `peso` decimal(7,2) NOT NULL,
  `balanca` varchar(50) DEFAULT NULL,
  `responsavel` varchar(100) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pesagens_bovino` (`id_bovino`),
  CONSTRAINT `pesagens_ibfk_1` FOREIGN KEY (`id_bovino`) REFERENCES `bovinos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pesagens`
--

LOCK TABLES `pesagens` WRITE;
/*!40000 ALTER TABLE `pesagens` DISABLE KEYS */;
INSERT INTO `pesagens` VALUES
(1,2,'2026-03-10',85.00,NULL,NULL,NULL,'2026-03-10 21:47:40'),
(2,1,'2026-03-10',45.00,NULL,NULL,NULL,'2026-03-10 22:18:40'),
(3,2,'2026-03-10',90.00,NULL,NULL,NULL,'2026-03-10 22:37:23');
/*!40000 ALTER TABLE `pesagens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `piquetes`
--

DROP TABLE IF EXISTS `piquetes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `piquetes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `nome_piquete` varchar(100) NOT NULL,
  `codigo` varchar(30) DEFAULT NULL,
  `area_hectares` decimal(10,2) DEFAULT NULL,
  `comprimento_m` decimal(10,2) DEFAULT NULL,
  `largura_m` decimal(10,2) DEFAULT NULL,
  `tipo_pasto` varchar(100) DEFAULT NULL,
  `capacidade_suporte` int(11) DEFAULT NULL,
  `disponivel` tinyint(1) DEFAULT 1,
  `lotacao_atual` int(11) DEFAULT 0,
  `dias_descanso` int(11) DEFAULT 30,
  `dias_ocupacao` int(11) DEFAULT 7,
  `data_ultima_ocupacao` date DEFAULT NULL,
  `data_ultima_manutencao` date DEFAULT NULL,
  `cerca_eletrica` tinyint(1) DEFAULT 0,
  `agua_disponivel` tinyint(1) DEFAULT 1,
  `sombra_disponivel` tinyint(1) DEFAULT 1,
  `coordenadas_polygon` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_piquete_fazenda` (`id_fazenda`,`nome_piquete`),
  CONSTRAINT `piquetes_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `piquetes`
--

LOCK TABLES `piquetes` WRITE;
/*!40000 ALTER TABLE `piquetes` DISABLE KEYS */;
INSERT INTO `piquetes` VALUES
(1,3,'Principal','P01',4.00,40000.00,40000.00,'Branquiaria',30,1,2,30,10,'2026-03-10',NULL,1,1,1,NULL,NULL,1,'2026-03-11 00:02:29');
/*!40000 ALTER TABLE `piquetes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plano_modulos`
--

DROP TABLE IF EXISTS `plano_modulos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `plano_modulos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_plano` int(11) NOT NULL,
  `id_modulo` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_plano_modulo` (`id_plano`,`id_modulo`),
  KEY `id_modulo` (`id_modulo`),
  CONSTRAINT `plano_modulos_ibfk_1` FOREIGN KEY (`id_plano`) REFERENCES `planos` (`id`),
  CONSTRAINT `plano_modulos_ibfk_2` FOREIGN KEY (`id_modulo`) REFERENCES `modulos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plano_modulos`
--

LOCK TABLES `plano_modulos` WRITE;
/*!40000 ALTER TABLE `plano_modulos` DISABLE KEYS */;
INSERT INTO `plano_modulos` VALUES
(1,1,1),
(2,1,2),
(3,3,1),
(4,3,2),
(5,3,3),
(6,3,4),
(7,3,5),
(8,3,6),
(9,3,7);
/*!40000 ALTER TABLE `plano_modulos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `planos`
--

DROP TABLE IF EXISTS `planos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `planos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_plano` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco_mensal` decimal(10,2) NOT NULL,
  `preco_anual` decimal(10,2) DEFAULT NULL,
  `max_animais` int(11) DEFAULT NULL,
  `max_usuarios` int(11) DEFAULT NULL,
  `max_piquetes` int(11) DEFAULT NULL,
  `recursos_extras` text DEFAULT NULL,
  `destaque` tinyint(1) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `ordem` int(11) DEFAULT 0,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `planos`
--

LOCK TABLES `planos` WRITE;
/*!40000 ALTER TABLE `planos` DISABLE KEYS */;
INSERT INTO `planos` VALUES
(1,'Básico','Ideal para pequenos produtores iniciarem a gestão',49.90,NULL,50,2,NULL,NULL,0,1,1,'2026-03-09 22:46:02'),
(2,'Profissional','Perfeito para médios produtores com rebanho em crescimento',99.90,NULL,200,5,NULL,NULL,1,1,2,'2026-03-09 22:46:02'),
(3,'Master','Solução completa para grandes propriedades',199.90,NULL,NULL,15,NULL,NULL,0,1,3,'2026-03-09 22:46:02');
/*!40000 ALTER TABLE `planos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producao_leite`
--

DROP TABLE IF EXISTS `producao_leite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `producao_leite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_bovino` int(11) NOT NULL,
  `data_producao` date NOT NULL,
  `turno` enum('manha','tarde','noite','unico') DEFAULT 'unico',
  `quantidade_litros` decimal(7,2) NOT NULL,
  `qualidade` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_bovino` (`id_bovino`),
  KEY `idx_producao_data` (`data_producao`),
  CONSTRAINT `producao_leite_ibfk_1` FOREIGN KEY (`id_bovino`) REFERENCES `bovinos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producao_leite`
--

LOCK TABLES `producao_leite` WRITE;
/*!40000 ALTER TABLE `producao_leite` DISABLE KEYS */;
INSERT INTO `producao_leite` VALUES
(1,2,'2026-03-10','manha',2.00,NULL,NULL,'2026-03-10 23:38:49'),
(2,4,'2026-03-11','manha',10.00,NULL,NULL,'2026-03-11 13:03:22');
/*!40000 ALTER TABLE `producao_leite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `racas`
--

DROP TABLE IF EXISTS `racas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `racas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `nome_raca` varchar(100) NOT NULL,
  `tipo` enum('corte','leite','misto','trabalho') NOT NULL,
  `origem` varchar(100) DEFAULT NULL,
  `porte` enum('pequeno','medio','grande') DEFAULT 'medio',
  `peso_medio_adulto` decimal(7,2) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_raca_fazenda` (`id_fazenda`,`nome_raca`),
  CONSTRAINT `racas_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `racas`
--

LOCK TABLES `racas` WRITE;
/*!40000 ALTER TABLE `racas` DISABLE KEYS */;
INSERT INTO `racas` VALUES
(17,3,'Nelore','corte',NULL,'medio',NULL,'Raça zebuína mais comum no Brasil, excelente para corte','2026-03-10 00:23:13'),
(18,3,'Angus','corte',NULL,'medio',NULL,'Raça britânica, carne marmorizada de alta qualidade','2026-03-10 00:23:13'),
(19,3,'Holandês','leite',NULL,'medio',NULL,'Raça especializada em produção de leite, pelagem preta e branca','2026-03-10 00:23:13'),
(20,3,'Girolando','leite',NULL,'medio',NULL,'Raça leiteira adaptada ao clima tropical, cruzamento de Gir com Holandês','2026-03-10 00:23:13'),
(21,3,'Brahma','corte',NULL,'medio',NULL,'Raça zebuína de grande porte, resistente ao calor','2026-03-10 00:23:13'),
(22,3,'Hereford','corte',NULL,'medio',NULL,'Raça britânica, cara branca e corpo vermelho','2026-03-10 00:23:13'),
(23,3,'Jersey','leite',NULL,'medio',NULL,'Raça leiteira de pequeno porte, leite rico em gordura','2026-03-10 00:23:13'),
(24,3,'Senepol','corte',NULL,'medio',NULL,'Raça de origem tropical, pelagem vermelha e sem chifres','2026-03-10 00:23:13');
/*!40000 ALTER TABLE `racas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reproducao`
--

DROP TABLE IF EXISTS `reproducao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reproducao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `id_bovino_femea` int(11) NOT NULL,
  `id_bovino_macho` int(11) DEFAULT NULL,
  `data_evento` date NOT NULL,
  `tipo_evento` enum('cio','inseminacao','prenhez','parto','aborto') NOT NULL,
  `tecnico` varchar(100) DEFAULT NULL,
  `semen_raca` varchar(100) DEFAULT NULL,
  `semen_touro` varchar(100) DEFAULT NULL,
  `data_prevista_parto` date DEFAULT NULL,
  `confirmada` tinyint(1) DEFAULT 0,
  `metodo_confirmacao` varchar(50) DEFAULT NULL,
  `data_parto` date DEFAULT NULL,
  `crias_nascidas` int(11) DEFAULT NULL,
  `crias_vivas` int(11) DEFAULT NULL,
  `dificuldade_parto` enum('normal','dificil','cesariana') DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_bovino_macho` (`id_bovino_macho`),
  KEY `idx_reproducao_femea` (`id_bovino_femea`),
  KEY `id_fazenda` (`id_fazenda`),
  CONSTRAINT `reproducao_ibfk_1` FOREIGN KEY (`id_bovino_femea`) REFERENCES `bovinos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reproducao_ibfk_2` FOREIGN KEY (`id_bovino_macho`) REFERENCES `bovinos` (`id`),
  CONSTRAINT `reproducao_ibfk_3` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reproducao`
--

LOCK TABLES `reproducao` WRITE;
/*!40000 ALTER TABLE `reproducao` DISABLE KEYS */;
INSERT INTO `reproducao` VALUES
(1,3,2,NULL,'2026-03-11','cio',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-03-11 13:01:14');
/*!40000 ALTER TABLE `reproducao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `situacoes`
--

DROP TABLE IF EXISTS `situacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `situacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `codigo` varchar(30) DEFAULT NULL,
  `nome` varchar(50) NOT NULL,
  `cor` varchar(20) DEFAULT 'secondary',
  `icone` varchar(50) DEFAULT NULL,
  `permite_movimentacao` tinyint(1) DEFAULT 1,
  `permite_venda` tinyint(1) DEFAULT 0,
  `ordem` int(11) DEFAULT 0,
  `padrao` tinyint(1) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_situacao_fazenda` (`id_fazenda`,`codigo`),
  CONSTRAINT `situacoes_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `situacoes`
--

LOCK TABLES `situacoes` WRITE;
/*!40000 ALTER TABLE `situacoes` DISABLE KEYS */;
INSERT INTO `situacoes` VALUES
(1,3,NULL,'No rebanho','success',NULL,1,0,1,1,1,'2026-03-10 00:14:13'),
(2,3,NULL,'Vendido','warning',NULL,1,0,2,0,1,'2026-03-10 00:14:13'),
(3,3,NULL,'Morto','danger',NULL,1,0,3,0,1,'2026-03-10 00:14:13'),
(4,3,NULL,'Abatido','secondary',NULL,1,0,4,0,1,'2026-03-10 00:14:13'),
(5,4,'no_rebanho','No rebanho','success',NULL,1,0,1,1,1,'2026-03-10 00:46:54'),
(6,4,'vendido','Vendido','warning',NULL,1,0,2,0,1,'2026-03-10 00:46:54'),
(7,4,'morto','Morto','danger',NULL,1,0,3,0,1,'2026-03-10 00:46:54'),
(8,4,'abatido','Abatido','secondary',NULL,1,0,4,0,1,'2026-03-10 00:46:54');
/*!40000 ALTER TABLE `situacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel` enum('admin','usuario') DEFAULT 'usuario',
  `telefone` varchar(20) DEFAULT NULL,
  `cpf_cnpj` varchar(20) DEFAULT NULL,
  `tipo_pessoa` enum('fisica','juridica') DEFAULT 'fisica',
  `email_confirmado` tinyint(1) DEFAULT 0,
  `token_confirmacao` varchar(100) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `profissao` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `ultimo_login` datetime DEFAULT NULL,
  `ultimo_ip` varchar(45) DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES
(1,'Administrador Sistema','admin@agrocontrol.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','usuario',NULL,NULL,'fisica',1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-03-09 22:47:13',NULL,1),
(2,'Maria Oliveira','maria@email.com','$2y$12$pkCdojUBPnR90OFAQUNhlORpOdKOGnGzTolka9mW8ZF.k5qhyGaM2','usuario','(11) 98888-7777',NULL,'fisica',0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-03-10 00:46:54',NULL,1);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vacinas`
--

DROP TABLE IF EXISTS `vacinas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vacinas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_fazenda` int(11) NOT NULL,
  `nome_vacina` varchar(100) NOT NULL,
  `fabricante` varchar(100) DEFAULT NULL,
  `doenca_prevenida` varchar(255) DEFAULT NULL,
  `intervalo_dias` int(11) DEFAULT NULL,
  `idade_minima_dias` int(11) DEFAULT NULL,
  `idade_maxima_dias` int(11) DEFAULT NULL,
  `dose_ml` decimal(5,2) DEFAULT NULL,
  `via_aplicacao` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vacina_fazenda` (`id_fazenda`,`nome_vacina`),
  CONSTRAINT `vacinas_ibfk_1` FOREIGN KEY (`id_fazenda`) REFERENCES `fazendas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vacinas`
--

LOCK TABLES `vacinas` WRITE;
/*!40000 ALTER TABLE `vacinas` DISABLE KEYS */;
INSERT INTO `vacinas` VALUES
(11,3,'Febre Aftosa','Merial','Febre Aftosa',180,NULL,NULL,5.00,'Subcutânea',NULL,'2026-03-10 22:46:27'),
(12,3,'Brucelose','Zoetis','Brucelose',NULL,NULL,NULL,2.00,'Subcutânea',NULL,'2026-03-10 22:46:27'),
(13,3,'Raiva','MSD','Raiva',365,NULL,NULL,2.00,'Intramuscular',NULL,'2026-03-10 22:46:27'),
(14,3,'Clostridiose','Vallée','Clostridioses',365,NULL,NULL,5.00,'Subcutânea',NULL,'2026-03-10 22:46:27'),
(15,3,'IBR/BVD','Zoetis','Rinotraqueíte/Diarréia',180,NULL,NULL,2.00,'Intramuscular',NULL,'2026-03-10 22:46:27');
/*!40000 ALTER TABLE `vacinas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'agrocontrol'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-16 19:25:09
