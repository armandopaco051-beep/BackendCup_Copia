<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rol extends Model
{
    protected $table = 'seguridad.rol';

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
}
