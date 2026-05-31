<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permiso extends Model
{
    protected $table = 'seguridad.permiso';

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
