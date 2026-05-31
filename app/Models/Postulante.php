<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Postulante extends Model
{
    protected $table = 'academico.postulante';

    protected $primaryKey = 'username_postulante';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'username_postulante',
        'correo',
        'ci',
        'nombre',
        'telefono',
        'ciudad',
        'colegio_procedencia',
        'direccion',
        'fecha_nacimiento',
        'genero',
        'cod_titulo_bachiller',
        'estado',
    ];
}
