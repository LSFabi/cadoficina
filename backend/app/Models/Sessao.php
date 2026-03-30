<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sessao extends Model
{
    protected $table = 'sessao';
    protected $primaryKey = 'id_sessao';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'token_hash',
        'ip',
        'dispositivo',
        'expira_em',
    ];

    protected $hidden = ['token_hash'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
