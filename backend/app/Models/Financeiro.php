<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Financeiro extends Model
{
    protected $table = 'financeiro';
    protected $primaryKey = 'id_financeiro';
    public $timestamps = false;

    protected $fillable = [
        'id_venda',
        'id_fornecedor',
        'id_promissoria',
        'tipo',
        'categoria',
        'valor',
        'data_vencimento',
        'data_pagamento',
        'descricao',
        'nome_fornecedor',
    ];

    public function venda()
    {
        return $this->belongsTo(Venda::class, 'id_venda', 'id_venda');
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'id_fornecedor', 'id_fornecedor');
    }

    public function promissoria()
    {
        return $this->belongsTo(Promissoria::class, 'id_promissoria', 'id_promissoria');
    }
}
