<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovEstoque extends Model
{
    protected $table = 'mov_estoque';
    protected $primaryKey = 'id_mov';
    public $timestamps = false;

    protected $fillable = [
        'id_variacao',
        'id_usuario',
        'tipo',
        'quantidade',
        'motivo',
    ];

    public function variacao()
    {
        return $this->belongsTo(ProdutoVariacao::class, 'id_variacao', 'id_variacao');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
