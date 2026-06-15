<?php

namespace App\Http\Controllers;

use App\Models\Administrativo;
use App\Models\Bitacora;
use App\Models\Docente;
use App\Models\Postulante;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UsuarioController extends Controller
{
    public function index(): JsonResponse
    {
        $usuarios = Usuario::with('rol.permisos', 'postulante', 'docente', 'administrativo')
            ->where(function ($query): void {
                $query->where('tipo', '!=', 'postulante')
                    ->orWhereHas('postulante', fn ($postulante) => $postulante->where('estado', '!=', 'pendiente_pago'));
            })
            ->orderBy('username')
            ->get()
            ->map(fn (Usuario $usuario): array => $this->formatUsuario($usuario));

        return response()->json([
            'usuarios' => $usuarios,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:500',
                Rule::unique('pgsql.seguridad.usuario', 'username'),
            ],
            'password' => ['required', 'string', 'min:6'],
            'codigo_rol' => ['nullable', 'integer', Rule::exists('pgsql.seguridad.rol', 'id')],
            'tipo' => ['required', 'string', Rule::in(['administrativo', 'docente', 'postulante'])],
            'perfil' => ['required', 'array'],
        ]);

        $perfil = $this->validatePerfil($request, $validated['tipo'], true);

        $usuario = DB::transaction(function () use ($validated, $perfil): Usuario {
            $usuario = Usuario::create([
                'username' => $validated['username'],
                'password' => $validated['password'],
                'codigo_rol' => $validated['codigo_rol'] ?? $this->rolIdPorTipo($validated['tipo']),
                'tipo' => $validated['tipo'],
            ]);

            $this->crearPerfil($usuario, $perfil);

            return $usuario->load('rol.permisos', 'postulante', 'docente', 'administrativo');
        });

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'usuario' => $this->formatUsuario($usuario),
        ], 201);
    }
 /* esta funcion hace la consulta de un usuario especifico */
    public function show(Usuario $usuario): JsonResponse
    {
        $usuario->load('rol.permisos', 'postulante', 'docente', 'administrativo');

        return response()->json([
            'usuario' => $this->formatUsuario($usuario),
        ]);
    }

    /* esta funcion hace la actualizacion de un usuario especifico */
    public function update(Request $request, Usuario $usuario): JsonResponse
    {
        $validated = $request->validate([
            'codigo_rol' => ['sometimes', 'nullable', 'integer', Rule::exists('pgsql.seguridad.rol', 'id')],
            'tipo' => ['sometimes', 'string', Rule::in(['administrativo', 'docente', 'postulante'])],
            'perfil' => ['sometimes', 'array'],
        ]);

        $nuevoTipo = $validated['tipo'] ?? $usuario->tipo;

        if ($nuevoTipo !== $usuario->tipo && ! $request->has('perfil')) {
            throw ValidationException::withMessages([
                'perfil' => ['El perfil es obligatorio cuando se cambia el tipo de usuario.'],
            ]);
        }

        $perfil = $request->has('perfil')
            ? $this->validatePerfil($request, $nuevoTipo, false)
            : null;

        $usuario = DB::transaction(function () use ($usuario, $validated, $nuevoTipo, $perfil): Usuario {
            $tipoAnterior = $usuario->tipo;

            $usuario->fill([
                'codigo_rol' => array_key_exists('codigo_rol', $validated)
                    ? $validated['codigo_rol']
                    : $usuario->codigo_rol,
                'tipo' => $nuevoTipo,
            ]);
            $usuario->save();

            if ($perfil !== null) {
                if ($nuevoTipo !== $tipoAnterior) {
                    $this->eliminarPerfil($usuario, $tipoAnterior);
                    $this->crearPerfil($usuario, $perfil);
                } else {
                    $this->actualizarPerfil($usuario, $perfil);
                }
            }

            return $usuario->load('rol.permisos', 'postulante', 'docente', 'administrativo');
        });

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'usuario' => $this->formatUsuario($usuario),
        ]);
    }
    
    /* esta funcion hace la eliminacion de un usuario especifico */
    public function destroy(Usuario $usuario): JsonResponse
    {
        DB::transaction(function () use ($usuario): void {
            $this->eliminarPerfil($usuario, $usuario->tipo);
            $usuario->delete();
        });

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }

    public function asignarRol(Request $request, Usuario $usuario): JsonResponse
    {
        $validated = $request->validate([
            'codigo_rol' => ['required', 'integer', Rule::exists('pgsql.seguridad.rol', 'id')],
        ]);

        $usuario->update([
            'codigo_rol' => $validated['codigo_rol'],
        ]);

        $usuario->load('rol.permisos', 'postulante', 'docente', 'administrativo');

        return response()->json([
            'message' => 'Rol asignado correctamente.',
            'usuario' => $this->formatUsuario($usuario),
        ]);
    }

    public function restablecerPassword(Request $request, Usuario $usuario): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $usuario->update([
            'password' => $validated['password'],
        ]);

        return response()->json([
            'message' => 'Contrasena restablecida correctamente.',
        ]);
    }

    private function validatePerfil(Request $request, string $tipo, bool $required): array
    {
        return match ($tipo) {
            'administrativo' => $request->validate([
                'perfil.nombre' => [$required ? 'required' : 'sometimes', 'string', 'max:500'],
                'perfil.correo' => [$required ? 'required' : 'sometimes', 'email', 'max:100'],
                'perfil.telefono' => [$required ? 'required' : 'sometimes', 'string', 'max:10'],
                'perfil.ciudad' => [$required ? 'required' : 'sometimes', 'string'],
            ])['perfil'],
            'docente' => $request->validate([
                'perfil.nombre' => [$required ? 'required' : 'sometimes', 'string', 'max:500'],
                'perfil.correo' => [$required ? 'required' : 'sometimes', 'email', 'max:100'],
                'perfil.telefono' => [$required ? 'required' : 'sometimes', 'string', 'max:10'],
                'perfil.ciudad' => [$required ? 'required' : 'sometimes', 'string'],
                'perfil.titulo_profesional' => [$required ? 'required' : 'sometimes', 'string', 'max:500'],
                'perfil.nro_registro_profesional' => ['nullable', 'string', 'max:100'],
                'perfil.estado_profesional' => ['nullable', Rule::in(['pendiente_revision', 'habilitado', 'observado', 'rechazado'])],
                'perfil.observacion_profesional' => ['nullable', 'string'],
                'perfil.max_grupos_periodo' => ['nullable', 'integer', 'min:1', 'max:20'],
                'perfil.max_horas_semana' => ['nullable', 'numeric', 'min:1', 'max:60'],
                'perfil.especializacion' => ['nullable', 'string'],
                'perfil.maestria' => ['nullable', 'string'],
            ])['perfil'],
            'postulante' => $request->validate([
                'perfil.correo' => [$required ? 'required' : 'sometimes', 'email', 'max:100'],
                'perfil.ci' => [$required ? 'required' : 'sometimes', 'string', 'max:100'],
                'perfil.nombre' => [$required ? 'required' : 'sometimes', 'string', 'max:100'],
                'perfil.telefono' => [$required ? 'required' : 'sometimes', 'string', 'max:10'],
                'perfil.ciudad' => [$required ? 'required' : 'sometimes', 'string', 'max:100'],
                'perfil.colegio_procedencia' => [$required ? 'required' : 'sometimes', 'string'],
                'perfil.direccion' => [$required ? 'required' : 'sometimes', 'string'],
                'perfil.fecha_nacimiento' => [$required ? 'required' : 'sometimes', 'date'],
                'perfil.genero' => [$required ? 'required' : 'sometimes', 'string', 'max:100'],
                'perfil.cod_titulo_bachiller' => [$required ? 'required' : 'sometimes', 'string'],
            ])['perfil'],
        };
    }

    private function rolIdPorTipo(string $tipo): int
    {
        $nombreRol = match ($tipo) {
            'administrativo' => 'administrador',
            'docente' => 'docente',
            'postulante' => 'postulante',
        };

        $rol = Rol::where('nombre', $nombreRol)->first();

        if (! $rol) {
            throw ValidationException::withMessages([
                'tipo' => ["No existe el rol {$nombreRol} en seguridad.rol."],
            ]);
        }

        return $rol->id;
    }

    private function crearPerfil(Usuario $usuario, array $perfil): void
    {
        if ($usuario->tipo === 'docente') {
            $perfil = $this->normalizarPerfilDocente($perfil);
            $this->validarPerfilProfesionalDocente($perfil);
        }

        match ($usuario->tipo) {
            'administrativo' => Administrativo::create([
                'username_administrativo' => $usuario->username,
                ...$perfil,
            ]),
            'docente' => Docente::create([
                'username_docente' => $usuario->username,
                ...$perfil,
            ]),
            'postulante' => Postulante::create([
                'username_postulante' => $usuario->username,
                ...$perfil,
            ]),
        };
    }

    private function actualizarPerfil(Usuario $usuario, array $perfil): void
    {
        if ($usuario->tipo === 'docente') {
            $perfilAnterior = $usuario->docente;
            $perfil = $this->normalizarPerfilDocente([
                ...($perfilAnterior?->only([
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
                ]) ?? []),
                ...$perfil,
            ]);
            $this->validarPerfilProfesionalDocente($perfil);
        }

        match ($usuario->tipo) {
            'administrativo' => $usuario->administrativo()->updateOrCreate(
                ['username_administrativo' => $usuario->username],
                $perfil,
            ),
            'docente' => $usuario->docente()->updateOrCreate(
                ['username_docente' => $usuario->username],
                $perfil,
            ),
            'postulante' => $usuario->postulante()->updateOrCreate(
                ['username_postulante' => $usuario->username],
                $perfil,
            ),
        };

        if ($usuario->tipo === 'docente') {
            $this->registrarCambioEstadoProfesional($usuario, $perfilAnterior ?? null, $perfil);
        }
    }

    private function eliminarPerfil(Usuario $usuario, string $tipo): void
    {
        match ($tipo) {
            'administrativo' => Administrativo::where('username_administrativo', $usuario->username)->delete(),
            'docente' => Docente::where('username_docente', $usuario->username)->delete(),
            'postulante' => Postulante::where('username_postulante', $usuario->username)->delete(),
            default => null,
        };
    }

    private function formatUsuario(Usuario $usuario): array
    {
        return [
            'username' => $usuario->username,
            'tipo' => $usuario->tipo,
            'rol' => $usuario->rol ? [
                'id' => $usuario->rol->id,
                'nombre' => $usuario->rol->nombre,
            ] : null,
            'perfil' => match ($usuario->tipo) {
                'administrativo' => $usuario->administrativo,
                'docente' => $usuario->docente,
                'postulante' => $usuario->postulante,
                default => null,
            },
            'permisos' => $usuario->rol
                ? $usuario->rol->permisos->pluck('nombre')->values()
                : [],
        ];
    }

    private function normalizarPerfilDocente(array $perfil): array
    {
        $perfil['estado_profesional'] = $perfil['estado_profesional'] ?? 'pendiente_revision';
        $perfil['max_grupos_periodo'] = (int) ($perfil['max_grupos_periodo'] ?? 3);
        $perfil['max_horas_semana'] = (float) ($perfil['max_horas_semana'] ?? 30);

        return $perfil;
    }

    private function validarPerfilProfesionalDocente(array $perfil): void
    {
        if (($perfil['estado_profesional'] ?? null) === 'habilitado' && blank($perfil['titulo_profesional'] ?? null)) {
            throw ValidationException::withMessages([
                'perfil.titulo_profesional' => ['El titulo profesional es obligatorio para habilitar al docente.'],
            ]);
        }
    }

    private function registrarCambioEstadoProfesional(Usuario $usuario, ?Docente $perfilAnterior, array $perfil): void
    {
        $estadoAnterior = $perfilAnterior?->estado_profesional;
        $estadoNuevo = $perfil['estado_profesional'] ?? null;

        if (! $estadoNuevo || $estadoNuevo === $estadoAnterior) {
            return;
        }

        Bitacora::registrar(
            'validar_perfil_profesional_docente',
            'academico',
            "Cambio de estado profesional del docente {$usuario->username}: {$estadoAnterior} -> {$estadoNuevo}.",
            [
                'username_docente' => $usuario->username,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $estadoNuevo,
                'titulo_profesional' => $perfil['titulo_profesional'] ?? null,
            ],
        );
    }
}
