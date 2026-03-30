<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Condicional extends Model
{
    protected $table = 'condicional';
    protected $primaryKey = 'id_condicional';
    public $timestamps = false;

    protected $fillable = [
        'id_cliente',
        'id_usuario',
        'data_prevista_dev',
        'status',
        'tipo_cancelamento',
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
