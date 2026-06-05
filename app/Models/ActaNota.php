<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;

class ActaNota extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'academico';

    protected $table = 'acta_nota';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'nota1',
        'nota2',
        'nota3',
        'promedio',
        'descripcion',
        'id_grupo',
        'id_materia',
        'username_postulante',
    ];

    protected $casts = [
        'nota1' => 'integer',
        'nota2' => 'integer',
        'nota3' => 'integer',
        'promedio' => 'float',
    ];
}
