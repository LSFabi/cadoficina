<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    protected $table = 'produto';
    protected $primaryKey = 'id_produto';
    public $timestamps = false;

    protected $fillable = [
        'id_categoria',
        'nome',
        'codigo_barras',
        'preco_venda',
        'preco_custo',
        'estoque_minimo',
        'foto_url',
        'ativo',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }

    public function variacoes()
    {
        return $this->hasMany(ProdutoVariacao::class, 'id_produto', 'id_produto');
    }
}
