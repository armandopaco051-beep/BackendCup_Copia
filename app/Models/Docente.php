<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    protected $table = 'academico.docente';

    protected $primaryKey = 'username_docente';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'username_docente',
        'nombre',
        'especializacion',
        'maestria',
    ];
}
