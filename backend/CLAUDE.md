CLAUDE.md — CADOficina

Sistema de Gestão Comercial — Tania Modas
Autor: Leonardo Silva Fabiano | Toledo Prudente Centro Universitário — Sistemas de Informação
Disciplina: Engenharia de Software | Prof. Alisson Kuhn | Presidente Prudente — SP | 2026


1. IDENTIDADE DO PROJETO
Você está trabalhando no CADOficina, sistema web de gestão comercial para a Tania Modas (loja física de roupas e cosméticos). O sistema substitui controles manuais por um sistema integrado que cobre: PDV com multipagamento, estoque por variação (cor/tamanho), condicionais com prazo, devoluções com crédito automático, promissórias com ciclo de vida completo, crédito da loja, financeiro categorizado, conformidade com LGPD e relatórios gerenciais.
Documento de referência: ERS — Especificação de Requisitos de Software v5.0

2. STACK TÉCNICA
CamadaTecnologiaBanco de dadosMySQL 8.0 / MariaDB 10.11Engine / CharsetInnoDB / utf8mb4_unicode_ciFuso horárioAmerica/Sao_PauloHospedagemVPS dedicado (~R$30/mês)FrontendWeb (navegador — computador/notebook da empresa)Automações DBEVENT SCHEDULER (diário / 1º dia do mês)Backupmysqldump diário 02h00 — retenção 30 dias + offsiteHardwareLeitor código de barras USB, impressora térmica, impressora comum

Ao sugerir código: sempre respeite o charset utf8mb4, o fuso America/Sao_Paulo, e a engine InnoDB com suporte a transações ACID.


3. COMANDOS DO PROJETO
bash# Banco de dados
mysql -u root -p cadoficina < CADOficina_DB_v4.sql   # criar/recriar o banco
mysql -u root -p cadoficina < BackupBancoScript.sql  # restaurar backup
mysqldump -u root -p cadoficina > backup_$(date +%Y%m%d).sql  # gerar backup

# Ativar EVENT SCHEDULER (executar como root)
mysql -e "SET GLOBAL event_scheduler = ON;"

# Verificar triggers ativos
mysql -u root -p cadoficina -e "SHOW TRIGGERS;"

# Verificar events ativos
mysql -u root -p cadoficina -e "SHOW EVENTS;"

Antes de qualquer alteração no banco: verifique FKs, triggers e constraints existentes. Nunca drope tabelas sem checar ON DELETE definido.


4. MODELO DE BANCO DE DADOS v4.0
22 tabelas | 7 domínios | 35 FKs | 10 triggers | 3 events | 2 procedures
4.1 Domínios e Tabelas
DomínioTabelasResponsabilidadeSegurançaUSUARIO, SESSAO, CONFIGURACAOAutenticação com token_hash/IP/dispositivo; singleton de configCadastroCLIENTE, CATEGORIA, PRODUTO, PRODUTO_VARIACAO, FORNECEDORSoft delete; LGPD; estoque por variaçãoEstoqueMOV_ESTOQUERastreabilidade de movimentações por variaçãoComercialVENDA, ITEM_VENDA, VENDA_PAGAMENTO, RECEBIMENTO_PREVISTOPDV multipagamento; previsão de parcelas de cartãoCondicionalCONDICIONAL, ITEM_CONDICIONALPrazo obrigatório; status automático via EVENTFinanceiroPROMISSORIA, SEQUENCIA_DOCUMENTO, FINANCEIRO, DEVOLUCAO, ITEM_DEVOLUCAO, CREDITO_LOJAAcordos mãe-filha; crédito automático; financeiro categorizadoAuditoriaAUDITORIARegistro via triggers de alterações críticas (JSON antes/depois)
4.2 Campos Críticos — Referência Rápida
TabelaCampo críticoRegraUSUARIOperfil ENUM('operador','proprietaria')Controle de acesso por perfilSESSAOtoken_hash, ip, dispositivo, expira_emAutenticação segura — RNF04CONFIGURACAO(qualquer)Singleton — trigger bloqueia 2º registroCLIENTEconsentimento_lgpd TINYINT(1)OBRIGATÓRIO TRUE — trigger bloqueia INSERT se FALSE (RN20)CLIENTEdata_consentimentoPreenchida automaticamente pelo trigger ao INSERTPRODUTOcodigo_barras VARCHAR(50) UNIQUEÚnico por produto (RN04)PRODUTO_VARIACAOqtd_estoque, qtd_minimaEstoque individualizado por variação cor+tamanho (RN03)PRODUTO_VARIACAOcodigo_barras_variacao UNIQUEÚnico quando informado (RN04)PRODUTOmarcaNÃO UTILIZADO no cadastro (RN10)VENDAstatus ENUM('pendente','concluida','cancelada')Cancelamento exige motivo_cancelamento (RN14)VENDAdesconto DECIMAL(10,2)Em R$ absoluto; bloqueado após 1º pagamento inserido (RN11)VENDA_PAGAMENTOtroco DECIMAL GENERATED ALWAYS AS (valor_recebido - valor) STOREDApenas informativo, não lançado no financeiro (RN17)VENDA_PAGAMENTOforma_pagamento ENUM(...)Dinheiro, PIX, Cartão Débito, Cartão Crédito, Promissória, Crédito da LojaCONDICIONALdata_prevista_dev DATE NOT NULLObrigatório — trigger bloqueia INSERT sem prazo (RN13)PROMISSORIAid_venda / id_condicionalMutuamente exclusivos — apenas UMA origem (RN12)PROMISSORIAnumero_documento VARCHAR(20)Formato NNN/AAAA ou NNN-X/AAAA (RN18)PROMISSORIAstatus_anteriorPreserva estado da mãe ao criar acordo (RN19)PROMISSORIAid_promissoria_origemFK para mãe; filha herda ano da mãe (RN18)SEQUENCIA_DOCUMENTOultimo_numero, prefixo, anoSELECT FOR UPDATE garante numeração sequencial (RN18)CREDITO_LOJAorigem ENUM('devolucao','cancelamento_venda','manual')Cancelamentos SEMPRE vitalícios — data_validade NULL (RN15)CREDITO_LOJAvalor_saldo DECIMAL GENERATED ALWAYS AS (valor_original - valor_utilizado) STOREDSaldo calculado automaticamenteAUDITORIAdados_anteriores JSON, dados_novos JSONEstado antes/depois de cada alteração crítica (RNF04)
4.3 Triggers Existentes (não recriar)
TriggerTabelaMomentoAçãotrg_cliente_lgpd_obrigatorioCLIENTEBEFORE INSERTBloqueia sem consentimento; seta data_consentimento (RN20)trg_item_venda_estoqueITEM_VENDABEFORE INSERTBloqueia se estoque < quantidade (RN02)trg_item_venda_baixa_estoqueITEM_VENDAAFTER INSERTDecrementa qtd_estoque na variação (RN01)trg_condicional_data_obrigatoriaCONDICIONALBEFORE INSERTBloqueia sem data_prevista_dev (RN13)trg_pgto_valida_totalVENDA_PAGAMENTOBEFORE INSERTBloqueia se soma pagamentos > valor_total (RN16)trg_configuracao_singletonCONFIGURACAOBEFORE INSERTBloqueia 2º registro (RF_B06)trg_venda_cancelamento_estoqueVENDAAFTER UPDATEEstorno em cascata ao cancelar: estoque + previsões + pagamentos (RN14)trg_audit_cliente_updateCLIENTEAFTER UPDATERegistra alterações em AUDITORIA (RNF04)trg_audit_venda_updateVENDAAFTER UPDATERegistra mudança de status em AUDITORIA (RNF04)trg_audit_promissoria_updatePROMISSORIAAFTER UPDATERegistra mudança de status em AUDITORIA (RNF04)
4.4 Events Automáticos (EVENT SCHEDULER deve estar ON)
EventFrequênciaAçãoevt_atualiza_condicionaisDiárioAtualiza status para vencido / parcial_vencido quando prazo ultrapassa data atualevt_lanca_parcelas_cartao1º dia do mêsLança parcelas de cartão crédito pendentes no FINANCEIRO; marca RECEBIMENTO_PREVISTO como recebidoevt_vence_creditosDiárioMarca CREDITO_LOJA com prazo expirado como vencido
4.5 Procedures Existentes
ProcedureParâmetrosAçãoproc_anonimizar_clientep_id_cliente INT, p_id_usuario INTAnonimiza dados pessoais irreversivelmente; registra em AUDITORIA (RN23)proc_gerar_numero_promissoriap_ano INT, p_sufixo VARCHAR(5), OUT p_numero VARCHAR(20)Gera número sequencial com SELECT FOR UPDATE (RN18)

5. PERFIS DE ACESSO
PerfilMódulos permitidosOperadorPDV, Condicionais, Devoluções, Produtos (CRUD), Clientes (insert/consulta), Estoque, Dashboard parcial (indicadores 1, 3, 4), Relatório de EstoqueProprietáriaAcesso completo: todos os módulos + Promissórias, Financeiro, Créditos da Loja, Configurações, Anonimização LGPD, todos os relatórios, todos os 8 indicadores do Dashboard

Regra de ouro: sempre verifique o perfil do usuário logado antes de exibir rotas, botões, e dados. Use o campo USUARIO.perfil.


6. FORMAS DE PAGAMENTO (PDV)
Podem ser combinadas simultaneamente em uma única venda:

Dinheiro
PIX
Cartão Débito
Cartão Crédito (parcelado → gera RECEBIMENTO_PREVISTO)
Promissória (→ gera registro em PROMISSORIA)
Crédito da Loja (→ debita de CREDITO_LOJA)


7. CASOS DE USO (27 no total)
Segurança
IDNomeRF_SEC01Realizar LoginRF_SEC02Realizar Logout
Cadastro
IDNomeRF_B01Gerenciar Produtos (CRUD + variações cor/tamanho + soft delete)RF_B01bGerenciar Categorias (CRUD + soft delete)RF_B02Gerenciar Clientes (LGPD + anonimização)RF_B03Gerenciar Movimentação de EstoqueRF_B04Gerenciar FinanceiroRF_B05Gerenciar FornecedoresRF_B06Gerenciar Configurações do Sistema (singleton)
Fundamentais
IDNomeRF_F01Registrar Venda (PDV multipagamento)RF_F01bCancelar Venda (estorno em cascata completo)RF_F02Registrar Condicional (prazo obrigatório)RF_F02bFechar / Cancelar CondicionalRF_F03Registrar Devolução (crédito automático)RF_F04Consultar Estoque por VariaçãoRF_F05Emitir Promissória (numeração sequencial + impressão 2 vias)RF_F05bRegistrar Acordo de Promissória (mãe-filha)RF_F05cEncaminhar Promissória ao JurídicoRF_F05dQuitar PromissóriaRF_F06Registrar Crédito da Loja ManualRF_F06bConsultar Créditos do Cliente
Relatórios
IDNomeRF_S01Emitir Relatório de VendasRF_S02Emitir Relatório de EstoqueRF_S03Emitir Relatório FinanceiroRF_S04Exibir Dashboard (8 indicadores)RF_S05Emitir Relatório de DevoluçõesRF_S06Emitir Relatório de Promissórias

8. REGRAS DE NEGÓCIO — CRÍTICAS ⚠️

As RNs abaixo têm impacto direto em código. Violá-las quebra o sistema ou gera inconsistência financeira.

Estoque

RN01 — INSERT em ITEM_VENDA → trigger decrementa qtd_estoque da variação.
RN02 — BEFORE INSERT em ITEM_VENDA → bloqueia se qtd_estoque < quantidade pedida.
RN03 — Estoque é por variação (PRODUTO_VARIACAO.qtd_estoque), nunca por produto.
RN04 — codigo_barras é UNIQUE em PRODUTO; codigo_barras_variacao é UNIQUE em PRODUTO_VARIACAO quando informado.

PDV / Venda

RN11 — Desconto em R$ absoluto em VENDA.desconto. Bloqueado após inserção do 1º pagamento.
RN14 — Cancelamento exige motivo_cancelamento. O estorno em cascata (trigger) restaura: estoque + cancela promissórias + estorna lançamentos financeiros + cancela previsões de cartão + restaura créditos utilizados.
RN15 — Créditos gerados por cancelamento de venda têm data_validade = NULL (vitalícios). CHECK constraint impede que esta origem tenha prazo.
RN16 — BEFORE INSERT em VENDA_PAGAMENTO bloqueia se soma pagamentos > VENDA.valor_total.
RN17 — troco em VENDA_PAGAMENTO é GENERATED ALWAYS AS (valor_recebido - valor). Apenas informativo. Não entra no financeiro.

Condicional

RN05 — Devolução de condicional pode ser parcial (por item) ou total.
RN06 — Itens não devolvidos ao fechar um condicional são automaticamente convertidos em venda.
RN13 — data_prevista_dev é NOT NULL em CONDICIONAL. Trigger bloqueia abertura sem prazo.

Promissórias

RN12 — Promissória tem exatamente UMA origem: id_venda OU id_condicional (mutuamente exclusivos por CHECK). Filha não tem origem própria, herda da mãe via id_promissoria_origem.
RN18 — Numeração: NNN/AAAA (ex.: 001/2026). Acordo: 001-A/2026, 001-B/2026… (máx. 26 por série). Filha herda o ano da mãe.
RN19 — Ao criar filha, mãe muda para status Substituída; status_anterior preserva o estado que a mãe tinha.

LGPD

RN09 / RN20 — Consentimento LGPD obrigatório. Trigger BEFORE INSERT em CLIENTE bloqueia se consentimento_lgpd = FALSE.
RN23 — Anonimização via proc_anonimizar_cliente é irreversível. Preserva histórico financeiro pelo id_cliente. Registra em AUDITORIA.

Soft Delete / Histórico

RN08 — Produtos, usuários, fornecedores e categorias usam ativo = FALSE (soft delete). Clientes usam anonimização.
RN22 — Registros com ativo = FALSE ficam invisíveis em consultas operacionais, mas preservados no histórico via FK.

Financeiro

RN07 — FINANCEIRO registra tipo (natureza contábil: entrada/saída) e categoria (origem funcional: venda, condicional, devolução, etc.) separados.
RN24 — Parcelas de cartão crédito são lançadas no FINANCEIRO via EVENT no 1º dia do mês. Recupera atrasos de meses anteriores.
RN25 — Devolução de exceção (sem produto cadastrado) é permitida; operador descreve em descricao_item de ITEM_DEVOLUCAO.


9. REGRAS DE NEGÓCIO — DEMAIS
RNResumoRN10Campo marca NÃO é utilizado no cadastro de produtosRN21Todas as 35 FKs têm ON DELETE definido: RESTRICT (dados críticos), SET NULL (histórico), CASCADE (dependentes sem significado próprio como RECEBIMENTO_PREVISTO)

10. REQUISITOS NÃO FUNCIONAIS
IDCategoriaDescriçãoRNF01DesempenhoResposta ≤ 3 segundos; índices em código de barras, status e datasRNF02UsabilidadeOperador aprende funções básicas em até 1h; PDV com mínimo de cliquesRNF03ConfiabilidadeTransações ACID com rollback automático (venda, cancelamento, acordo)RNF04SegurançaSessões com token_hash + IP + dispositivo + expira_em; LGPD; AUDITORIA JSONRNF05DisponibilidadeDisponível no horário de funcionamento; EVENT SCHEDULER em America/Sao_PauloRNF06ManutenibilidadeCódigo com boas práticas; documentação atualizada por versãoRNF07CompatibilidadeLeitor código de barras USB, impressora térmica 2 vias, impressora comum; utf8mb4RNF08Backupmysqldump diário 02h00, retenção 30 dias, offsite obrigatório, restore mensal

11. DASHBOARD — 8 INDICADORES (RF_S04)
#IndicadorPerfil1Vendas do Dia — qtd e valor total das vendas concluídas no diaAmbos2Faturamento Mensal — valor acumulado no mês em cursoProprietária3Estoque Crítico — variações zeradas + variações abaixo do mínimo (exibidos separadamente)Ambos4Condicionais Abertos — qtd com status aberto, parcial ou vencidoAmbos5A Receber — Cartão — previsões agrupadas por mês futuroProprietária6A Receber — Promissórias — valor por mês de vencimento (status em aberto)Proprietária7Acordos Ativos — qtd e valor das promissórias com status SubstituídaProprietária8Perdas do Mês — soma de lançamentos com categoria = 'perda' no mêsProprietária

12. CICLO DE VIDA DAS PROMISSÓRIAS
Emitida ──► Pendente ──► Carência  (30 dias após vencimento)
                    ├──► Acordo    (gera filha; mãe → Substituída; status_anterior preservado)
                    ├──► Jurídico  (data_envio_juridico registrada)
                    ├──► Paga      (data_quitacao registrada)
                    └──► Cancelada
Ciclo do documento físico:
Gerado ──► Impresso ──► Assinado ──► Digitalizado (url_documento registrada)

13. STATUS AUTOMÁTICOS VIA EVENT SCHEDULER
EntidadeEventoCondiçãoNovo statusCONDICIONALevt_atualiza_condicionaisstatus='aberto' e data_prevista_dev < CURDATE()vencidoCONDICIONALevt_atualiza_condicionaisstatus='parcial' e data_prevista_dev < CURDATE()parcial_vencidoRECEBIMENTO_PREVISTOevt_lanca_parcelas_cartaostatus='pendente' e (ano/mês) ≤ mês atualrecebido + INSERT no FINANCEIROCREDITO_LOJAevt_vence_creditosstatus='disponivel' e data_validade < CURDATE()vencido

14. FORA DO ESCOPO
NF-e, duplicatas fiscais, integrações com governo, e-commerce, contabilidade avançada, aplicativos móveis, conversão automática de troco em crédito da loja.

15. CONVENÇÕES DE DESENVOLVIMENTO
Nomenclatura

Tabelas: MAIÚSCULAS com underscore (PRODUTO_VARIACAO, MOV_ESTOQUE)
Colunas: snake_case minúsculo (qtd_estoque, id_variacao, consentimento_lgpd)
Triggers: trg_<tabela>_<acao> (trg_item_venda_estoque)
Events: evt_<acao> (evt_atualiza_condicionais)
Procedures: proc_<acao> (proc_anonimizar_cliente)
IDs de requisitos: sempre use os IDs exatos (RF_SEC01, RF_B01, RF_F01, RF_S04)

Código SQL

Sempre usar transações ACID para operações críticas (venda, cancelamento, acordo de promissória)
Usar SELECT FOR UPDATE ao acessar SEQUENCIA_DOCUMENTO
Nunca remover triggers existentes sem revisar todas as RNs afetadas
Ao criar novas FKs, definir ON DELETE explicitamente (nunca deixar default)
Charset: utf8mb4_unicode_ci em todas as tabelas e colunas de texto

Backend (quando implementado)

Todas as rotas protegidas devem verificar SESSAO.expira_em > NOW() e SESSAO.token_hash
Rotas exclusivas da proprietária devem verificar USUARIO.perfil = 'proprietaria'
Senhas armazenadas como SHA2(senha, 256) — nunca em texto plano
Soft delete: filtrar sempre por ativo = TRUE em consultas operacionais de PRODUTO, USUARIO, FORNECEDOR, CATEGORIA

Frontend

PDV (RF_F01): 2 colunas; formas de pagamento dinâmicas; desconto bloqueado após 1º pagamento
Clientes (RF_B02): checkbox LGPD bloqueia botão Salvar; modal de Anonimizar com confirmação dupla
Condicionais (RF_F02): campo data_prevista_dev obrigatório; lista de abertos sempre visível
Promissórias (RF_F05): 4 cards de status; acordo exibe mãe e filha; campo URL para digitalização
Créditos (RF_F06): 3 cards; extrato do cliente; concessão manual; informar caráter vitalício ao cancelamento


16. INSTRUÇÕES PARA MODO AUTÔNOMO (Claude Code)
Ao atuar de forma autônoma neste projeto, siga este fluxo:
Antes de implementar qualquer feature:

Identifique o ID do requisito (RF_XXX) e verifique as RNs associadas
Confirme quais tabelas e colunas serão afetadas (seção 4)
Verifique se já existe trigger ou procedure que resolve parte da lógica no banco
Confirme o perfil de acesso exigido (Operador / Proprietária / Ambos)

Ao escrever código SQL:

Sempre use transações (START TRANSACTION ... COMMIT / ROLLBACK) em operações críticas
Teste o comportamento das RNs: insira dados inválidos e confirme que os triggers bloqueiam corretamente
Nunca use DROP TRIGGER sem listar todas as RNs que aquele trigger implementa

Ao escrever código de backend:

Valide no servidor as mesmas regras que os triggers validam no banco (defesa em profundidade)
Sempre retorne mensagens de erro claras mapeando o SQLSTATE 45000 dos triggers para respostas HTTP legíveis
Implemente logout ao detectar sessão expirada ou IP/dispositivo diferente do registrado

Ciclo de desenvolvimento autônomo:
1. Analisar requisito (RF_XXX) e RNs associadas
2. Verificar tabelas/triggers/procedures existentes no banco
3. Implementar a feature
4. Escrever testes (inserções válidas + inserções que devem ser bloqueadas pelas RNs)
5. Executar e validar — corrigir até todos os testes passarem
6. Revisar: consistência de nomes, charset, perfis de acesso, soft delete
7. Confirmar que nenhum trigger existente foi quebrado
Checklist de validação por feature:

 RNs da feature respeitadas?
 Perfil de acesso verificado?
 Soft delete aplicado em consultas operacionais?
 Transação ACID usada em operações críticas?
 Campos GENERATED ALWAYS não foram recebidos como input?
 Consentimento LGPD verificado antes de exibir dados de clientes?
 Charset utf8mb4 preservado em novas colunas/tabelas?
 EVENT SCHEDULER não precisa de novo event?


17. GLOSSÁRIO
TermoDefinição no contexto do CADOficinaPDVPonto de Venda — tela de registro de venda com multipagamentoCONDICIONALProduto(s) levado(s) pelo cliente para experimento com prazo de devoluçãoPROMISSÓRIADocumento de dívida com ciclo de vida: Emitida → Pendente → Carência → Acordo / Jurídico / Paga / CanceladaCRÉDITO DA LOJASaldo gerado por devolução, cancelamento ou concessão manual, utilizável como forma de pagamentoSOFT DELETEInativar registro via ativo = FALSE sem excluir fisicamente do bancoANONIMIZAÇÃO LGPDSubstituição irreversível de dados pessoais do cliente por valores genéricos via proc_anonimizar_clienteVPSServidor privado virtual onde o sistema é hospedadoEVENT SCHEDULERMecanismo do MySQL/MariaDB que executa procedures automaticamente em intervalos definidosACORDO MÃE-FILHARenegociação de promissória: a original (mãe) vira Substituída e uma nova (filha) é emitida com novo prazoVARIAÇÃOCombinação cor + tamanho de um produto; cada variação tem estoque independente