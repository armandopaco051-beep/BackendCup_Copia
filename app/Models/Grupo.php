<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'academico';

    protected $table = 'grupo';

    protected $primaryKey = 'codigo';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'descripcion',
        'cupo_maximo',
        'turno',
        'id_periodo_academico',
        'estado',
        'created_at',
    ];
}
