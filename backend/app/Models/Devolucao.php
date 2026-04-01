<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Devolucao extends Model
{
    protected $table = 'devolucao';
    protected $primaryKey = 'id_devolucao';
    public $timestamps = false;

    protected $fillable = [
        'id_venda',
        'id_condicional',
        'id_cliente',
        'id_usuario',
        'tipo',
        'observacao',
    ];

    public function venda()
    {
        return $this->belongsTo(Venda::class, 'id_venda', 'id_venda');
    }

    public function condicional()
    {
        return $this->belongsTo(Condicional::class, 'id_condicional', 'id_condicional');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function itens()
    {
        return $this->hasMany(ItemDevolucao::class, 'id_devolucao', 'id_devolucao');
    }
}
