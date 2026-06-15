<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'academico';

    protected $table = 'carrera';

    protected $primaryKey = 'codigo';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'cupo_maximo',
        'estado',
    ];
}
