<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venda extends Model
{
    protected $table = 'venda';
    protected $primaryKey = 'id_venda';
    public $timestamps = false;

    protected $fillable = [
        'id_cliente',
        'id_usuario',
        'valor_total',
        'desconto',
        'status',
        'motivo_cancelamento',
        'data_cancelamento',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
