<?php

namespace App\Http\Controllers;

use App\Models\Postulante;
use App\Models\PostulanteCarrera;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * CU-06: Registrar preinscripcion.
 *
 * Captura los datos personales del bachiller, crea su usuario como postulante
 * y registra la carrera elegida cuando se envia en la solicitud.
 */
class PreinscripcionController extends Controller
{
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
            'correo' => ['required', 'email', 'max:100'],
            'ci' => ['required', 'string', 'max:100'],
            'nombre' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'max:10'],
            'ciudad' => ['required', 'string', 'max:100'],
            'colegio_procedencia' => ['required', 'string'],
            'direccion' => ['required', 'string'],
            'fecha_nacimiento' => ['required', 'date'],
            'genero' => ['required', 'string', 'max:100'],
            'cod_titulo_bachiller' => ['required', 'string'],
            'id_carrera' => ['nullable', 'string', 'max:50', Rule::exists('pgsql.academico.carrera', 'codigo')],
            'descripcion' => ['required_with:id_carrera', 'nullable', 'string'],
        ]);

        $postulante = DB::transaction(function () use ($validated): Postulante {
            $usuario = Usuario::create([
                'username' => $validated['username'],
                'password' => $validated['password'],
                'codigo_rol' => $this->rolPostulanteId(),
                'tipo' => 'postulante',
            ]);

            $postulante = Postulante::create([
                'username_postulante' => $usuario->username,
                'correo' => $validated['correo'],
                'ci' => $validated['ci'],
                'nombre' => $validated['nombre'],
                'telefono' => $validated['telefono'],
                'ciudad' => $validated['ciudad'],
                'colegio_procedencia' => $validated['colegio_procedencia'],
                'direccion' => $validated['direccion'],
                'fecha_nacimiento' => $validated['fecha_nacimiento'],
                'genero' => $validated['genero'],
                'cod_titulo_bachiller' => $validated['cod_titulo_bachiller'],
            ]);

            if (! empty($validated['id_carrera'])) {
                PostulanteCarrera::create([
                    'id_carrera' => $validated['id_carrera'],
                    'username_postulante' => $usuario->username,
                    'descripcion' => $validated['descripcion'],
                ]);
            }

            return $postulante;
        });

        return response()->json([
            'caso_uso' => 'CU-06 Registrar preinscripcion',
            'message' => 'Preinscripcion registrada correctamente.',
            'preinscripcion' => [
                'username' => $postulante->username_postulante,
                'tipo' => 'postulante',
                'correo' => $postulante->correo,
                'ci' => $postulante->ci,
                'nombre' => $postulante->nombre,
                'telefono' => $postulante->telefono,
                'ciudad' => $postulante->ciudad,
                'colegio_procedencia' => $postulante->colegio_procedencia,
                'direccion' => $postulante->direccion,
                'fecha_nacimiento' => $postulante->fecha_nacimiento,
                'genero' => $postulante->genero,
                'cod_titulo_bachiller' => $postulante->cod_titulo_bachiller,
                'id_carrera' => $validated['id_carrera'] ?? null,
                'descripcion' => $validated['descripcion'] ?? null,
            ],
        ], 201);
    }

    private function rolPostulanteId(): int
    {
        $rol = Rol::where('nombre', 'postulante')->first();

        if (! $rol) {
            throw ValidationException::withMessages([
                'tipo' => ['No existe el rol postulante en seguridad.rol.'],
            ]);
        }

        return $rol->id;
    }
}
