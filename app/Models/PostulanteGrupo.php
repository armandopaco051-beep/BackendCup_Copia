<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;

class PostulanteGrupo extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'academico';

    protected $table = 'postulante_grupo';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'username_postulante',
        'id_grupo',
        'id_periodo_academico',
        'estado',
        'created_at',
    ];
}
