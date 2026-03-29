<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracao extends Model
{
    protected $table = 'configuracao';
    protected $primaryKey = 'id_config';
    public $timestamps = false;

    protected $fillable = [
        'nome_loja',
        'cnpj',
        'telefone',
        'endereco',
        'logo_url',
    ];
}
