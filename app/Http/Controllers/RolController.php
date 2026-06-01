<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'roles' => Rol::with('permisos')
                ->withCount(['usuarios'])
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:500', Rule::unique('pgsql.seguridad.rol', 'nombre')],
        ]);

        $rol = Rol::create($validated)->load('permisos');

        return response()->json([
            'message' => 'Rol creado correctamente.',
            'rol' => $rol,
        ], 201);
    }

    public function show(Rol $rol): JsonResponse
    {
        return response()->json([
            'rol' => $rol->load('permisos'),
        ]);
    }

    public function update(Request $request, Rol $rol): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:500',
                Rule::unique('pgsql.seguridad.rol', 'nombre')->ignore($rol->id, 'id'),
            ],
        ]);

        $rol->update($validated);

        return response()->json([
            'message' => 'Rol actualizado correctamente.',
            'rol' => $rol->load('permisos'),
        ]);
    }

    public function destroy(Rol $rol): JsonResponse
    {
        $rol->permisos()->detach();
        Usuario::where('codigo_rol', $rol->id)->update(['codigo_rol' => null]);
        $rol->delete();

        return response()->json([
            'message' => 'Rol eliminado correctamente.',
        ]);
    }

    public function sincronizarPermisos(Request $request, Rol $rol): JsonResponse
    {
        $validated = $request->validate([
            'permisos' => ['required', 'array'],
            'permisos.*' => ['integer', Rule::exists('pgsql.seguridad.permiso', 'codigo')],
        ]);

        $rol->permisos()->sync($validated['permisos']);

        return response()->json([
            'message' => 'Permisos asignados correctamente.',
            'rol' => $rol->load('permisos'),
        ]);
    }
}
