<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;

class AsignacionCarrera extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'academico';

    protected $table = 'asignacion_carrera';

    public $timestamps = false;

    protected $fillable = [
        'username_postulante',
        'id_periodo_academico',
        'id_carrera',
        'primera_opcion',
        'segunda_opcion',
        'promedio_final',
        'nota3_promedio',
        'nota2_promedio',
        'nota1_promedio',
        'opcion_asignada',
        'estado',
        'motivo',
        'created_at',
    ];

    protected $casts = [
        'promedio_final' => 'float',
        'id_periodo_academico' => 'integer',
        'nota3_promedio' => 'float',
        'nota2_promedio' => 'float',
        'nota1_promedio' => 'float',
        'opcion_asignada' => 'integer',
    ];
}
