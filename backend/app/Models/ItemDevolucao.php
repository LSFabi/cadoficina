<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemDevolucao extends Model
{
    protected $table = 'item_devolucao';
    protected $primaryKey = 'id_item_dev';
    public $timestamps = false;

    protected $fillable = [
        'id_devolucao',
        'id_variacao',
        'quantidade',
        'valor_unitario',
        'descricao_item',
    ];

    public function devolucao()
    {
        return $this->belongsTo(Devolucao::class, 'id_devolucao', 'id_devolucao');
    }

    public function variacao()
    {
        return $this->belongsTo(ProdutoVariacao::class, 'id_variacao', 'id_variacao');
    }
}
