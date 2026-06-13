<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;

class PeriodoAcademico extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'academico';

    protected $table = 'periodo_academico';

    public $timestamps = false;

    protected $fillable = [
        'semestre',
        'año',
        'anio',
        'nombre',
        'fecha_inicio_preinscripcion',
        'fecha_fin_preinscripcion',
        'fecha_inicio_requisitos',
        'fecha_fin_requisitos',
        'fecha_inicio_pago',
        'fecha_fin_pago',
        'estado',
    ];
}
