<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Criar UNIQUE primeiro — MySQL transfere o backing da FK fk_dev_venda para este índice
        // 2. Só então dropar o non-unique idx_dev_venda (agora sem dependência da FK)
        // UNIQUE nullable: MySQL permite múltiplos NULL — tipo='excecao' com id_venda=NULL continua funcionando
        DB::unprepared('CREATE UNIQUE INDEX uq_dev_id_venda ON devolucao (id_venda)');
        DB::unprepared('DROP INDEX idx_dev_venda ON devolucao');
    }

    public function down(): void
    {
        // 1. Recriar non-unique antes de dropar o UNIQUE (mesma lógica inversa)
        DB::unprepared('CREATE INDEX idx_dev_venda ON devolucao (id_venda)');
        DB::unprepared('DROP INDEX uq_dev_id_venda ON devolucao');
    }
};
