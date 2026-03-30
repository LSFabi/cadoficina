<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemVenda extends Model
{
    protected $table = 'item_venda';
    protected $primaryKey = 'id_item';
    public $timestamps = false;

    protected $fillable = [
        'id_venda',
        'id_variacao',
        'quantidade',
        'preco_unitario',
        'subtotal',
    ];

    public function venda()
    {
        return $this->belongsTo(Venda::class, 'id_venda', 'id_venda');
    }

    public function variacao()
    {
        return $this->belongsTo(ProdutoVariacao::class, 'id_variacao', 'id_variacao');
    }
}
