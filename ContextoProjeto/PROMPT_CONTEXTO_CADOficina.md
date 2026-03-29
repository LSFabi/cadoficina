# PROMPT DE CONTEXTO — PROJETO CADOficina

---

## IDENTIDADE DO PROJETO

Você está trabalhando no projeto **CADOficina**, um sistema de gestão comercial desenvolvido para a empresa **Tania Modas** (loja física de roupas e cosméticos).

- **Autor:** Leonardo Silva Fabiano
- **Instituição:** Toledo Prudente Centro Universitário — curso de Sistemas de Informação
- **Professor referência:** Prof. Alisson Kuhn (disciplina de Engenharia de Software)
- **Documento principal:** ERS — Especificação de Requisitos de Software v4.0
- **Versão atual do documento:** v5.0 (com protótipos inseridos)
- **Localidade:** Presidente Prudente — SP, Brasil
- **Ano:** 2026

---

## OBJETIVO DO SISTEMA

Informatizar e integrar os processos internos da loja Tania Modas, substituindo controles manuais por um sistema web que abranja: vendas com múltiplas formas de pagamento, controle de estoque por variação (cor/tamanho), condicionais com prazo, devoluções com crédito automático, promissórias com ciclo de vida completo, crédito da loja, financeiro categorizado, LGPD e relatórios gerenciais.

---

## INFRAESTRUTURA TÉCNICA

| Item | Especificação |
|---|---|
| Banco de dados | MySQL 8.0 ou MariaDB 10.11 |
| Engine | InnoDB |
| Charset | utf8mb4_unicode_ci |
| Fuso horário | America/Sao_Paulo |
| Hospedagem | VPS dedicado (~R$30/mês) |
| Acesso | Navegador web (computador/notebook da empresa) |
| Automações | EVENT SCHEDULER (diário) |
| Backup | mysqldump diário 02h00 — retenção 30 dias + offsite |
| Hardware | Leitor código de barras USB, impressora térmica, impressora comum |
| Custo inicial | R$580,00 (leitor R$150 + impressora térmica R$350 + etiquetas R$80) |

---

## MODELO DE BANCO DE DADOS v4.0

**22 tabelas, 7 domínios, 35 FKs com ON DELETE definido, 22 triggers/eventos automáticos**

| Domínio | Tabelas | Responsabilidade |
|---|---|---|
| Segurança | USUARIO, SESSAO, CONFIGURACAO | Autenticação com token_hash/IP/dispositivo; configuração singleton |
| Cadastro | CLIENTE, CATEGORIA, PRODUTO, PRODUTO_VARIACAO, FORNECEDOR | Soft delete; LGPD com anonimização; estoque por variação |
| Estoque | MOV_ESTOQUE | Rastreabilidade de todas as movimentações por variação |
| Comercial | VENDA, ITEM_VENDA, VENDA_PAGAMENTO, RECEBIMENTO_PREVISTO | Multipagamento; previsão automática de parcelas de cartão |
| Condicional | CONDICIONAL, ITEM_CONDICIONAL | Prazo obrigatório; status automático via EVENT |
| Financeiro | PROMISSORIA, SEQUENCIA_DOCUMENTO, FINANCEIRO, DEVOLUCAO, ITEM_DEVOLUCAO, CREDITO_LOJA | Acordos mãe-filha; crédito automático; financeiro categorizado |
| Auditoria | AUDITORIA | Registro via triggers de alterações críticas (antes/depois em JSON) |

**Campos técnicos relevantes:**
- `token_hash` — autenticação de sessão com IP e dispositivo
- `consentimento_lgpd` — campo booleano obrigatório em CLIENTE (trigger BEFORE INSERT bloqueia se FALSE)
- `status_anterior` — preserva estado da promissória mãe ao criar filha
- `data_prevista_dev` — NOT NULL em CONDICIONAL (trigger bloqueia sem prazo)
- `SELECT FOR UPDATE` — usado na numeração sequencial de promissórias (SEQUENCIA_DOCUMENTO)
- `troco` — coluna GENERATED ALWAYS AS (valor_recebido - valor) em VENDA_PAGAMENTO — apenas informativo

---

## ATORES E PERFIS DE ACESSO

| Perfil | Acesso |
|---|---|
| **Operador de Vendas** | PDV, Condicionais, Devoluções, Produtos, Clientes (inserção/consulta), Estoque, Dashboard (parcial: Vendas do Dia, Estoque Crítico, Condicionais Abertos), Relatório de Estoque |
| **Proprietária** | Acesso completo — todos os módulos incluindo Promissórias, Financeiro, Créditos da Loja, Configurações, Anonimização LGPD, todos os relatórios e todos os 8 indicadores do Dashboard |

---

## 27 CASOS DE USO

### Segurança
| ID | Nome |
|---|---|
| RF_SEC01 | Realizar Login |
| RF_SEC02 | Realizar Logout |

### Cadastro (Básicos)
| ID | Nome |
|---|---|
| RF_B01 | Gerenciar Produtos (CRUD + variações + soft delete) |
| RF_B01b | Gerenciar Categorias (CRUD + soft delete) |
| RF_B02 | Gerenciar Clientes (LGPD + anonimização) |
| RF_B03 | Gerenciar Movimentação de Estoque |
| RF_B04 | Gerenciar Financeiro |
| RF_B05 | Gerenciar Fornecedores |
| RF_B06 | Gerenciar Configurações do Sistema (singleton) |

### Fundamentais
| ID | Nome |
|---|---|
| RF_F01 | Registrar Venda (PDV multipagamento) |
| RF_F01b | Cancelar Venda (estorno em cascata completo) |
| RF_F02 | Registrar Condicional (prazo obrigatório) |
| RF_F02b | Fechar / Cancelar Condicional |
| RF_F03 | Registrar Devolução (crédito automático) |
| RF_F04 | Consultar Estoque por Variação |
| RF_F05 | Emitir Promissória (numeração sequencial + impressão 2 vias) |
| RF_F05b | Registrar Acordo de Promissória (mãe-filha) |
| RF_F05c | Encaminhar Promissória ao Jurídico |
| RF_F05d | Quitar Promissória |
| RF_F06 | Registrar Crédito da Loja Manual |
| RF_F06b | Consultar Créditos do Cliente |

### Saída (Relatórios)
| ID | Nome |
|---|---|
| RF_S01 | Emitir Relatório de Vendas |
| RF_S02 | Emitir Relatório de Estoque |
| RF_S03 | Emitir Relatório Financeiro |
| RF_S04 | Exibir Dashboard (8 indicadores) |
| RF_S05 | Emitir Relatório de Devoluções |
| RF_S06 | Emitir Relatório de Promissórias |

---

## FORMAS DE PAGAMENTO (PDV)

Dinheiro, PIX, Cartão Débito, Cartão Crédito (parcelado), Promissória, Crédito da Loja — podem ser combinadas simultaneamente em uma única venda.

---

## 25 REGRAS DE NEGÓCIO

| ID | Regra |
|---|---|
| RN01 | Venda confirmada baixa estoque da variação via trigger AFTER INSERT em ITEM_VENDA |
| RN02 | Venda bloqueada se estoque da variação = 0 (trigger BEFORE INSERT em ITEM_VENDA) |
| RN03 | Saldo de estoque individualizado por variação (cor + tamanho) em PRODUTO_VARIACAO |
| RN04 | Código de barras único por produto (UNIQUE constraint); variação é opcional e também único quando informado |
| RN05 | Devolução de condicional pode ser parcial (por item) ou total |
| RN06 | Itens não devolvidos no condicional são convertidos em venda ao fechar |
| RN07 | Financeiro registra entradas/saídas com tipo (natureza contábil) e categoria (origem funcional) separados |
| RN08 | Produtos/clientes desativados preservados no histórico via FK com ON DELETE SET NULL ou RESTRICT |
| RN09 | LGPD: consentimento obrigatório via trigger BEFORE INSERT em CLIENTE; anonimização irreversível preserva histórico financeiro pelo ID |
| RN10 | Campo marca não é utilizado no cadastro de produtos |
| RN11 | Desconto em R$ absoluto; bloqueado após inserção do primeiro pagamento na venda |
| RN12 | Promissória tem exatamente uma origem — venda OU condicional (mutuamente exclusivas por CHECK). Filha não tem origem própria, herda da mãe via id_promissoria_origem |
| RN13 | Condicional exige data_prevista_dev NOT NULL; trigger bloqueia abertura sem prazo |
| RN14 | Cancelamento de venda exige motivo obrigatório; estorno em cascata: estoque restaurado, promissórias canceladas, financeiro estornado, previsões de cartão canceladas, créditos restaurados |
| RN15 | Créditos de cancelamento de venda são SEMPRE vitalícios (data_validade = NULL); constraint CHECK impede prazo de validade para esta origem |
| RN16 | Venda só confirmada quando soma dos pagamentos = VENDA.valor_total; trigger BEFORE INSERT em VENDA_PAGAMENTO valida |
| RN17 | Troco = coluna GENERATED ALWAYS AS (valor_recebido - valor) em VENDA_PAGAMENTO; apenas informativo, não lançado no financeiro |
| RN18 | Promissória filha herda o ano da mãe no número (001/2025 → 001-A/2025); sufixo avança alfabeticamente (A, B, C...) |
| RN19 | Ao criar filha, mãe passa para status Substituída e sai dos relatórios ativos; status_anterior preserva o estado anterior |
| RN20 | Consentimento LGPD obrigatório e explícito; trigger BEFORE INSERT em CLIENTE bloqueia se consentimento_lgpd = FALSE |
| RN21 | Todas as 35 FKs têm ON DELETE definido: RESTRICT (dados críticos), SET NULL (histórico preservado) ou CASCADE (dependentes sem significado próprio como RECEBIMENTO_PREVISTO) |
| RN22 | Produtos, usuários, fornecedores e categorias usam soft delete (ativo = FALSE); invisíveis em consultas operacionais mas histórico preservado |
| RN23 | Clientes usam anonimização LGPD (não soft delete); procedure anonimizar_cliente substitui dados pessoais irreversivelmente; operação registrada em AUDITORIA |
| RN24 | Parcelas de cartão de crédito lançadas no FINANCEIRO via EVENT SCHEDULER no 1º dia de cada mês; recupera previsões atrasadas de meses anteriores |
| RN25 | Devolução de exceção (sem origem) permitida sob responsabilidade do operador; produto não cadastrado → operador descreve em descricao_item de ITEM_DEVOLUCAO |

---

## 8 REQUISITOS NÃO FUNCIONAIS

| ID | Categoria | Descrição resumida |
|---|---|---|
| RNF01 | Desempenho | Resposta ≤ 3 segundos; índices em código de barras, status e datas |
| RNF02 | Usabilidade | Operador aprende funções básicas em até 1h de treinamento; PDV com mínimo de cliques |
| RNF03 | Confiabilidade | Transações ACID com rollback automático em operações críticas (venda, cancelamento, acordo) |
| RNF04 | Segurança | Sessões com token hash, IP, dispositivo e prazo de expiração; LGPD; AUDITORIA com dados antes/depois em JSON |
| RNF05 | Disponibilidade | Disponível no horário de funcionamento; VPS com EVENT SCHEDULER ativo em America/Sao_Paulo |
| RNF06 | Manutenibilidade | Código com boas práticas; documentação atualizada por versão |
| RNF07 | Compatibilidade | Leitor de código de barras USB, impressora térmica (2 vias), impressora comum; charset utf8mb4 |
| RNF08 | Backup e Recuperação | mysqldump diário 02h00, retenção 30 dias, cópia offsite obrigatória, teste de restore mensal |

---

## DASHBOARD — 8 INDICADORES (RF_S04)

1. **Vendas do Dia** — quantidade e valor total das vendas concluídas no dia corrente
2. **Faturamento Mensal** — valor total acumulado no mês em curso
3. **Estoque Crítico** — dois sub-indicadores: variações zeradas e variações abaixo do mínimo (exibidos separadamente)
4. **Condicionais Abertos** — quantidade com status aberto, parcial ou vencido
5. **A Receber — Cartão** — previsões de recebimento agrupadas por mês futuro
6. **A Receber — Promissórias** — valor total por mês de vencimento das promissórias em aberto
7. **Acordos Ativos** — quantidade e valor total de promissórias com status Substituída
8. **Perdas do Mês** — soma dos lançamentos com categoria perda no mês corrente

Proprietária visualiza todos os 8. Operador visualiza apenas indicadores 1, 3 e 4.

---

## CICLO DE VIDA DAS PROMISSÓRIAS

```
Emitida → Pendente → Carência (30 dias após vencimento)
                   → Acordo (gera filha; mãe vira Substituída)
                   → Jurídico (data de envio registrada)
                   → Paga (data de quitação registrada)
                   → Cancelada
```

- Numeração: `NNN/AAAA` (ex.: 001/2026) via SELECT FOR UPDATE em SEQUENCIA_DOCUMENTO
- Acordo: mãe `001/2026` → filha `001-A/2026` → `001-B/2026` etc. (máx. 26 acordos por série)
- Filha herda o ano da mãe
- Documento físico: Gerado → Impresso → Assinado → Digitalizado (URL registrada)

---

## STATUS AUTOMÁTICOS VIA EVENT SCHEDULER

| Entidade | Evento | Frequência |
|---|---|---|
| CONDICIONAL | Atualiza status para Vencido ou Parcial Vencido quando data_prevista_dev ultrapassa a data atual | Diário |
| RECEBIMENTO_PREVISTO | Lança parcelas de cartão crédito no FINANCEIRO | 1º dia de cada mês |
| CREDITO_LOJA | Marca créditos com prazo expirado como Vencidos | Diário |

---

## PROTÓTIPOS DE TELA (seção 3.7 do ERS)

| Seção | RF | Tela |
|---|---|---|
| 3.7.1 | RF_B02 | Gerenciar Cliente — checkbox LGPD bloqueia Salvar; modal Anonimizar |
| 3.7.2 | RF_S04 | Dashboard — 8 cards clicáveis em 2 linhas; tabela últimas vendas |
| 3.7.3 | RF_F01 | PDV Registrar Venda — 2 colunas; formas de pagamento dinâmicas |
| 3.7.4 | RF_B02 | Gerenciar Clientes LGPD — referência à 3.7.1 (mesmo RF) |
| 3.7.5 | RF_B01 | Gerenciar Produtos — lista com status colorido; variações |
| 3.7.6 | RF_F02 | Registrar Condicional — bloqueio sem data; lista em aberto |
| 3.7.7 | RF_F03 | Registrar Devolução — cálculo automático; crédito gerado |
| 3.7.8 | RF_F05 | Controle Promissórias — 4 cards; acordo mãe-filha; digitalização |
| 3.7.9 | RF_F06 | Créditos da Loja — 3 cards; extrato; concessão manual; RN15 |

---

## DIAGRAMAS UML DO DOCUMENTO (seção 3 do ERS)

| Seção | Diagrama |
|---|---|
| 3.1 | Diagrama de Caso de Uso — 27 CUs, atores Operador (esquerda) e Proprietária (direita) |
| 3.3.1 | Diagrama de Atividades — RF_F01: Registrar Venda |
| 3.3.2 | Diagrama de Atividades — RF_F02/F03: Registrar e Devolver Condicional |
| 3.4 | Diagrama de Classes — 22 classes com atributos e relacionamentos |
| 3.5.1 | Diagrama de Sequência — RF_F01: Registrar Venda |
| 3.5.2 | Diagrama de Sequência — RF_F02: Registrar Condicional |
| 3.6 | Diagrama Entidade-Relacionamento (DER) — 22 tabelas, 35 FKs |

---

## FORA DO ESCOPO

NF-e, duplicatas fiscais, integrações com governo, e-commerce, contabilidade avançada, aplicativos móveis, conversão automática de troco em crédito da loja.

---

## HISTÓRICO DE REVISÕES

| Versão | Data | Principais alterações |
|---|---|---|
| 1.0 | 02/03/2026 | Criação da versão inicial da ERS |
| 2.0 | 16/03/2026 | Avaliação e ajuste da versão preliminar |
| 3.0 | 22/03/2026 | Conclusão do Capítulo 3 (diagramas, protótipo, RN01-RN10, RNF01-RNF07) |
| 4.0 | 23/03/2026 | Modelo v4.0 completo: 22 tabelas, 27 CUs, RN11-RN25, RNF08 |

---

## ESTADO ATUAL DO DOCUMENTO (ERS v5.0)

- **18 imagens** inseridas nas posições corretas
- **27 casos de uso** completamente especificados (8 linhas cada)
- **9 protótipos de tela** nas seções 3.7.1 a 3.7.9
- **7 diagramas UML** nas seções 3.1, 3.3, 3.4, 3.5 e 3.6
- **25 RNs** + **8 RNFs** no Capítulo 4
- **Pendência:** sumário desatualizado (lista apenas 3.7.1 e 3.7.2 — precisa incluir 3.7.3 a 3.7.9)

---

## INSTRUÇÕES PARA USO DESTE PROMPT

Ao receber este contexto, você deve:
1. Reconhecer que está trabalhando no sistema CADOficina para Tania Modas
2. Respeitar todas as RNs, especialmente RN11 (desconto), RN12 (origem promissória), RN13 (prazo condicional), RN14 (cascata cancelamento), RN15 (crédito vitalício), RN18/RN19 (acordo mãe-filha)
3. Manter consistência com o modelo de banco v4.0 (22 tabelas, nomes exatos das colunas e tabelas)
4. Usar os IDs exatos dos requisitos (RF_SEC01, RF_B01, RF_F01, RF_S04 etc.)
5. Considerar os dois perfis de usuário e suas diferenças de acesso
6. Respeitar os termos técnicos definidos no glossário (CONDICIONAL, PDV, PROMISSÓRIA, CRÉDITO DA LOJA, VPS, EVENT SCHEDULER, SOFT DELETE)
