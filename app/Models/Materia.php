<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'academico';

    protected $table = 'materia';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'nombre',
        'estado',
    ];
}
