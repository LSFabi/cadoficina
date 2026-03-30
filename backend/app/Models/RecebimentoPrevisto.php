<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecebimentoPrevisto extends Model
{
    protected $table = 'recebimento_previsto';
    protected $primaryKey = 'id_recebimento';
    public $timestamps = false;

    protected $fillable = [
        'id_venda_pagamento',
        'valor_parcela',
        'mes_previsto',
        'ano_previsto',
        'status',
        'data_recebimento',
    ];

    public function vendaPagamento()
    {
        return $this->belongsTo(VendaPagamento::class, 'id_venda_pagamento', 'id_pagamento');
    }
}
