<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;

class HorarioGrupo extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'academico';

    protected $table = 'horario_grupo';

    public $timestamps = false;

    protected $fillable = [
        'id_grupo',
        'id_materia',
        'id_aula',
        'username_docente',
        'id_dia',
        'hora_inicio',
        'hora_fin',
        'turno',
        'id_periodo_academico',
        'estado',
        'created_at',
    ];
}
