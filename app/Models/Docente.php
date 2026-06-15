<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'correo',
        'telefono',
        'ciudad',
        'titulo_profesional',
        'nro_registro_profesional',
        'estado_profesional',
        'observacion_profesional',
        'max_grupos_periodo',
        'max_horas_semana',
        'especializacion',
        'maestria',
    ];

    public function estaHabilitadoProfesionalmente(): bool
    {
        return $this->estado_profesional === 'habilitado'
            && filled($this->titulo_profesional);
    }

    public function materias(): BelongsToMany
    {
        return $this->belongsToMany(
            Materia::class,
            'academico.docente_materia',
            'username_docente',
            'id_materia',
            'username_docente',
            'id',
        );
    }
}
