<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promissoria extends Model
{
    protected $table = 'promissoria';
    protected $primaryKey = 'id_promissoria';
    public $timestamps = false;

    protected $fillable = [
        'id_venda',
        'id_condicional',
        'id_promissoria_origem',
        'numero_documento',
        'sufixo_acordo',
        'valor_total',
        'data_vencimento',
        'data_limite_carencia',
        'status',
        'status_anterior',
        'status_documento',
        'url_documento',
        'data_pagamento',
        'data_envio_juridico',
    ];

    public function venda()
    {
        return $this->belongsTo(Venda::class, 'id_venda', 'id_venda');
    }

    public function condicional()
    {
        return $this->belongsTo(Condicional::class, 'id_condicional', 'id_condicional');
    }

    public function origem()
    {
        return $this->belongsTo(self::class, 'id_promissoria_origem', 'id_promissoria');
    }
}
