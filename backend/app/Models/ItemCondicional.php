<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCondicional extends Model
{
    protected $table = 'item_condicional';
    protected $primaryKey = 'id_item_cond';
    public $timestamps = false;

    protected $fillable = [
        'id_condicional',
        'id_variacao',
        'qtd_retirada',
        'qtd_devolvida',
        'qtd_comprada',
        'preco_unitario',
        'status_item',
    ];

    public function condicional()
    {
        return $this->belongsTo(Condicional::class, 'id_condicional', 'id_condicional');
    }

    public function variacao()
    {
        return $this->belongsTo(ProdutoVariacao::class, 'id_variacao', 'id_variacao');
    }
}
