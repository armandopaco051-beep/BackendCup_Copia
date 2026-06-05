<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;

class Aula extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'academico';

    protected $table = 'aula';

    protected $primaryKey = 'nro_aula';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'nro_aula',
        'tipo',
        'piso',
        'capacidad',
        'estado',
    ];
}
