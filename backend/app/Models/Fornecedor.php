<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fornecedor extends Model
{
    protected $table = 'fornecedor';
    protected $primaryKey = 'id_fornecedor';
    public $timestamps = false;

    protected $fillable = [
        'nome',
        'telefone',
        'email',
        'cnpj',
        'observacoes',
        'ativo',
    ];
}
