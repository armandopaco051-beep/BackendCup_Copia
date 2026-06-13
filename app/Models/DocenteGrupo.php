<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;

class DocenteGrupo extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'academico';

    protected $table = 'docente_grupo';

    protected $primaryKey = 'username_docente';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'username_docente',
        'codigo_grupo',
        'created_at',
    ];
}
