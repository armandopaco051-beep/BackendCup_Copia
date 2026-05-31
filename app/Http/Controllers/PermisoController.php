<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermisoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'permisos' => Permiso::orderBy('codigo')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:500', Rule::unique('seguridad.permiso', 'nombre')],
        ]);

        $permiso = Permiso::create($validated);

        return response()->json([
            'message' => 'Permiso creado correctamente.',
            'permiso' => $permiso,
        ], 201);
    }

    public function show(Permiso $permiso): JsonResponse
    {
        return response()->json([
            'permiso' => $permiso,
        ]);
    }

    public function update(Request $request, Permiso $permiso): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:500',
                Rule::unique('seguridad.permiso', 'nombre')->ignore($permiso->codigo, 'codigo'),
            ],
        ]);

        $permiso->update($validated);

        return response()->json([
            'message' => 'Permiso actualizado correctamente.',
            'permiso' => $permiso,
        ]);
    }

    public function destroy(Permiso $permiso): JsonResponse
    {
        $permiso->roles()->detach();
        $permiso->delete();

        return response()->json([
            'message' => 'Permiso eliminado correctamente.',
        ]);
    }
}
