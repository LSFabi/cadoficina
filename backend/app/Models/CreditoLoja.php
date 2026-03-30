<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditoLoja extends Model
{
    protected $table = 'credito_loja';
    protected $primaryKey = 'id_credito';
    public $timestamps = false;

    protected $fillable = [
        'id_devolucao',
        'id_cliente',
        'id_usuario',
        'origem',
        'valor_original',
        'valor_utilizado',
        'data_validade',
        'status',
        'motivo',
    ];

    public function devolucao()
    {
        return $this->belongsTo(Devolucao::class, 'id_devolucao', 'id_devolucao');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
