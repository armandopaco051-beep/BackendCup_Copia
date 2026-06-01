<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;

class PostulanteCarrera extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'academico';

    protected $table = 'postulante_carrera';

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = null;

    protected $fillable = [
        'id_carrera',
        'username_postulante',
        'descripcion',
    ];
}
