<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Administrativo extends Model
{
    protected $table = 'academico.administrativo';

    protected $primaryKey = 'username_administrativo';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'username_administrativo',
        'nombre',
        'telefono',
        'ciudad',
    ];
}
