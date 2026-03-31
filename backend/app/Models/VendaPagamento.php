<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendaPagamento extends Model
{
    protected $table = 'venda_pagamento';
    protected $primaryKey = 'id_pagamento';
    public $timestamps = false;

    protected $fillable = [
        'id_venda',
        'id_credito',
        'forma_pagamento',
        'valor',
        'parcelas',
        'valor_recebido',
        // troco: STORED GENERATED (banco calcula como valor_recebido - valor)
        'status',
    ];

    public function venda()
    {
        return $this->belongsTo(Venda::class, 'id_venda', 'id_venda');
    }

    public function creditoLoja()
    {
        return $this->belongsTo(CreditoLoja::class, 'id_credito', 'id_credito');
    }
}
