<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'seguridad';

    protected $table = 'rol';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = ['nombre'];

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(
            Permiso::class,
            'seguridad.permiso_rol',
            'id_rol',
            'codigo_permiso',
            'id',
            'codigo',
        );
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'codigo_rol', 'id');
    }
}
