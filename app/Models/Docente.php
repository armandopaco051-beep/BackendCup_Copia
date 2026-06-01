<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'academico';

    protected $table = 'docente';

    protected $primaryKey = 'username_docente';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'username_docente',
        'nombre',
        'especializacion',
        'maestria',
    ];
}
