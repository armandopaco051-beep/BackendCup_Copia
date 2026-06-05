<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;

class MateriaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'materias' => Materia::orderBy('nombre')
                ->get()
                ->map(fn (Materia $materia): array => $this->formatMateria($materia))
                ->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $materia = Materia::create($validated);

        return response()->json([
            'message' => 'Materia registrada correctamente.',
            'materia' => $this->formatMateria($materia),
        ], 201);
    }

    public function show(Materia $materia): JsonResponse
    {
        return response()->json([
            'materia' => $this->formatMateria($materia),
        ]);
    }

    public function update(Request $request, Materia $materia): JsonResponse
    {
        $validated = $request->validate($this->rules($materia->id));

        $materia->update($validated);

        return response()->json([
            'message' => 'Materia actualizada correctamente.',
            'materia' => $this->formatMateria($materia->fresh()),
        ]);
    }

    public function destroy(Materia $materia): JsonResponse
    {
        try {
            $materia->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'No se puede eliminar la materia porque ya esta relacionada con grupos o calificaciones.',
            ], 422);
        }

        return response()->json([
            'message' => 'Materia eliminada correctamente.',
        ]);
    }

    private function rules(?string $id = null): array
    {
        return [
            'id' => [
                'required',
                'string',
                'max:100',
                Rule::unique('pgsql.academico.materia', 'id')->ignore($id, 'id'),
            ],
            'nombre' => ['required', 'string', 'max:500'],
            'estado' => ['nullable', Rule::in(['habilitada', 'inactiva'])],
        ];
    }

    private function formatMateria(Materia $materia): array
    {
        return [
            'id' => $materia->id,
            'nombre' => $materia->nombre,
            'estado' => $materia->estado ?? 'habilitada',
        ];
    }
}
