<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SequenciaDocumento extends Model
{
    protected $table = 'sequencia_documento';
    protected $primaryKey = 'id_seq';
    public $timestamps = false;

    protected $fillable = [
        'prefixo',
        'ultimo_numero',
        'ano',
    ];
}
