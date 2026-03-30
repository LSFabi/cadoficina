<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'cliente';
    protected $primaryKey = 'id_cliente';
    public $timestamps = false;

    protected $fillable = [
        'nome',
        'cpf',
        'telefone',
        'email',
        'data_nascimento',
        'endereco',
        'consentimento_lgpd',
        'data_consentimento',
    ];

    public function getRouteKeyName(): string
    {
        return 'id_cliente';
    }
}
