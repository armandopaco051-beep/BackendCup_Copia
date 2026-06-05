<?php

namespace App\Models;

use App\Models\Concerns\HasPostgresSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class Bitacora extends Model
{
    use HasPostgresSchema;

    protected string $schema = 'seguridad';

    protected $table = 'bitacora';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'username',
        'rol',
        'tipo_usuario',
        'accion',
        'modulo',
        'metodo',
        'ruta',
        'descripcion',
        'ip',
        'user_agent',
        'datos',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public static function registrar(string $accion, ?string $modulo = null, ?string $descripcion = null, array $datos = [], ?Request $request = null): void
    {
        try {
            $request ??= request();
            $usuario = $request?->user();
            $usuario?->loadMissing('rol');

            self::create([
                'username' => $usuario?->username,
                'rol' => $usuario?->rol?->nombre,
                'tipo_usuario' => $usuario?->tipo,
                'accion' => $accion,
                'modulo' => $modulo,
                'metodo' => $request?->method(),
                'ruta' => $request?->path(),
                'descripcion' => $descripcion,
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'datos' => $datos ?: null,
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::debug('No se pudo registrar bitacora.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
