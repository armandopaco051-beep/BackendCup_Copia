<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostulanteCarrera extends Model
{
    protected $table = 'academico.postulante_carrera';

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = null;

    protected $fillable = [
        'id_carrera',
        'username_postulante',
        'descripcion',
    ];
}
