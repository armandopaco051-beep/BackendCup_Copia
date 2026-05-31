<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['username', 'password', 'codigo_rol', 'tipo'])]
#[Hidden(['password'])]
class Usuario extends Authenticatable
{
    protected $table = 'seguridad.usuario';

    protected $primaryKey = 'username';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'codigo_rol', 'id');
    }

    public function postulante(): HasOne
    {
        return $this->hasOne(Postulante::class, 'username_postulante', 'username');
    }

    public function docente(): HasOne
    {
        return $this->hasOne(Docente::class, 'username_docente', 'username');
    }

    public function administrativo(): HasOne
    {
        return $this->hasOne(Administrativo::class, 'username_administrativo', 'username');
    }
}
