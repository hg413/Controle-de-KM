CREATE DATABASE IF NOT EXISTS `sistema-partum`;
USE `sistema-partum`;

-- Estrutura da tabela `usuarios`
-- (Assumindo que talvez já exista, alteramos ou criamos)
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `perfil` ENUM('admin', 'motorista') NOT NULL DEFAULT 'motorista',
  `assinatura` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Estrutura da tabela `veiculos`
CREATE TABLE IF NOT EXISTS `veiculos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `placa` VARCHAR(20) NOT NULL UNIQUE,
  `motorista_responsavel_id` INT DEFAULT NULL,
  `km_atual` INT NOT NULL DEFAULT 0,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`motorista_responsavel_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
);

-- Estrutura da tabela `registro_diario`
CREATE TABLE IF NOT EXISTS `registro_diario` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `veiculo_id` INT NOT NULL,
  `motorista_id` INT NOT NULL,
  `data_registro` DATE NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `hora_final` TIME DEFAULT NULL,
  `km_inicial` INT NOT NULL,
  `km_final` INT DEFAULT NULL,
  `km_rodado` INT GENERATED ALWAYS AS (km_final - km_inicial) STORED,
  `roteiro` TEXT NOT NULL,
  `assinatura_motorista` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`motorista_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
);

-- Estrutura da tabela `abastecimentos`
CREATE TABLE IF NOT EXISTS `abastecimentos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `veiculo_id` INT NOT NULL,
  `data_abastecimento` DATE NOT NULL,
  `posto` VARCHAR(255) NOT NULL,
  `litros` DECIMAL(10,2) NOT NULL,
  `valor_pago` DECIMAL(10,2) NOT NULL,
  `km_veiculo` INT NOT NULL,
  `consumo_medio` DECIMAL(10,2) GENERATED ALWAYS AS (km_veiculo / NULLIF(litros, 0)) STORED,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos`(`id`) ON DELETE CASCADE
);

-- Estrutura da tabela `manutencoes`
CREATE TABLE IF NOT EXISTS `manutencoes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `veiculo_id` INT NOT NULL,
  `tipo` ENUM('Troca de Óleo', 'Revisão', 'Pneus', 'Manutenção Preventiva') NOT NULL,
  `data_manutencao` DATE NOT NULL,
  `km_veiculo` INT NOT NULL,
  `descricao` TEXT NOT NULL,
  `valor` DECIMAL(10,2) NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos`(`id`) ON DELETE CASCADE
);

-- Estrutura da tabela `ocorrencias`
CREATE TABLE IF NOT EXISTS `ocorrencias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `veiculo_id` INT NOT NULL,
  `motorista_id` INT NOT NULL,
  `data_ocorrencia` DATE NOT NULL,
  `hora_ocorrencia` TIME NOT NULL,
  `km_veiculo` INT NOT NULL,
  `local_ocorrencia` VARCHAR(255) NOT NULL,
  `descricao` TEXT NOT NULL,
  `anexo` VARCHAR(255) DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`motorista_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
);
