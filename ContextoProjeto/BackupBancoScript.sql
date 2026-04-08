CREATE DATABASE cadoficina
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE cadoficina;

CREATE TABLE IF NOT EXISTS USUARIO (
    id_usuario INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    login VARCHAR(50) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('operador','proprietaria') NOT NULL DEFAULT 'operador',
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuario_login (login)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS CONFIGURACAO (
    id_config INT NOT NULL AUTO_INCREMENT,
    nome_loja VARCHAR(100) NOT NULL,
    cnpj VARCHAR(18) NULL,
    telefone VARCHAR(20) NULL,
    endereco TEXT NULL,
    logo_url VARCHAR(500) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_config)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS CATEGORIA (
    id_categoria INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(80) NOT NULL,
    descricao TEXT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_categoria),
    UNIQUE KEY uq_categoria_nome (nome)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS PRODUTO (
    id_produto INT NOT NULL AUTO_INCREMENT,
    id_categoria INT NOT NULL,
    nome VARCHAR(120) NOT NULL,
    codigo_barras VARCHAR(50) NOT NULL,
    preco_venda DECIMAL(10,2) NOT NULL,
    preco_custo DECIMAL(10,2) NULL,
    estoque_minimo INT NOT NULL DEFAULT 3,
    foto_url VARCHAR(500) NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_produto),
    UNIQUE KEY uq_produto_cod_barras (codigo_barras),
    INDEX idx_produto_categoria (id_categoria),
    INDEX idx_produto_ativo (ativo),
    CONSTRAINT chk_produto_preco_venda CHECK (preco_venda >= 0),
    CONSTRAINT fk_produto_categoria
        FOREIGN KEY (id_categoria) REFERENCES CATEGORIA(id_categoria)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS PRODUTO_VARIACAO (
    id_variacao INT NOT NULL AUTO_INCREMENT,
    id_produto INT NOT NULL,
    cor VARCHAR(50) NOT NULL DEFAULT 'UNICO',
    tamanho VARCHAR(20) NOT NULL DEFAULT 'UNICO',
    codigo_barras_var VARCHAR(50) NULL,
    qtd_estoque INT NOT NULL DEFAULT 0,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (id_variacao),
    UNIQUE KEY uq_variacao_cod_barras (codigo_barras_var),
    UNIQUE KEY uq_variacao_prod_cor_tam (id_produto, cor, tamanho),
    INDEX idx_variacao_produto (id_produto),
    INDEX idx_variacao_estoque (qtd_estoque),
    CONSTRAINT fk_variacao_produto
        FOREIGN KEY (id_produto) REFERENCES PRODUTO(id_produto)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS SESSAO (
    id_sessao INT NOT NULL AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NULL,
    dispositivo VARCHAR(200) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_em TIMESTAMP NOT NULL,
    PRIMARY KEY (id_sessao),
    UNIQUE KEY uq_sessao_token (token_hash),
    INDEX idx_sessao_usuario (id_usuario),
    INDEX idx_sessao_expira (expira_em),
    CONSTRAINT fk_sessao_usuario
        FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

SHOW TABLES;

DESCRIBE produto;

CREATE TABLE IF NOT EXISTS FORNECEDOR (
    id_fornecedor INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(120) NOT NULL,
    telefone VARCHAR(20) NULL,
    email VARCHAR(120) NULL,
    cnpj VARCHAR(18) NULL,
    observacoes TEXT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_fornecedor),
    UNIQUE KEY uq_fornecedor_cnpj (cnpj)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS CLIENTE (
    id_cliente INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(120) NOT NULL,
    cpf VARCHAR(14) NULL,
    telefone VARCHAR(20) NOT NULL,
    email VARCHAR(120) NULL,
    data_nascimento DATE NULL,
    endereco TEXT NULL,
    consentimento_lgpd BOOLEAN NOT NULL DEFAULT FALSE,
    data_consentimento TIMESTAMP NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_cliente),
    UNIQUE KEY uq_cliente_cpf (cpf),
    INDEX idx_cliente_nome (nome),
    INDEX idx_cliente_telefone (telefone)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS MOV_ESTOQUE (
    id_mov INT NOT NULL AUTO_INCREMENT,
    id_variacao INT NOT NULL,
    id_usuario INT NULL,
    tipo ENUM(
        'entrada',
        'saida',
        'ajuste',
        'perda',
        'devolucao',
        'condicional_retirada',
        'condicional_retorno'
    ) NOT NULL,
    quantidade INT NOT NULL,
    motivo TEXT NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_mov),
    INDEX idx_mov_variacao (id_variacao),
    INDEX idx_mov_usuario (id_usuario),
    INDEX idx_mov_tipo (tipo),
    INDEX idx_mov_data (criado_em),
    INDEX idx_mov_variacao_data (id_variacao, criado_em),
    CONSTRAINT chk_mov_quantidade CHECK (quantidade > 0),
    CONSTRAINT fk_mov_variacao
        FOREIGN KEY (id_variacao) REFERENCES PRODUTO_VARIACAO(id_variacao)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_mov_usuario
        FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

SHOW TABLES;

CREATE TABLE IF NOT EXISTS VENDA (
    id_venda INT NOT NULL AUTO_INCREMENT,
    id_cliente INT NOT NULL,
    id_usuario INT NULL,
    valor_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    desconto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('rascunho','concluida','cancelada') NOT NULL DEFAULT 'rascunho',
    motivo_cancelamento TEXT NULL,
    data_venda TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_cancelamento TIMESTAMP NULL,
    PRIMARY KEY (id_venda),
    INDEX idx_venda_cliente (id_cliente),
    INDEX idx_venda_usuario (id_usuario),
    INDEX idx_venda_status (status),
    INDEX idx_venda_data (data_venda),
    CONSTRAINT chk_venda_valor_total CHECK (valor_total >= 0),
    CONSTRAINT chk_venda_desconto CHECK (desconto >= 0),
    CONSTRAINT fk_venda_cliente
        FOREIGN KEY (id_cliente) REFERENCES CLIENTE(id_cliente)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_venda_usuario
        FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ITEM_VENDA (
    id_item INT NOT NULL AUTO_INCREMENT,
    id_venda INT NOT NULL,
    id_variacao INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) GENERATED ALWAYS AS (quantidade * preco_unitario) STORED,
    PRIMARY KEY (id_item),
    INDEX idx_item_venda (id_venda),
    INDEX idx_item_variacao (id_variacao),
    CONSTRAINT chk_item_quantidade CHECK (quantidade > 0),
    CONSTRAINT chk_item_preco CHECK (preco_unitario >= 0),
    CONSTRAINT fk_item_venda
        FOREIGN KEY (id_venda) REFERENCES VENDA(id_venda)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_item_variacao
        FOREIGN KEY (id_variacao) REFERENCES PRODUTO_VARIACAO(id_variacao)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS VENDA_PAGAMENTO (
    id_pagamento INT NOT NULL AUTO_INCREMENT,
    id_venda INT NOT NULL,
    id_credito INT NULL,
    forma_pagamento ENUM(
        'dinheiro',
        'pix',
        'cartao_debito',
        'cartao_credito',
        'promissoria',
        'credito_loja'
    ) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    parcelas INT NOT NULL DEFAULT 1,
    valor_recebido DECIMAL(10,2) NULL,
    troco DECIMAL(10,2) GENERATED ALWAYS AS (
        CASE WHEN valor_recebido IS NOT NULL
             THEN GREATEST(0, valor_recebido - valor)
             ELSE 0 END
    ) STORED,
    status ENUM('ativo','estornado') NOT NULL DEFAULT 'ativo',
    PRIMARY KEY (id_pagamento),
    INDEX idx_pgto_venda (id_venda),
    INDEX idx_pgto_forma (forma_pagamento),
    INDEX idx_pgto_credito (id_credito),
    CONSTRAINT chk_pgto_valor CHECK (valor > 0),
    CONSTRAINT chk_pgto_parcelas CHECK (parcelas >= 1),
    CONSTRAINT fk_pgto_venda
        FOREIGN KEY (id_venda) REFERENCES VENDA(id_venda)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS RECEBIMENTO_PREVISTO (
    id_recebimento INT NOT NULL AUTO_INCREMENT,
    id_venda_pagamento INT NOT NULL,
    valor_parcela DECIMAL(10,2) NOT NULL,
    mes_previsto INT NOT NULL,
    ano_previsto INT NOT NULL,
    status ENUM('pendente','recebido','cancelado') NOT NULL DEFAULT 'pendente',
    data_recebimento DATE NULL,
    PRIMARY KEY (id_recebimento),
    INDEX idx_receb_pagamento (id_venda_pagamento),
    INDEX idx_receb_mes_ano (ano_previsto, mes_previsto),
    INDEX idx_receb_status (status),
    CONSTRAINT chk_receb_mes CHECK (mes_previsto BETWEEN 1 AND 12),
    CONSTRAINT chk_receb_ano CHECK (ano_previsto >= 2020),
    CONSTRAINT chk_receb_parcela CHECK (valor_parcela > 0),
    CONSTRAINT fk_receb_pagamento
        FOREIGN KEY (id_venda_pagamento) REFERENCES VENDA_PAGAMENTO(id_pagamento)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SHOW TABLES;

CREATE TABLE IF NOT EXISTS CONDICIONAL (
    id_condicional INT NOT NULL AUTO_INCREMENT,
    id_cliente INT NOT NULL,
    id_usuario INT NULL,
    data_prevista_dev DATE NOT NULL,
    status ENUM(
        'retirado',
        'parcial',
        'parcial_vencido',
        'vencido',
        'devolvido',
        'convertido',
        'cancelado',
        'fechado'
    ) NOT NULL DEFAULT 'retirado',
    tipo_cancelamento ENUM(
        'virou_promissoria',
        'perda',
        'devolvido_informalmente'
    ) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_condicional),
    INDEX idx_cond_cliente (id_cliente),
    INDEX idx_cond_usuario (id_usuario),
    INDEX idx_cond_status (status),
    INDEX idx_cond_data_prev (data_prevista_dev),
    INDEX idx_cond_status_data (status, data_prevista_dev),
    CONSTRAINT fk_cond_cliente
        FOREIGN KEY (id_cliente) REFERENCES CLIENTE(id_cliente)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_cond_usuario
        FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ITEM_CONDICIONAL (
    id_item_cond INT NOT NULL AUTO_INCREMENT,
    id_condicional INT NOT NULL,
    id_variacao INT NOT NULL,
    qtd_retirada INT NOT NULL,
    qtd_devolvida INT NOT NULL DEFAULT 0,
    qtd_comprada INT NOT NULL DEFAULT 0,
    preco_unitario DECIMAL(10,2) NOT NULL,
    status_item ENUM(
        'ativo',
        'devolvido',
        'convertido',
        'perdido'
    ) NOT NULL DEFAULT 'ativo',
    PRIMARY KEY (id_item_cond),
    INDEX idx_item_cond_condicional (id_condicional),
    INDEX idx_item_cond_variacao (id_variacao),
    INDEX idx_item_cond_status (status_item),
    CONSTRAINT chk_item_cond_qtd_retirada CHECK (qtd_retirada > 0),
    CONSTRAINT chk_item_cond_qtd_devolvida CHECK (qtd_devolvida >= 0),
    CONSTRAINT chk_item_cond_qtd_comprada CHECK (qtd_comprada >= 0),
    CONSTRAINT chk_item_cond_preco CHECK (preco_unitario >= 0),
    CONSTRAINT chk_item_cond_movimento CHECK (qtd_devolvida + qtd_comprada <= qtd_retirada),
    CONSTRAINT fk_item_cond_condicional
        FOREIGN KEY (id_condicional) REFERENCES CONDICIONAL(id_condicional)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_item_cond_variacao
        FOREIGN KEY (id_variacao) REFERENCES PRODUTO_VARIACAO(id_variacao)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

SHOW TABLES;

CREATE TABLE IF NOT EXISTS SEQUENCIA_DOCUMENTO (
    id_seq INT NOT NULL AUTO_INCREMENT,
    prefixo VARCHAR(10) NOT NULL DEFAULT 'PROM',
    ultimo_numero INT NOT NULL DEFAULT 0,
    ano INT NOT NULL,
    PRIMARY KEY (id_seq),
    UNIQUE KEY uq_seq_prefixo_ano (prefixo, ano)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS PROMISSORIA (
    id_promissoria INT NOT NULL AUTO_INCREMENT,
    id_venda INT NULL,
    id_condicional INT NULL,
    id_promissoria_origem INT NULL,
    numero_documento VARCHAR(20) NOT NULL,
    sufixo_acordo VARCHAR(5) NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_limite_carencia DATE NULL,
    status ENUM(
        'pendente',
        'carencia',
        'substituida',
        'juridico',
        'pago',
        'cancelado'
    ) NOT NULL DEFAULT 'pendente',
    status_anterior VARCHAR(20) NULL,
    status_documento ENUM(
        'gerado',
        'impresso',
        'assinado',
        'digitalizado'
    ) NOT NULL DEFAULT 'gerado',
    url_documento VARCHAR(500) NULL,
    data_pagamento DATE NULL,
    data_envio_juridico DATE NULL,
    PRIMARY KEY (id_promissoria),
    UNIQUE KEY uq_prom_numero (numero_documento),
    INDEX idx_prom_venda (id_venda),
    INDEX idx_prom_condicional (id_condicional),
    INDEX idx_prom_origem (id_promissoria_origem),
    INDEX idx_prom_status (status),
    INDEX idx_prom_vencimento (data_vencimento),
    INDEX idx_prom_status_vencimento (status, data_vencimento),
    CONSTRAINT chk_prom_valor CHECK (valor_total > 0),
    CONSTRAINT fk_prom_venda
        FOREIGN KEY (id_venda) REFERENCES VENDA(id_venda)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_prom_condicional
        FOREIGN KEY (id_condicional) REFERENCES CONDICIONAL(id_condicional)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_prom_origem
        FOREIGN KEY (id_promissoria_origem) REFERENCES PROMISSORIA(id_promissoria)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS FINANCEIRO (
    id_financeiro INT NOT NULL AUTO_INCREMENT,
    id_venda INT NULL,
    id_fornecedor INT NULL,
    id_promissoria INT NULL,
    tipo ENUM(
        'entrada',
        'saida',
        'a_receber',
        'a_pagar',
        'promissoria',
        'estorno'
    ) NOT NULL,
    categoria ENUM(
        'venda',
        'compra',
        'despesa',
        'estorno',
        'perda',
        'outros'
    ) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_vencimento DATE NULL,
    data_pagamento DATE NULL,
    descricao TEXT NULL,
    nome_fornecedor VARCHAR(120) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_financeiro),
    INDEX idx_fin_venda (id_venda),
    INDEX idx_fin_fornecedor (id_fornecedor),
    INDEX idx_fin_promissoria (id_promissoria),
    INDEX idx_fin_tipo (tipo),
    INDEX idx_fin_categoria (categoria),
    INDEX idx_fin_data_venc (data_vencimento),
    INDEX idx_fin_tipo_data (tipo, criado_em),
    CONSTRAINT chk_fin_valor CHECK (valor > 0),
    CONSTRAINT chk_fin_origem_exclusiva CHECK (
        (id_venda IS NOT NULL AND id_fornecedor IS NULL AND id_promissoria IS NULL)
        OR (id_venda IS NULL AND id_fornecedor IS NOT NULL AND id_promissoria IS NULL)
        OR (id_venda IS NULL AND id_fornecedor IS NULL AND id_promissoria IS NOT NULL)
        OR (id_venda IS NULL AND id_fornecedor IS NULL AND id_promissoria IS NULL)
    ),
    CONSTRAINT fk_fin_venda
        FOREIGN KEY (id_venda) REFERENCES VENDA(id_venda)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_fin_fornecedor
        FOREIGN KEY (id_fornecedor) REFERENCES FORNECEDOR(id_fornecedor)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_fin_promissoria
        FOREIGN KEY (id_promissoria) REFERENCES PROMISSORIA(id_promissoria)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS DEVOLUCAO (
    id_devolucao INT NOT NULL AUTO_INCREMENT,
    id_venda INT NULL,
    id_condicional INT NULL,
    id_cliente INT NULL,
    id_usuario INT NULL,
    tipo ENUM(
        'venda',
        'condicional',
        'excecao'
    ) NOT NULL,
    observacao TEXT NULL,
    data_devolucao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_devolucao),
    INDEX idx_dev_venda (id_venda),
    INDEX idx_dev_condicional (id_condicional),
    INDEX idx_dev_cliente (id_cliente),
    INDEX idx_dev_data (data_devolucao),
    CONSTRAINT chk_dev_coerencia_tipo CHECK (
        (tipo = 'venda' AND id_venda IS NOT NULL)
        OR (tipo = 'condicional' AND id_condicional IS NOT NULL)
        OR (tipo = 'excecao')
    ),
    CONSTRAINT fk_dev_venda
        FOREIGN KEY (id_venda) REFERENCES VENDA(id_venda)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_dev_condicional
        FOREIGN KEY (id_condicional) REFERENCES CONDICIONAL(id_condicional)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_dev_cliente
        FOREIGN KEY (id_cliente) REFERENCES CLIENTE(id_cliente)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_dev_usuario
        FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ITEM_DEVOLUCAO (
    id_item_dev INT NOT NULL AUTO_INCREMENT,
    id_devolucao INT NOT NULL,
    id_variacao INT NULL,
    quantidade INT NOT NULL,
    valor_unitario DECIMAL(10,2) NOT NULL,
    descricao_item TEXT NULL,
    PRIMARY KEY (id_item_dev),
    INDEX idx_item_dev_devolucao (id_devolucao),
    INDEX idx_item_dev_variacao (id_variacao),
    CONSTRAINT chk_item_dev_quantidade CHECK (quantidade > 0),
    CONSTRAINT chk_item_dev_valor CHECK (valor_unitario >= 0),
    CONSTRAINT chk_item_dev_excecao CHECK (
        id_variacao IS NOT NULL OR descricao_item IS NOT NULL
    ),
    CONSTRAINT fk_item_dev_devolucao
        FOREIGN KEY (id_devolucao) REFERENCES DEVOLUCAO(id_devolucao)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_item_dev_variacao
        FOREIGN KEY (id_variacao) REFERENCES PRODUTO_VARIACAO(id_variacao)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

SHOW TABLES;

CREATE TABLE IF NOT EXISTS FINANCEIRO (
    id_financeiro INT NOT NULL AUTO_INCREMENT,
    id_venda INT NULL,
    id_fornecedor INT NULL,
    id_promissoria INT NULL,
    tipo ENUM(
        'entrada',
        'saida',
        'a_receber',
        'a_pagar',
        'promissoria',
        'estorno'
    ) NOT NULL,
    categoria ENUM(
        'venda',
        'compra',
        'despesa',
        'estorno',
        'perda',
        'outros'
    ) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_vencimento DATE NULL,
    data_pagamento DATE NULL,
    descricao TEXT NULL,
    nome_fornecedor VARCHAR(120) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_financeiro),
    INDEX idx_fin_venda (id_venda),
    INDEX idx_fin_fornecedor (id_fornecedor),
    INDEX idx_fin_promissoria (id_promissoria),
    INDEX idx_fin_tipo (tipo),
    INDEX idx_fin_categoria (categoria),
    INDEX idx_fin_data_venc (data_vencimento),
    INDEX idx_fin_tipo_data (tipo, criado_em),
    CONSTRAINT chk_fin_valor CHECK (valor > 0),
    CONSTRAINT fk_fin_venda
        FOREIGN KEY (id_venda) REFERENCES VENDA(id_venda)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_fin_fornecedor
        FOREIGN KEY (id_fornecedor) REFERENCES FORNECEDOR(id_fornecedor)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_fin_promissoria
        FOREIGN KEY (id_promissoria) REFERENCES PROMISSORIA(id_promissoria)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS DEVOLUCAO (
    id_devolucao INT NOT NULL AUTO_INCREMENT,
    id_venda INT NULL,
    id_condicional INT NULL,
    id_cliente INT NULL,
    id_usuario INT NULL,
    tipo ENUM('venda','condicional','excecao') NOT NULL,
    observacao TEXT NULL,
    data_devolucao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_devolucao),
    INDEX idx_dev_venda (id_venda),
    INDEX idx_dev_condicional (id_condicional),
    INDEX idx_dev_cliente (id_cliente),
    INDEX idx_dev_data (data_devolucao),
    CONSTRAINT fk_dev_venda
        FOREIGN KEY (id_venda) REFERENCES VENDA(id_venda)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_dev_condicional
        FOREIGN KEY (id_condicional) REFERENCES CONDICIONAL(id_condicional)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_dev_cliente
        FOREIGN KEY (id_cliente) REFERENCES CLIENTE(id_cliente)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_dev_usuario
        FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ITEM_DEVOLUCAO (
    id_item_dev INT NOT NULL AUTO_INCREMENT,
    id_devolucao INT NOT NULL,
    id_variacao INT NULL,
    quantidade INT NOT NULL,
    valor_unitario DECIMAL(10,2) NOT NULL,
    descricao_item TEXT NULL,
    PRIMARY KEY (id_item_dev),
    INDEX idx_item_dev_devolucao (id_devolucao),
    INDEX idx_item_dev_variacao (id_variacao),
    CONSTRAINT chk_item_dev_quantidade CHECK (quantidade > 0),
    CONSTRAINT chk_item_dev_valor CHECK (valor_unitario >= 0),
    CONSTRAINT fk_item_dev_devolucao
        FOREIGN KEY (id_devolucao) REFERENCES DEVOLUCAO(id_devolucao)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_item_dev_variacao
        FOREIGN KEY (id_variacao) REFERENCES PRODUTO_VARIACAO(id_variacao)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

SHOW TABLES;

CREATE TABLE IF NOT EXISTS CREDITO_LOJA (
    id_credito INT NOT NULL AUTO_INCREMENT,
    id_devolucao INT NULL,
    id_cliente INT NOT NULL,
    id_usuario INT NULL,
    origem ENUM(
        'devolucao',
        'cancelamento_venda',
        'manual'
    ) NOT NULL,
    valor_original DECIMAL(10,2) NOT NULL,
    valor_utilizado DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    valor_saldo DECIMAL(10,2) GENERATED ALWAYS AS
        (valor_original - valor_utilizado) STORED,
    data_validade DATE NULL,
    status ENUM(
        'disponivel',
        'utilizado',
        'vencido',
        'cancelado'
    ) NOT NULL DEFAULT 'disponivel',
    motivo TEXT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id_credito),

    INDEX idx_cred_cliente (id_cliente),
    INDEX idx_cred_devolucao (id_devolucao),
    INDEX idx_cred_status (status),
    INDEX idx_cred_validade (data_validade),

    CONSTRAINT chk_cred_valor_original
        CHECK (valor_original > 0),

    CONSTRAINT chk_cred_valor_utilizado
        CHECK (valor_utilizado >= 0),

    CONSTRAINT chk_cred_saldo_consistente
        CHECK (valor_utilizado <= valor_original),

    CONSTRAINT chk_cred_cancelamento_vitalicio
        CHECK (origem <> 'cancelamento_venda' OR data_validade IS NULL),

    CONSTRAINT fk_cred_devolucao
        FOREIGN KEY (id_devolucao)
        REFERENCES DEVOLUCAO(id_devolucao)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_cred_cliente
        FOREIGN KEY (id_cliente)
        REFERENCES CLIENTE(id_cliente)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_cred_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES USUARIO(id_usuario)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS AUDITORIA (
    id_auditoria BIGINT NOT NULL AUTO_INCREMENT,
    id_usuario INT NULL,
    tabela VARCHAR(60) NOT NULL,
    operacao ENUM(
        'INSERT',
        'UPDATE',
        'DELETE'
    ) NOT NULL,
    id_registro INT NOT NULL,
    dados_anteriores JSON NULL,
    dados_novos JSON NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id_auditoria),

    INDEX idx_aud_tabela (tabela),
    INDEX idx_aud_usuario (id_usuario),
    INDEX idx_aud_operacao (operacao),
    INDEX idx_aud_data (criado_em),
    INDEX idx_aud_tabela_reg (tabela, id_registro),

    CONSTRAINT fk_aud_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES USUARIO(id_usuario)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


ALTER TABLE VENDA_PAGAMENTO
ADD CONSTRAINT fk_pgto_credito
FOREIGN KEY (id_credito)
REFERENCES CREDITO_LOJA(id_credito)
ON DELETE SET NULL
ON UPDATE CASCADE;


SHOW TABLES;

DELIMITER $$

CREATE TRIGGER trg_item_venda_before_insert
BEFORE INSERT ON ITEM_VENDA
FOR EACH ROW
BEGIN
    DECLARE v_qtd_estoque INT;
    DECLARE v_ativo TINYINT(1);

    SELECT qtd_estoque, ativo
    INTO v_qtd_estoque, v_ativo
    FROM PRODUTO_VARIACAO
    WHERE id_variacao = NEW.id_variacao
    FOR UPDATE;

    IF v_qtd_estoque IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'RN02: variacao inexistente.';
    END IF;

    IF v_ativo = FALSE THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'RN02: variacao inativa.';
    END IF;

    IF v_qtd_estoque < NEW.quantidade THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'RN02: estoque insuficiente.';
    END IF;
END$$


CREATE TRIGGER trg_item_venda_after_insert
AFTER INSERT ON ITEM_VENDA
FOR EACH ROW
BEGIN
    DECLARE v_id_usuario INT;

    SELECT id_usuario
    INTO v_id_usuario
    FROM VENDA
    WHERE id_venda = NEW.id_venda;

    UPDATE PRODUTO_VARIACAO
    SET qtd_estoque = qtd_estoque - NEW.quantidade
    WHERE id_variacao = NEW.id_variacao;

    INSERT INTO MOV_ESTOQUE (
        id_variacao,
        id_usuario,
        tipo,
        quantidade,
        motivo
    )
    VALUES (
        NEW.id_variacao,
        v_id_usuario,
        'saida',
        NEW.quantidade,
        'Venda'
    );
END$$

DELIMITER ;

SHOW TRIGGERS;

DELIMITER $$

CREATE TRIGGER trg_venda_pagamento_before_insert
BEFORE INSERT ON VENDA_PAGAMENTO
FOR EACH ROW
BEGIN
    DECLARE v_valor_total DECIMAL(10,2);
    DECLARE v_status_venda VARCHAR(20);
    DECLARE v_soma_atual DECIMAL(10,2);

    SELECT valor_total, status
    INTO v_valor_total, v_status_venda
    FROM VENDA
    WHERE id_venda = NEW.id_venda;

    IF v_status_venda <> 'rascunho' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'RN16: pagamento rejeitado.';
    END IF;

    SELECT COALESCE(SUM(valor),0)
    INTO v_soma_atual
    FROM VENDA_PAGAMENTO
    WHERE id_venda = NEW.id_venda
      AND status = 'ativo';

    IF (v_soma_atual + NEW.valor) > v_valor_total THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'RN16: soma ultrapassa valor total.';
    END IF;
END$$


CREATE TRIGGER trg_venda_pagamento_after_insert
AFTER INSERT ON VENDA_PAGAMENTO
FOR EACH ROW
BEGIN
    DECLARE v_valor_total DECIMAL(10,2);
    DECLARE v_soma_ativa DECIMAL(10,2);

    SELECT valor_total
    INTO v_valor_total
    FROM VENDA
    WHERE id_venda = NEW.id_venda;

    SELECT COALESCE(SUM(valor),0)
    INTO v_soma_ativa
    FROM VENDA_PAGAMENTO
    WHERE id_venda = NEW.id_venda
      AND status = 'ativo';

    IF v_soma_ativa = v_valor_total AND v_valor_total > 0 THEN
        UPDATE VENDA
        SET status = 'concluida'
        WHERE id_venda = NEW.id_venda
          AND status = 'rascunho';
    END IF;
END$$


CREATE TRIGGER trg_venda_before_update
BEFORE UPDATE ON VENDA
FOR EACH ROW
BEGIN
    DECLARE v_total_pagamentos INT;

    IF NOT (NEW.desconto <=> OLD.desconto) THEN

        SELECT COUNT(*)
        INTO v_total_pagamentos
        FROM VENDA_PAGAMENTO
        WHERE id_venda = OLD.id_venda
          AND status = 'ativo';

        IF v_total_pagamentos > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'RN11: desconto bloqueado após pagamento.';
        END IF;

    END IF;
END$$

DELIMITER ;

SHOW TRIGGERS;

ALTER TABLE CLIENTE
ADD CONSTRAINT uq_cliente_email UNIQUE (email);


DELIMITER $$

CREATE TRIGGER trg_cliente_before_insert
BEFORE INSERT ON CLIENTE
FOR EACH ROW
BEGIN
    IF NEW.consentimento_lgpd = FALSE OR NEW.consentimento_lgpd IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'RN09/RN20: consentimento LGPD obrigatório.';
    END IF;
END$$


CREATE TRIGGER trg_configuracao_before_insert
BEFORE INSERT ON CONFIGURACAO
FOR EACH ROW
BEGIN
    DECLARE v_total INT;

    SELECT COUNT(*)
    INTO v_total
    FROM CONFIGURACAO;

    IF v_total >= 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'CONFIGURACAO singleton violada.';
    END IF;
END$$


CREATE TRIGGER trg_condicional_before_insert
BEFORE INSERT ON CONDICIONAL
FOR EACH ROW
BEGIN
    IF NEW.data_prevista_dev <= CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'RN13: data futura obrigatória.';
    END IF;
END$$


CREATE TRIGGER trg_condicional_before_update
BEFORE UPDATE ON CONDICIONAL
FOR EACH ROW
BEGIN
    IF NOT (NEW.data_prevista_dev <=> OLD.data_prevista_dev)
       AND NEW.data_prevista_dev <= CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'RN13: prazo não pode voltar.';
    END IF;
END$$

DELIMITER ;


SHOW TRIGGERS;

DESCRIBE financeiro;

SET GLOBAL event_scheduler = ON;

SHOW VARIABLES LIKE 'event_scheduler';

DROP PROCEDURE IF EXISTS anonimizar_cliente;

DELIMITER $$

CREATE PROCEDURE anonimizar_cliente(
    IN p_id_cliente INT,
    IN p_id_usuario INT
)
BEGIN
    DECLARE v_dados_anteriores JSON;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT JSON_OBJECT(
        'nome', nome,
        'cpf', cpf,
        'telefone', telefone,
        'email', email,
        'data_nascimento', DATE_FORMAT(data_nascimento, '%Y-%m-%d'),
        'endereco', endereco,
        'consentimento_lgpd', consentimento_lgpd
    )
    INTO v_dados_anteriores
    FROM CLIENTE
    WHERE id_cliente = p_id_cliente
    FOR UPDATE;

    IF v_dados_anteriores IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'RN23: cliente não encontrado — anonimização bloqueada.';
    END IF;

    UPDATE CLIENTE
    SET nome            = CONCAT('ANONIMIZADO #', p_id_cliente),
        cpf             = NULL,
        telefone        = LPAD(p_id_cliente, 11, '0'),
        email           = NULL,
        data_nascimento = NULL,
        endereco        = NULL
    WHERE id_cliente = p_id_cliente;

    INSERT INTO AUDITORIA (
        id_usuario,
        tabela,
        operacao,
        id_registro,
        dados_anteriores,
        dados_novos
    ) VALUES (
        p_id_usuario,
        'CLIENTE',
        'UPDATE',
        p_id_cliente,
        v_dados_anteriores,
        JSON_OBJECT(
            'nome', CONCAT('ANONIMIZADO #', p_id_cliente),
            'cpf', NULL,
            'telefone', LPAD(p_id_cliente, 11, '0'),
            'email', NULL,
            'data_nascimento', NULL,
            'endereco', NULL,
            'consentimento_lgpd', TRUE
        )
    );

    COMMIT;

END$$

DELIMITER ;

SHOW PROCEDURE STATUS WHERE Db = 'cadoficina';