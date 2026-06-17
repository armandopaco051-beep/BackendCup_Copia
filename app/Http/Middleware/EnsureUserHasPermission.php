<?php

namespace App\Http\Middleware;

use App\Models\Bitacora;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{   
    // pregunta 2 
    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return $request->expectsJson() || $request->is('api/*')
                ? response()->json(['message' => 'Debes iniciar sesion para acceder a este recurso.'], Response::HTTP_UNAUTHORIZED)
                : redirect('/login');
        }

        $usuario->loadMissing('rol.permisos');

        if ($this->puedeAcceder($usuario, $permisos)) {
            return $next($request);
        }

        Bitacora::registrar(
            'acceso_denegado',
            'seguridad',
            'Intento de acceso sin permiso suficiente.',
            [
                'permisos_requeridos' => $permisos,
                'rol' => $usuario->rol?->nombre,
                'tipo' => $usuario->tipo,
            ],
            $request,
        );

        return $request->expectsJson() || $request->is('api/*')
            ? response()->json(['message' => 'No tienes permiso para realizar esta accion.'], Response::HTTP_FORBIDDEN)
            : abort(Response::HTTP_FORBIDDEN, 'No tienes permiso para acceder a este modulo.');
    }

    private function puedeAcceder($usuario, array $permisos): bool
    {
        if ($usuario->esAdministrador()) {
            return true;
        }

        foreach ($permisos as $permiso) {
            if (str_starts_with($permiso, 'rol:')) {
                if ($usuario->rol?->nombre === substr($permiso, 4)) {
                    return true;
                }

                continue;
            }

            if ($usuario->tienePermiso($permiso)) {
                return true;
            }
        }

        return false;
    }
}
