<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Bitacora;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'username' => ['Las credenciales no son correctas.'],
            ]);
        }

        $request->session()->regenerate();

        /** @var Usuario $usuario */
        $usuario = Auth::user()->load('rol.permisos', 'postulante', 'docente', 'administrativo');

        Bitacora::registrar('iniciar_sesion', 'autenticacion', 'Inicio de sesion en el portal.', [
            'username' => $usuario->username,
            'tipo' => $usuario->tipo,
        ], $request);

        return response()->json([
            'message' => 'Sesion iniciada correctamente.',
            'usuario' => $this->formatUsuario($usuario),
        ]);
    }

    public function me(): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user()->load('rol.permisos', 'postulante', 'docente', 'administrativo');

        return response()->json([
            'usuario' => $this->formatUsuario($usuario),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var Usuario|null $usuario */
        $usuario = Auth::user();

        Bitacora::registrar('cerrar_sesion', 'autenticacion', 'Cierre de sesion en el portal.', [
            'username' => $usuario?->username,
            'tipo' => $usuario?->tipo,
        ], $request);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Sesion cerrada correctamente.',
        ]);
    }

    private function formatUsuario(Usuario $usuario): array
    {
        $perfil = $this->formatPerfil($usuario);

        return [
            'username' => $usuario->username,
            'tipo' => $usuario->tipo,
            'rol' => $usuario->rol ? [
                'id' => $usuario->rol->id,
                'nombre' => $usuario->rol->nombre,
            ] : null,
            'correo' => $perfil['correo'] ?? null,
            'perfil' => $perfil,
            'permisos' => $usuario->rol
                ? $usuario->rol->permisos->pluck('nombre')->values()
                : [],
        ];
    }

    private function formatPerfil(Usuario $usuario): ?array
    {
        return match ($usuario->tipo) {
            'postulante' => $usuario->postulante ? [
                'username' => $usuario->postulante->username_postulante,
                'correo' => $usuario->postulante->correo,
                'ci' => $usuario->postulante->ci,
                'nombre' => $usuario->postulante->nombre,
                'telefono' => $usuario->postulante->telefono,
                'ciudad' => $usuario->postulante->ciudad,
                'colegio_procedencia' => $usuario->postulante->colegio_procedencia,
                'direccion' => $usuario->postulante->direccion,
                'fecha_nacimiento' => $usuario->postulante->fecha_nacimiento,
                'genero' => $usuario->postulante->genero,
                'cod_titulo_bachiller' => $usuario->postulante->cod_titulo_bachiller,
            ] : null,
            'docente' => $usuario->docente ? [
                'username' => $usuario->docente->username_docente,
                'nombre' => $usuario->docente->nombre,
                'especializacion' => $usuario->docente->especializacion,
                'maestria' => $usuario->docente->maestria,
            ] : null,
            'administrativo' => $usuario->administrativo ? [
                'username' => $usuario->administrativo->username_administrativo,
                'nombre' => $usuario->administrativo->nombre,
                'telefono' => $usuario->administrativo->telefono,
                'ciudad' => $usuario->administrativo->ciudad,
            ] : null,
            default => null,
        };
    }
}
