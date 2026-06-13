<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;

class DocenteMateria extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'academico';

    protected $table = 'docente_materia';

    protected $primaryKey = 'username_docente';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'username_docente',
        'id_materia',
        'created_at',
    ];
}
