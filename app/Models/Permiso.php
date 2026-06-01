<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permiso extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'seguridad';

    protected $table = 'permiso';

    protected $primaryKey = 'codigo';

    public $timestamps = false;

    protected $fillable = ['nombre'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Rol::class,
            'seguridad.permiso_rol',
            'codigo_permiso',
            'id_rol',
            'codigo',
            'id',
        );
    }
}
