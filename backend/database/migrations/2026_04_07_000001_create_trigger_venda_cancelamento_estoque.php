<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_venda_cancelamento_estoque');

        DB::unprepared('
            CREATE TRIGGER trg_venda_cancelamento_estoque
            AFTER UPDATE ON venda
            FOR EACH ROW
            BEGIN
                -- Variáveis compartilhadas entre os dois blocos aninhados
                DECLARE v_id_variacao   INT;
                DECLARE v_quantidade     INT;
                DECLARE v_id_pagamento   INT;
                DECLARE v_id_credito     INT;
                DECLARE v_valor_pag      DECIMAL(10,2);
                DECLARE v_forma          VARCHAR(50);

                -- Disparar apenas na transição real para cancelada
                IF OLD.status <> \'cancelada\' AND NEW.status = \'cancelada\' THEN

                    -- Bloco 1: Restaurar estoque + registrar mov_estoque por item
                    -- Flag v_done_itens isolada neste escopo — handler não interfere no bloco 2
                    BEGIN
                        DECLARE v_done_itens TINYINT DEFAULT 0;
                        DECLARE cur_itens CURSOR FOR
                            SELECT id_variacao, quantidade
                            FROM item_venda
                            WHERE id_venda = OLD.id_venda;
                        DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done_itens = 1;

                        OPEN cur_itens;
                        loop_itens: LOOP
                            FETCH cur_itens INTO v_id_variacao, v_quantidade;
                            IF v_done_itens THEN LEAVE loop_itens; END IF;

                            UPDATE produto_variacao
                               SET qtd_estoque = qtd_estoque + v_quantidade
                             WHERE id_variacao = v_id_variacao;

                            INSERT INTO mov_estoque (id_variacao, id_usuario, tipo, quantidade, motivo)
                            VALUES (v_id_variacao, OLD.id_usuario, \'entrada\', v_quantidade,
                                    CONCAT(\'Cancelamento da venda #\', OLD.id_venda));
                        END LOOP;
                        CLOSE cur_itens;
                    END;

                    -- Bloco 2: Estornar pagamentos ativos + reverter credito_loja se existir
                    -- Flag v_done_pag isolada neste escopo — handler não interfere no bloco 1
                    BEGIN
                        DECLARE v_done_pag TINYINT DEFAULT 0;
                        DECLARE cur_pag CURSOR FOR
                            SELECT id_pagamento, forma_pagamento, valor, id_credito
                            FROM venda_pagamento
                            WHERE id_venda = OLD.id_venda
                              AND status = \'ativo\';
                        DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done_pag = 1;

                        OPEN cur_pag;
                        loop_pag: LOOP
                            FETCH cur_pag INTO v_id_pagamento, v_forma, v_valor_pag, v_id_credito;
                            IF v_done_pag THEN LEAVE loop_pag; END IF;

                            UPDATE venda_pagamento
                               SET status = \'estornado\'
                             WHERE id_pagamento = v_id_pagamento;

                            IF v_forma = \'credito_loja\' AND v_id_credito IS NOT NULL THEN
                                UPDATE credito_loja
                                   SET valor_utilizado = GREATEST(0, valor_utilizado - v_valor_pag),
                                       status = \'disponivel\'
                                 WHERE id_credito = v_id_credito;
                            END IF;
                        END LOOP;
                        CLOSE cur_pag;
                    END;

                END IF;
            END
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_venda_cancelamento_estoque');
    }
};
