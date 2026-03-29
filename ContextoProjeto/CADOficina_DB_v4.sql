-- ============================================================
--  CADOficina v4.0 — Script de Criação do Banco de Dados
--  Sistema de Gestão Comercial — Tania Modas
--  Autor: Leonardo Silva Fabiano
--  Banco: MySQL 8.0 / MariaDB 10.11
--  Engine: InnoDB | Charset: utf8mb4_unicode_ci
--  Fuso: America/Sao_Paulo
--  Gerado em: 27/03/2026
-- ============================================================

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';
SET time_zone = 'America/Sao_Paulo';

-- ------------------------------------------------------------
-- SCHEMA
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS cadoficina
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cadoficina;


-- ============================================================
-- DOMÍNIO 1: SEGURANÇA
-- ============================================================

-- ------------------------------------------------------------
-- CONFIGURACAO (singleton — uma única linha permitida)
-- RN: trigger bloqueia mais de um registro
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS CONFIGURACAO (
    id_config        INT            NOT NULL AUTO_INCREMENT,
    nome_loja        VARCHAR(100)   NOT NULL,
    cnpj             VARCHAR(18)    NULL,
    telefone         VARCHAR(20)    NULL,
    endereco         TEXT           NULL,
    logo_url         VARCHAR(500)   NULL,
    criado_em        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_config)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Configurações da loja — singleton (apenas 1 registro permitido)';


-- ------------------------------------------------------------
-- USUARIO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS USUARIO (
    id_usuario    INT            NOT NULL AUTO_INCREMENT,
    login         VARCHAR(60)    NOT NULL,
    senha_hash    VARCHAR(255)   NOT NULL,
    perfil        ENUM('operador','proprietaria') NOT NULL,
    ativo         TINYINT(1)     NOT NULL DEFAULT 1,
    criado_em     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuario_login (login)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Usuários do sistema com perfil de acesso';


-- ------------------------------------------------------------
-- SESSAO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS SESSAO (
    id_sessao     INT            NOT NULL AUTO_INCREMENT,
    id_usuario    INT            NOT NULL,
    token_hash    VARCHAR(255)   NOT NULL,
    ip            VARCHAR(45)    NULL,
    dispositivo   VARCHAR(200)   NULL,
    criado_em     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_em     DATETIME       NOT NULL,
    PRIMARY KEY (id_sessao),
    UNIQUE KEY uq_sessao_token (token_hash),
    INDEX idx_sessao_usuario (id_usuario),
    INDEX idx_sessao_expira (expira_em),
    CONSTRAINT fk_sessao_usuario
        FOREIGN KEY (id_usuario) REFERENCES USUARIO (id_usuario)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Sessões autenticadas com token, IP e dispositivo';


-- ============================================================
-- DOMÍNIO 2: CADASTRO
-- ============================================================

-- ------------------------------------------------------------
-- CATEGORIA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS CATEGORIA (
    id_categoria  INT            NOT NULL AUTO_INCREMENT,
    nome          VARCHAR(80)    NOT NULL,
    descricao     TEXT           NULL,
    ativo         TINYINT(1)     NOT NULL DEFAULT 1,
    criado_em     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_categoria),
    UNIQUE KEY uq_categoria_nome (nome)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Categorias de produtos — soft delete';


-- ------------------------------------------------------------
-- FORNECEDOR
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS FORNECEDOR (
    id_fornecedor  INT            NOT NULL AUTO_INCREMENT,
    nome           VARCHAR(120)   NOT NULL,
    telefone       VARCHAR(20)    NULL,
    observacoes    TEXT           NULL,
    ativo          TINYINT(1)     NOT NULL DEFAULT 1,
    criado_em      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_fornecedor)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Fornecedores — soft delete';


-- ------------------------------------------------------------
-- PRODUTO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PRODUTO (
    id_produto        INT              NOT NULL AUTO_INCREMENT,
    id_categoria      INT              NULL,
    nome              VARCHAR(120)     NOT NULL,
    codigo_barras     VARCHAR(50)      NOT NULL,
    preco_venda       DECIMAL(10,2)    NOT NULL,
    preco_custo       DECIMAL(10,2)    NULL,
    estoque_minimo    INT              NOT NULL DEFAULT 0,
    foto_url          VARCHAR(500)     NULL,
    ativo             TINYINT(1)       NOT NULL DEFAULT 1,
    criado_em         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_produto),
    UNIQUE KEY uq_produto_cod_barras (codigo_barras),
    INDEX idx_produto_categoria (id_categoria),
    INDEX idx_produto_ativo (ativo),
    CONSTRAINT fk_produto_categoria
        FOREIGN KEY (id_categoria) REFERENCES CATEGORIA (id_categoria)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo de produtos com soft delete — RN04';


-- ------------------------------------------------------------
-- PRODUTO_VARIACAO
-- Estoque individualizado por variação (cor + tamanho) — RN03
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PRODUTO_VARIACAO (
    id_variacao      INT           NOT NULL AUTO_INCREMENT,
    id_produto       INT           NOT NULL,
    cor              VARCHAR(50)   NOT NULL,
    tamanho          VARCHAR(20)   NOT NULL,
    codigo_barras    VARCHAR(50)   NULL,
    qtd_estoque      INT           NOT NULL DEFAULT 0,
    ativo            TINYINT(1)    NOT NULL DEFAULT 1,
    PRIMARY KEY (id_variacao),
    UNIQUE KEY uq_variacao_cod_barras (codigo_barras),
    INDEX idx_variacao_produto (id_produto),
    INDEX idx_variacao_estoque (qtd_estoque),
    CONSTRAINT fk_variacao_produto
        FOREIGN KEY (id_produto) REFERENCES PRODUTO (id_produto)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Variações de produto (cor/tamanho) com estoque individualizado — RN03';


-- ------------------------------------------------------------
-- CLIENTE
-- LGPD: consentimento obrigatório — RN09, RN20
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS CLIENTE (
    id_cliente            INT           NOT NULL AUTO_INCREMENT,
    nome                  VARCHAR(120)  NOT NULL,
    telefone              VARCHAR(20)   NOT NULL,
    cpf                   VARCHAR(14)   NULL,
    email                 VARCHAR(120)  NULL,
    data_nascimento       DATE          NULL,
    endereco              TEXT          NULL,
    consentimento_lgpd    TINYINT(1)    NOT NULL DEFAULT 0,
    data_consentimento    DATETIME      NULL,
    criado_em             DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_cliente),
    UNIQUE KEY uq_cliente_cpf (cpf),
    INDEX idx_cliente_nome (nome),
    INDEX idx_cliente_telefone (telefone)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Clientes com controle LGPD — anonimização em vez de exclusão — RN09, RN20, RN23';


-- ============================================================
-- DOMÍNIO 3: ESTOQUE
-- ============================================================

-- ------------------------------------------------------------
-- MOV_ESTOQUE
-- Rastreabilidade completa de todas as movimentações
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS MOV_ESTOQUE (
    id_mov        INT           NOT NULL AUTO_INCREMENT,
    id_variacao   INT           NOT NULL,
    id_usuario    INT           NULL,
    id_devolucao  INT           NULL,
    tipo          ENUM('entrada','saida','ajuste','perda','devolucao','condicional_retirada','condicional_retorno') NOT NULL,
    quantidade    INT           NOT NULL,
    motivo        TEXT          NULL,
    criado_em     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_mov),
    INDEX idx_mov_variacao (id_variacao),
    INDEX idx_mov_usuario (id_usuario),
    INDEX idx_mov_tipo (tipo),
    INDEX idx_mov_data (criado_em),
    CONSTRAINT fk_mov_variacao
        FOREIGN KEY (id_variacao) REFERENCES PRODUTO_VARIACAO (id_variacao)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_mov_usuario
        FOREIGN KEY (id_usuario) REFERENCES USUARIO (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Rastreabilidade de todas as movimentações de estoque por variação';


-- ============================================================
-- DOMÍNIO 4: COMERCIAL
-- ============================================================

-- ------------------------------------------------------------
-- VENDA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS VENDA (
    id_venda              INT              NOT NULL AUTO_INCREMENT,
    id_cliente            INT              NULL,
    id_usuario            INT              NULL,
    valor_total           DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
    desconto              DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
    status                ENUM('rascunho','concluida','cancelada') NOT NULL DEFAULT 'rascunho',
    motivo_cancelamento   TEXT             NULL,
    data_venda            DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_cancelamento     DATETIME         NULL,
    PRIMARY KEY (id_venda),
    INDEX idx_venda_cliente (id_cliente),
    INDEX idx_venda_usuario (id_usuario),
    INDEX idx_venda_status (status),
    INDEX idx_venda_data (data_venda),
    CONSTRAINT fk_venda_cliente
        FOREIGN KEY (id_cliente) REFERENCES CLIENTE (id_cliente)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_venda_usuario
        FOREIGN KEY (id_usuario) REFERENCES USUARIO (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Vendas com múltiplas formas de pagamento simultâneas';


-- ------------------------------------------------------------
-- ITEM_VENDA
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ITEM_VENDA (
    id_item          INT              NOT NULL AUTO_INCREMENT,
    id_venda         INT              NOT NULL,
    id_variacao      INT              NOT NULL,
    quantidade       INT              NOT NULL,
    preco_unitario   DECIMAL(10,2)    NOT NULL,
    subtotal         DECIMAL(10,2)    GENERATED ALWAYS AS (quantidade * preco_unitario) STORED,
    PRIMARY KEY (id_item),
    INDEX idx_item_venda (id_venda),
    INDEX idx_item_variacao (id_variacao),
    CONSTRAINT fk_item_venda
        FOREIGN KEY (id_venda) REFERENCES VENDA (id_venda)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_item_variacao
        FOREIGN KEY (id_variacao) REFERENCES PRODUTO_VARIACAO (id_variacao)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Itens de venda com subtotal gerado automaticamente';


-- ------------------------------------------------------------
-- VENDA_PAGAMENTO
-- Troco = coluna gerada — RN17
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS VENDA_PAGAMENTO (
    id_pagamento      INT              NOT NULL AUTO_INCREMENT,
    id_venda          INT              NOT NULL,
    id_credito        INT              NULL,
    forma_pagamento   ENUM('dinheiro','pix','cartao_debito','cartao_credito','promissoria','credito_loja') NOT NULL,
    valor             DECIMAL(10,2)    NOT NULL,
    parcelas          INT              NOT NULL DEFAULT 1,
    valor_recebido    DECIMAL(10,2)    NULL COMMENT 'Preenchido apenas para pagamento em dinheiro',
    troco             DECIMAL(10,2)    GENERATED ALWAYS AS (
                          CASE WHEN valor_recebido IS NOT NULL
                               THEN GREATEST(0, valor_recebido - valor)
                               ELSE 0 END
                      ) STORED COMMENT 'Calculado automaticamente — apenas informativo — RN17',
    status            ENUM('ativo','estornado') NOT NULL DEFAULT 'ativo',
    PRIMARY KEY (id_pagamento),
    INDEX idx_pgto_venda (id_venda),
    INDEX idx_pgto_forma (forma_pagamento),
    CONSTRAINT fk_pgto_venda
        FOREIGN KEY (id_venda) REFERENCES VENDA (id_venda)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Pagamentos da venda — troco gerado automaticamente (RN17)';


-- ------------------------------------------------------------
-- RECEBIMENTO_PREVISTO
-- Parcelas de cartão crédito — lançadas via EVENT no 1º do mês (RN24)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS RECEBIMENTO_PREVISTO (
    id_recebimento       INT              NOT NULL AUTO_INCREMENT,
    id_venda_pagamento   INT              NOT NULL,
    valor_parcela        DECIMAL(10,2)    NOT NULL,
    mes_previsto         INT              NOT NULL COMMENT 'Mês de previsão (1-12)',
    ano_previsto         INT              NOT NULL,
    status               ENUM('pendente','recebido','cancelado') NOT NULL DEFAULT 'pendente',
    data_recebimento     DATE             NULL,
    PRIMARY KEY (id_recebimento),
    INDEX idx_receb_pagamento (id_venda_pagamento),
    INDEX idx_receb_mes_ano (ano_previsto, mes_previsto),
    INDEX idx_receb_status (status),
    CONSTRAINT fk_receb_pagamento
        FOREIGN KEY (id_venda_pagamento) REFERENCES VENDA_PAGAMENTO (id_pagamento)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Previsão de recebimento de parcelas de cartão — lançadas via EVENT (RN24)';


-- ============================================================
-- DOMÍNIO 5: CONDICIONAL
-- ============================================================

-- ------------------------------------------------------------
-- CONDICIONAL
-- data_prevista_dev NOT NULL obrigatório — RN13
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS CONDICIONAL (
    id_condicional       INT          NOT NULL AUTO_INCREMENT,
    id_cliente           INT          NOT NULL,
    id_usuario           INT          NULL,
    data_prevista_dev    DATE         NOT NULL COMMENT 'Obrigatório — trigger bloqueia sem prazo — RN13',
    status               ENUM('aberto','parcial','parcial_vencido','vencido','fechado','devolvido','cancelado') NOT NULL DEFAULT 'aberto',
    tipo_cancelamento    ENUM('virou_promissoria','perda','devolvido_informalmente') NULL,
    criado_em            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_condicional),
    INDEX idx_cond_cliente (id_cliente),
    INDEX idx_cond_usuario (id_usuario),
    INDEX idx_cond_status (status),
    INDEX idx_cond_data_prev (data_prevista_dev),
    CONSTRAINT fk_cond_cliente
        FOREIGN KEY (id_cliente) REFERENCES CLIENTE (id_cliente)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_cond_usuario
        FOREIGN KEY (id_usuario) REFERENCES USUARIO (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Condicionais com prazo obrigatório e status automático via EVENT — RN13';


-- ------------------------------------------------------------
-- ITEM_CONDICIONAL
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ITEM_CONDICIONAL (
    id_item_cond    INT       NOT NULL AUTO_INCREMENT,
    id_condicional  INT       NOT NULL,
    id_variacao     INT       NOT NULL,
    qtd_retirada    INT       NOT NULL,
    qtd_devolvida   INT       NOT NULL DEFAULT 0,
    qtd_comprada    INT       NOT NULL DEFAULT 0,
    status_item     ENUM('aberto','devolvido','comprado','perdido') NOT NULL DEFAULT 'aberto',
    PRIMARY KEY (id_item_cond),
    INDEX idx_item_cond_condicional (id_condicional),
    INDEX idx_item_cond_variacao (id_variacao),
    CONSTRAINT fk_item_cond_condicional
        FOREIGN KEY (id_condicional) REFERENCES CONDICIONAL (id_condicional)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_item_cond_variacao
        FOREIGN KEY (id_variacao) REFERENCES PRODUTO_VARIACAO (id_variacao)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Itens do condicional com controle individual de devolução — RN05';


-- ============================================================
-- DOMÍNIO 6: FINANCEIRO
-- ============================================================

-- ------------------------------------------------------------
-- SEQUENCIA_DOCUMENTO
-- Numeração de promissórias com SELECT FOR UPDATE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS SEQUENCIA_DOCUMENTO (
    id_seq         INT          NOT NULL AUTO_INCREMENT,
    prefixo        VARCHAR(10)  NOT NULL DEFAULT 'PROM',
    ultimo_numero  INT          NOT NULL DEFAULT 0,
    ano            INT          NOT NULL,
    PRIMARY KEY (id_seq),
    UNIQUE KEY uq_seq_prefixo_ano (prefixo, ano)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Controle de numeração sequencial de promissórias por ano';


-- ------------------------------------------------------------
-- PROMISSORIA
-- Origem exclusiva: venda OU condicional — RN12
-- Filha herda ano da mãe — RN18
-- Mãe vira Substituída ao criar filha — RN19
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PROMISSORIA (
    id_promissoria          INT              NOT NULL AUTO_INCREMENT,
    id_venda                INT              NULL,
    id_condicional          INT              NULL,
    id_promissoria_origem   INT              NULL COMMENT 'Preenchido na filha — aponta para a mãe',
    numero_documento        VARCHAR(20)      NOT NULL COMMENT 'Formato: NNN/AAAA ou NNN-A/AAAA',
    valor_total             DECIMAL(10,2)    NOT NULL,
    data_vencimento         DATE             NOT NULL,
    data_limite_carencia    DATE             NULL COMMENT '30 dias após vencimento',
    status                  ENUM('pendente','carencia','substituida','juridico','pago','cancelado') NOT NULL DEFAULT 'pendente',
    status_anterior         VARCHAR(20)      NULL COMMENT 'Preserva status antes de virar Substituída — RN19',
    status_documento        ENUM('gerado','impresso','assinado','digitalizado') NOT NULL DEFAULT 'gerado',
    url_documento           VARCHAR(500)     NULL COMMENT 'URL do documento digitalizado assinado',
    data_pagamento          DATE             NULL,
    data_envio_juridico     DATE             NULL,
    PRIMARY KEY (id_promissoria),
    UNIQUE KEY uq_prom_numero (numero_documento),
    INDEX idx_prom_venda (id_venda),
    INDEX idx_prom_condicional (id_condicional),
    INDEX idx_prom_origem (id_promissoria_origem),
    INDEX idx_prom_status (status),
    INDEX idx_prom_vencimento (data_vencimento),
    CONSTRAINT fk_prom_venda
        FOREIGN KEY (id_venda) REFERENCES VENDA (id_venda)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_prom_condicional
        FOREIGN KEY (id_condicional) REFERENCES CONDICIONAL (id_condicional)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_prom_origem
        FOREIGN KEY (id_promissoria_origem) REFERENCES PROMISSORIA (id_promissoria)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_prom_origem_exclusiva
        CHECK (
            (id_venda IS NOT NULL AND id_condicional IS NULL)
            OR (id_venda IS NULL AND id_condicional IS NOT NULL)
            OR (id_venda IS NULL AND id_condicional IS NULL AND id_promissoria_origem IS NOT NULL)
        )
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Promissórias com ciclo de vida completo e acordos mãe-filha — RN12, RN18, RN19';


-- ------------------------------------------------------------
-- FINANCEIRO
-- Lançamentos categorizados
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS FINANCEIRO (
    id_financeiro    INT              NOT NULL AUTO_INCREMENT,
    id_venda         INT              NULL,
    id_fornecedor    INT              NULL,
    tipo             ENUM('entrada','saida','a_receber','a_pagar','promissoria','estorno') NOT NULL,
    categoria        ENUM('venda','compra','despesa','estorno','perda','outros') NOT NULL,
    valor            DECIMAL(10,2)    NOT NULL,
    data_vencimento  DATE             NULL,
    data_pagamento   DATE             NULL,
    descricao        TEXT             NULL,
    nome_fornecedor  VARCHAR(120)     NULL COMMENT 'Preserva nome mesmo com fornecedor desativado',
    criado_em        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_financeiro),
    INDEX idx_fin_venda (id_venda),
    INDEX idx_fin_fornecedor (id_fornecedor),
    INDEX idx_fin_tipo (tipo),
    INDEX idx_fin_categoria (categoria),
    INDEX idx_fin_data_venc (data_vencimento),
    CONSTRAINT fk_fin_venda
        FOREIGN KEY (id_venda) REFERENCES VENDA (id_venda)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_fin_fornecedor
        FOREIGN KEY (id_fornecedor) REFERENCES FORNECEDOR (id_fornecedor)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Lançamentos financeiros categorizados — RN07, RN24';


-- ------------------------------------------------------------
-- DEVOLUCAO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS DEVOLUCAO (
    id_devolucao      INT       NOT NULL AUTO_INCREMENT,
    id_venda          INT       NULL,
    id_condicional    INT       NULL,
    id_cliente        INT       NULL,
    id_usuario        INT       NULL,
    tipo              ENUM('venda','condicional','excecao') NOT NULL,
    observacao        TEXT      NULL,
    data_devolucao    DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_devolucao),
    INDEX idx_dev_venda (id_venda),
    INDEX idx_dev_condicional (id_condicional),
    INDEX idx_dev_cliente (id_cliente),
    INDEX idx_dev_data (data_devolucao),
    CONSTRAINT fk_dev_venda
        FOREIGN KEY (id_venda) REFERENCES VENDA (id_venda)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_dev_condicional
        FOREIGN KEY (id_condicional) REFERENCES CONDICIONAL (id_condicional)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_dev_cliente
        FOREIGN KEY (id_cliente) REFERENCES CLIENTE (id_cliente)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_dev_usuario
        FOREIGN KEY (id_usuario) REFERENCES USUARIO (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Devoluções de venda, condicional ou exceção — RN25';


-- ------------------------------------------------------------
-- ITEM_DEVOLUCAO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ITEM_DEVOLUCAO (
    id_item_dev      INT              NOT NULL AUTO_INCREMENT,
    id_devolucao     INT              NOT NULL,
    id_variacao      INT              NULL,
    quantidade       INT              NOT NULL,
    valor_unitario   DECIMAL(10,2)    NOT NULL,
    descricao_item   TEXT             NULL COMMENT 'Preenchido quando produto não cadastrado (exceção) — RN25',
    PRIMARY KEY (id_item_dev),
    INDEX idx_item_dev_devolucao (id_devolucao),
    INDEX idx_item_dev_variacao (id_variacao),
    CONSTRAINT fk_item_dev_devolucao
        FOREIGN KEY (id_devolucao) REFERENCES DEVOLUCAO (id_devolucao)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_item_dev_variacao
        FOREIGN KEY (id_variacao) REFERENCES PRODUTO_VARIACAO (id_variacao)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Itens devolvidos — descrição manual para exceções sem produto cadastrado';


-- ------------------------------------------------------------
-- CREDITO_LOJA
-- Créditos de cancelamento SEMPRE vitalícios — RN15
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS CREDITO_LOJA (
    id_credito       INT              NOT NULL AUTO_INCREMENT,
    id_devolucao     INT              NULL,
    id_cliente       INT              NOT NULL,
    id_usuario       INT              NULL,
    origem           ENUM('devolucao','cancelamento_venda','manual') NOT NULL,
    valor_original   DECIMAL(10,2)    NOT NULL,
    valor_utilizado  DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
    valor_saldo      DECIMAL(10,2)    GENERATED ALWAYS AS (valor_original - valor_utilizado) STORED,
    data_validade    DATE             NULL COMMENT 'NULL = vitalício. Cancelamentos SEMPRE NULL — RN15',
    status           ENUM('disponivel','utilizado','vencido','cancelado') NOT NULL DEFAULT 'disponivel',
    motivo           TEXT             NULL,
    criado_em        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_credito),
    INDEX idx_cred_cliente (id_cliente),
    INDEX idx_cred_devolucao (id_devolucao),
    INDEX idx_cred_status (status),
    INDEX idx_cred_validade (data_validade),
    CONSTRAINT fk_cred_devolucao
        FOREIGN KEY (id_devolucao) REFERENCES DEVOLUCAO (id_devolucao)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_cred_cliente
        FOREIGN KEY (id_cliente) REFERENCES CLIENTE (id_cliente)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_cred_usuario
        FOREIGN KEY (id_usuario) REFERENCES USUARIO (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_cred_cancelamento_vitalicio
        CHECK (
            origem <> 'cancelamento_venda' OR data_validade IS NULL
        )
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Créditos da loja — cancelamentos SEMPRE vitalícios (RN15); saldo gerado automaticamente';


-- ============================================================
-- DOMÍNIO 7: AUDITORIA
-- ============================================================

-- ------------------------------------------------------------
-- AUDITORIA
-- Registro automático via triggers de alterações críticas
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS AUDITORIA (
    id_auditoria      BIGINT        NOT NULL AUTO_INCREMENT,
    id_usuario        INT           NULL,
    tabela            VARCHAR(60)   NOT NULL,
    operacao          ENUM('INSERT','UPDATE','DELETE') NOT NULL,
    id_registro       INT           NOT NULL COMMENT 'PK do registro afetado',
    dados_anteriores  JSON          NULL COMMENT 'Estado antes (UPDATE/DELETE)',
    dados_novos       JSON          NULL COMMENT 'Estado depois (INSERT/UPDATE)',
    criado_em         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_auditoria),
    INDEX idx_aud_tabela (tabela),
    INDEX idx_aud_usuario (id_usuario),
    INDEX idx_aud_data (criado_em),
    INDEX idx_aud_operacao (operacao),
    CONSTRAINT fk_aud_usuario
        FOREIGN KEY (id_usuario) REFERENCES USUARIO (id_usuario)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Auditoria automática de operações críticas com dados JSON antes/depois — RNF04';


-- ============================================================
-- FK PENDENTES (adicionadas após criação das tabelas dependentes)
-- ============================================================

ALTER TABLE VENDA_PAGAMENTO
    ADD CONSTRAINT fk_pgto_credito
        FOREIGN KEY (id_credito) REFERENCES CREDITO_LOJA (id_credito)
        ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE MOV_ESTOQUE
    ADD CONSTRAINT fk_mov_devolucao
        FOREIGN KEY (id_devolucao) REFERENCES DEVOLUCAO (id_devolucao)
        ON DELETE SET NULL ON UPDATE CASCADE;


-- ============================================================
-- TRIGGERS
-- ============================================================

DELIMITER $$

-- ------------------------------------------------------------
-- TRG_01: Bloqueia cadastro sem consentimento LGPD — RN20
-- ------------------------------------------------------------
CREATE TRIGGER trg_cliente_lgpd_obrigatorio
BEFORE INSERT ON CLIENTE
FOR EACH ROW
BEGIN
    IF NEW.consentimento_lgpd = FALSE OR NEW.consentimento_lgpd IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Consentimento LGPD obrigatório para cadastro de clientes (RN20).';
    END IF;
    IF NEW.consentimento_lgpd = TRUE THEN
        SET NEW.data_consentimento = NOW();
    END IF;
END$$


-- ------------------------------------------------------------
-- TRG_02: Bloqueia ITEM_VENDA se estoque = 0 — RN02
-- ------------------------------------------------------------
CREATE TRIGGER trg_item_venda_estoque
BEFORE INSERT ON ITEM_VENDA
FOR EACH ROW
BEGIN
    DECLARE v_estoque INT;
    SELECT qtd_estoque INTO v_estoque
    FROM PRODUTO_VARIACAO
    WHERE id_variacao = NEW.id_variacao;

    IF v_estoque < NEW.quantidade THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Estoque insuficiente para a variação selecionada (RN02).';
    END IF;
END$$


-- ------------------------------------------------------------
-- TRG_03: Baixa estoque ao confirmar item de venda — RN01
-- ------------------------------------------------------------
CREATE TRIGGER trg_item_venda_baixa_estoque
AFTER INSERT ON ITEM_VENDA
FOR EACH ROW
BEGIN
    UPDATE PRODUTO_VARIACAO
    SET qtd_estoque = qtd_estoque - NEW.quantidade
    WHERE id_variacao = NEW.id_variacao;
END$$


-- ------------------------------------------------------------
-- TRG_04: Bloqueia condicional sem data de devolução — RN13
-- ------------------------------------------------------------
CREATE TRIGGER trg_condicional_data_obrigatoria
BEFORE INSERT ON CONDICIONAL
FOR EACH ROW
BEGIN
    IF NEW.data_prevista_dev IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Data prevista de devolução é obrigatória para abertura de condicional (RN13).';
    END IF;
END$$


-- ------------------------------------------------------------
-- TRG_05: Bloqueia pagamento que ultrapasse o total da venda — RN16
-- ------------------------------------------------------------
CREATE TRIGGER trg_pgto_valida_total
BEFORE INSERT ON VENDA_PAGAMENTO
FOR EACH ROW
BEGIN
    DECLARE v_total      DECIMAL(10,2);
    DECLARE v_pago       DECIMAL(10,2);

    SELECT valor_total INTO v_total FROM VENDA WHERE id_venda = NEW.id_venda;
    SELECT COALESCE(SUM(valor), 0) INTO v_pago
    FROM VENDA_PAGAMENTO
    WHERE id_venda = NEW.id_venda AND status = 'ativo';

    IF (v_pago + NEW.valor) > v_total THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Soma dos pagamentos ultrapassa o valor total da venda (RN16).';
    END IF;
END$$


-- ------------------------------------------------------------
-- TRG_06: Singleton de CONFIGURACAO — RF_B06
-- ------------------------------------------------------------
CREATE TRIGGER trg_configuracao_singleton
BEFORE INSERT ON CONFIGURACAO
FOR EACH ROW
BEGIN
    DECLARE v_count INT;
    SELECT COUNT(*) INTO v_count FROM CONFIGURACAO;
    IF v_count >= 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Já existe uma configuração cadastrada. Use UPDATE para alterar (RF_B06 — singleton).';
    END IF;
END$$


-- ------------------------------------------------------------
-- TRG_07: Restaura estoque ao cancelar venda — RN14
-- ------------------------------------------------------------
CREATE TRIGGER trg_venda_cancelamento_estoque
AFTER UPDATE ON VENDA
FOR EACH ROW
BEGIN
    IF NEW.status = 'cancelada' AND OLD.status <> 'cancelada' THEN
        UPDATE PRODUTO_VARIACAO pv
        JOIN ITEM_VENDA iv ON iv.id_variacao = pv.id_variacao
        SET pv.qtd_estoque = pv.qtd_estoque + iv.quantidade
        WHERE iv.id_venda = NEW.id_venda;

        -- Cancela previsões de cartão
        UPDATE RECEBIMENTO_PREVISTO rp
        JOIN VENDA_PAGAMENTO vp ON vp.id_pagamento = rp.id_venda_pagamento
        SET rp.status = 'cancelado'
        WHERE vp.id_venda = NEW.id_venda AND rp.status = 'pendente';

        -- Estorna pagamentos
        UPDATE VENDA_PAGAMENTO
        SET status = 'estornado'
        WHERE id_venda = NEW.id_venda AND status = 'ativo';
    END IF;
END$$


-- ------------------------------------------------------------
-- TRG_08: Auditoria automática em CLIENTE — RNF04
-- ------------------------------------------------------------
CREATE TRIGGER trg_audit_cliente_update
AFTER UPDATE ON CLIENTE
FOR EACH ROW
BEGIN
    INSERT INTO AUDITORIA (id_usuario, tabela, operacao, id_registro, dados_anteriores, dados_novos)
    VALUES (
        NULL,
        'CLIENTE',
        'UPDATE',
        OLD.id_cliente,
        JSON_OBJECT(
            'nome', OLD.nome, 'cpf', OLD.cpf, 'telefone', OLD.telefone,
            'email', OLD.email, 'consentimento_lgpd', OLD.consentimento_lgpd
        ),
        JSON_OBJECT(
            'nome', NEW.nome, 'cpf', NEW.cpf, 'telefone', NEW.telefone,
            'email', NEW.email, 'consentimento_lgpd', NEW.consentimento_lgpd
        )
    );
END$$


-- ------------------------------------------------------------
-- TRG_09: Auditoria automática em VENDA — RNF04
-- ------------------------------------------------------------
CREATE TRIGGER trg_audit_venda_update
AFTER UPDATE ON VENDA
FOR EACH ROW
BEGIN
    IF NEW.status <> OLD.status THEN
        INSERT INTO AUDITORIA (id_usuario, tabela, operacao, id_registro, dados_anteriores, dados_novos)
        VALUES (
            NEW.id_usuario,
            'VENDA',
            'UPDATE',
            OLD.id_venda,
            JSON_OBJECT('status', OLD.status, 'valor_total', OLD.valor_total),
            JSON_OBJECT('status', NEW.status, 'motivo_cancelamento', NEW.motivo_cancelamento)
        );
    END IF;
END$$


-- ------------------------------------------------------------
-- TRG_10: Auditoria em PROMISSORIA — RNF04
-- ------------------------------------------------------------
CREATE TRIGGER trg_audit_promissoria_update
AFTER UPDATE ON PROMISSORIA
FOR EACH ROW
BEGIN
    IF NEW.status <> OLD.status THEN
        INSERT INTO AUDITORIA (id_usuario, tabela, operacao, id_registro, dados_anteriores, dados_novos)
        VALUES (
            NULL,
            'PROMISSORIA',
            'UPDATE',
            OLD.id_promissoria,
            JSON_OBJECT('status', OLD.status, 'numero_documento', OLD.numero_documento),
            JSON_OBJECT('status', NEW.status, 'status_anterior', NEW.status_anterior)
        );
    END IF;
END$$


DELIMITER ;


-- ============================================================
-- EVENTS (EVENT SCHEDULER)
-- ============================================================

-- Garantir que o EVENT SCHEDULER está ativo (executar como root se necessário)
-- SET GLOBAL event_scheduler = ON;

DELIMITER $$

-- ------------------------------------------------------------
-- EVT_01: Atualiza status de condicionais vencidos (diário) — RN13
-- ------------------------------------------------------------
CREATE EVENT IF NOT EXISTS evt_atualiza_condicionais
ON SCHEDULE EVERY 1 DAY
STARTS (CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 1 HOUR)
DO
BEGIN
    -- Totalmente vencidos (todos os itens em aberto e prazo expirado)
    UPDATE CONDICIONAL
    SET status = 'vencido'
    WHERE status IN ('aberto')
      AND data_prevista_dev < CURDATE();

    -- Parcialmente vencidos (alguns itens resolvidos, outros não)
    UPDATE CONDICIONAL
    SET status = 'parcial_vencido'
    WHERE status IN ('parcial')
      AND data_prevista_dev < CURDATE();
END$$


-- ------------------------------------------------------------
-- EVT_02: Lança parcelas de cartão no financeiro (1º dia do mês) — RN24
-- ------------------------------------------------------------
CREATE EVENT IF NOT EXISTS evt_lanca_parcelas_cartao
ON SCHEDULE EVERY 1 MONTH
STARTS (DATE_FORMAT(CURRENT_DATE + INTERVAL 1 MONTH, '%Y-%m-01'))
DO
BEGIN
    DECLARE v_mes INT;
    DECLARE v_ano INT;
    SET v_mes = MONTH(CURDATE());
    SET v_ano = YEAR(CURDATE());

    -- Lança parcelas do mês atual e meses anteriores ainda pendentes
    INSERT INTO FINANCEIRO (id_venda, tipo, categoria, valor, data_vencimento, descricao)
    SELECT
        vp.id_venda,
        'entrada',
        'venda',
        rp.valor_parcela,
        LAST_DAY(CONCAT(rp.ano_previsto, '-', LPAD(rp.mes_previsto, 2, '0'), '-01')),
        CONCAT('Parcela cartão crédito — ', rp.mes_previsto, '/', rp.ano_previsto)
    FROM RECEBIMENTO_PREVISTO rp
    JOIN VENDA_PAGAMENTO vp ON vp.id_pagamento = rp.id_venda_pagamento
    WHERE rp.status = 'pendente'
      AND (rp.ano_previsto < v_ano OR (rp.ano_previsto = v_ano AND rp.mes_previsto <= v_mes));

    -- Marca como recebidos
    UPDATE RECEBIMENTO_PREVISTO
    SET status = 'recebido', data_recebimento = CURDATE()
    WHERE status = 'pendente'
      AND (ano_previsto < v_ano OR (ano_previsto = v_ano AND mes_previsto <= v_mes));
END$$


-- ------------------------------------------------------------
-- EVT_03: Vence créditos com prazo expirado (diário)
-- ------------------------------------------------------------
CREATE EVENT IF NOT EXISTS evt_vence_creditos
ON SCHEDULE EVERY 1 DAY
STARTS (CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 2 HOUR)
DO
BEGIN
    UPDATE CREDITO_LOJA
    SET status = 'vencido'
    WHERE status = 'disponivel'
      AND data_validade IS NOT NULL
      AND data_validade < CURDATE();
END$$


DELIMITER ;


-- ============================================================
-- PROCEDURES
-- ============================================================

DELIMITER $$

-- ------------------------------------------------------------
-- PROC_01: Anonimizar cliente (LGPD) — RN23
-- ------------------------------------------------------------
CREATE PROCEDURE proc_anonimizar_cliente(
    IN p_id_cliente   INT,
    IN p_id_usuario   INT
)
BEGIN
    DECLARE v_nome_atual VARCHAR(120);

    SELECT nome INTO v_nome_atual FROM CLIENTE WHERE id_cliente = p_id_cliente;

    -- Anonimizar dados pessoais
    UPDATE CLIENTE
    SET
        nome                = 'CLIENTE ANÔNIMO',
        cpf                 = NULL,
        telefone            = '00000000000',
        email               = NULL,
        data_nascimento     = NULL,
        endereco            = NULL,
        consentimento_lgpd  = 0,
        data_consentimento  = NULL
    WHERE id_cliente = p_id_cliente;

    -- Registrar em auditoria
    INSERT INTO AUDITORIA (id_usuario, tabela, operacao, id_registro, dados_anteriores, dados_novos)
    VALUES (
        p_id_usuario,
        'CLIENTE',
        'UPDATE',
        p_id_cliente,
        JSON_OBJECT('nome', v_nome_atual, 'acao', 'dados_pessoais_antes_anonimizacao'),
        JSON_OBJECT('nome', 'CLIENTE ANÔNIMO', 'acao', 'anonimizacao_lgpd_executada')
    );
END$$


-- ------------------------------------------------------------
-- PROC_02: Gerar número de promissória (SELECT FOR UPDATE) — RN18
-- ------------------------------------------------------------
CREATE PROCEDURE proc_gerar_numero_promissoria(
    IN  p_ano        INT,
    IN  p_sufixo     VARCHAR(5),
    OUT p_numero     VARCHAR(20)
)
BEGIN
    DECLARE v_ultimo INT;

    START TRANSACTION;

    SELECT ultimo_numero INTO v_ultimo
    FROM SEQUENCIA_DOCUMENTO
    WHERE prefixo = 'PROM' AND ano = p_ano
    FOR UPDATE;

    IF v_ultimo IS NULL THEN
        INSERT INTO SEQUENCIA_DOCUMENTO (prefixo, ultimo_numero, ano)
        VALUES ('PROM', 1, p_ano);
        SET v_ultimo = 1;
    ELSE
        UPDATE SEQUENCIA_DOCUMENTO
        SET ultimo_numero = ultimo_numero + 1
        WHERE prefixo = 'PROM' AND ano = p_ano;
        SET v_ultimo = v_ultimo + 1;
    END IF;

    IF p_sufixo IS NULL OR p_sufixo = '' THEN
        SET p_numero = CONCAT(LPAD(v_ultimo, 3, '0'), '/', p_ano);
    ELSE
        SET p_numero = CONCAT(LPAD(v_ultimo, 3, '0'), '-', p_sufixo, '/', p_ano);
    END IF;

    COMMIT;
END$$


DELIMITER ;


-- ============================================================
-- DADOS INICIAIS
-- ============================================================

-- Usuário padrão administrador (senha: alterar após primeiro acesso)
INSERT INTO USUARIO (login, senha_hash, perfil, ativo)
VALUES ('proprietaria', SHA2('TrocarEstasSenha2026!', 256), 'proprietaria', 1);

INSERT INTO USUARIO (login, senha_hash, perfil, ativo)
VALUES ('operador', SHA2('TrocaestaTemporaria!', 256), 'operador', 1);

-- Categorias padrão para Tania Modas
INSERT INTO CATEGORIA (nome) VALUES ('Roupas');
INSERT INTO CATEGORIA (nome) VALUES ('Cosméticos');

-- Sequência de promissórias para 2026
INSERT INTO SEQUENCIA_DOCUMENTO (prefixo, ultimo_numero, ano)
VALUES ('PROM', 0, 2026);


-- ============================================================
-- RESTAURAR CONFIGURAÇÕES
-- ============================================================
SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;


-- ============================================================
-- FIM DO SCRIPT
-- CADOficina v4.0 — 22 tabelas · 7 domínios · 35 FKs
-- 10 triggers · 3 events · 2 procedures
-- ============================================================
