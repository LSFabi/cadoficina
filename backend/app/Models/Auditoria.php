<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    protected $table = 'auditoria';
    protected $primaryKey = 'id_auditoria';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'tabela',
        'operacao',
        'id_registro',
        'dados_anteriores',
        'dados_novos',
    ];

    protected $casts = [
        'dados_anteriores' => 'array',
        'dados_novos'      => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
