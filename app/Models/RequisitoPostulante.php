<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisitoPostulante extends Model
{
    protected $table = 'academico.requisito_postulante';

    public $timestamps = false;

    protected $fillable = [
        'username_postulante',
        'ci_entregado',
        'titulo_entregado',
        'libretas_entregadas',
        'observacion',
        'validado_por',
        'fecha_validacion',
    ];

    protected function casts(): array
    {
        return [
            'ci_entregado' => 'boolean',
            'titulo_entregado' => 'boolean',
            'libretas_entregadas' => 'boolean',
            'fecha_validacion' => 'datetime',
        ];
    }
}