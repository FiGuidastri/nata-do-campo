-- Atualiza a tabela clientes
ALTER TABLE clientes
    ADD tipo_pessoa CHAR(1) NOT NULL DEFAULT 'J' AFTER tipo_cliente_id COMMENT 'F = Física, J = Jurídica',
    ADD cpf VARCHAR(11) UNIQUE NULL AFTER nome_cliente,
    ADD cep VARCHAR(8) NULL AFTER email,
    ADD logradouro VARCHAR(150) NULL AFTER cep,
    ADD numero VARCHAR(10) NULL AFTER logradouro,
    ADD complemento VARCHAR(100) NULL AFTER numero,
    ADD bairro VARCHAR(100) NULL AFTER complemento,
    ADD observacoes TEXT NULL AFTER endereco_entrega,
    ADD ativo BOOLEAN NOT NULL DEFAULT TRUE AFTER observacoes,
    ADD data_atualizacao DATETIME NULL AFTER data_cadastro,
    MODIFY endereco_entrega TEXT COMMENT 'Endereço completo formatado para impressão',
    MODIFY data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Atualiza os registros existentes
UPDATE clientes SET
    tipo_pessoa = CASE 
        WHEN cpf IS NOT NULL THEN 'F'
        ELSE 'J'
    END,
    data_atualizacao = NOW();

-- Adiciona os índices necessários
ALTER TABLE clientes
    ADD INDEX idx_tipo_pessoa (tipo_pessoa),
    ADD INDEX idx_tipo_cliente_id (tipo_cliente_id),
    ADD INDEX idx_codigo_cliente (codigo_cliente),
    ADD INDEX idx_nome_cliente (nome_cliente),
    ADD INDEX idx_cpf (cpf),
    ADD INDEX idx_cnpj (cnpj),
    ADD INDEX idx_cidade_estado (cidade, estado),
    ADD INDEX idx_ativo (ativo),
    ADD INDEX idx_data_cadastro (data_cadastro),
    ADD INDEX idx_data_atualizacao (data_atualizacao);

-- Adiciona as constraints
ALTER TABLE clientes
    MODIFY tipo_cliente_id INT NOT NULL,
    ADD CONSTRAINT fk_cliente_tipo_cliente 
    FOREIGN KEY (tipo_cliente_id) 
    REFERENCES tipos_cliente (id) 
    ON UPDATE CASCADE
    ON DELETE RESTRICT;