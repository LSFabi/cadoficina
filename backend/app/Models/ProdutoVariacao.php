<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdutoVariacao extends Model
{
    protected $table = 'produto_variacao';
    protected $primaryKey = 'id_variacao';
    public $timestamps = false;

    protected $fillable = [
        'id_produto',
        'cor',
        'tamanho',
        'codigo_barras_var',
        'qtd_estoque',
        'ativo',
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'id_produto', 'id_produto');
    }
}
