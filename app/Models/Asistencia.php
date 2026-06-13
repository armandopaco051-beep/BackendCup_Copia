<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'academico';

    protected $table = 'asistencia';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'username_postulante',
        'username_docente',
        'id_grupo',
        'id_materia',
        'fecha',
        'estado',
        'observacion',
        'created_at',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];
}
