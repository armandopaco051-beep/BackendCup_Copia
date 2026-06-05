<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CarreraController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'carreras' => Carrera::orderBy('nombre')
                ->get()
                ->map(fn (Carrera $carrera): array => $this->formatCarrera($carrera))
                ->values(),
        ]);
    }

    public function habilitadas(): JsonResponse
    {
        $query = Carrera::orderBy('nombre');

        if ($this->hasEstadoColumn()) {
            $query->where('estado', 'habilitada');
        }

        return response()->json([
            'carreras' => $query->get()
                ->map(fn (Carrera $carrera): array => $this->formatCarrera($carrera))
                ->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $carrera = Carrera::create($validated);

        return response()->json([
            'message' => 'Carrera registrada correctamente.',
            'carrera' => $this->formatCarrera($carrera),
        ], 201);
    }

    public function show(Carrera $carrera): JsonResponse
    {
        return response()->json([
            'carrera' => $this->formatCarrera($carrera),
        ]);
    }

    public function update(Request $request, Carrera $carrera): JsonResponse
    {
        $validated = $request->validate($this->rules($carrera->codigo));

        $carrera->update($validated);

        return response()->json([
            'message' => 'Carrera actualizada correctamente.',
            'carrera' => $this->formatCarrera($carrera->fresh()),
        ]);
    }

    public function destroy(Carrera $carrera): JsonResponse
    {
        try {
            $carrera->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'No se puede eliminar la carrera porque ya esta relacionada con postulantes.',
            ], 422);
        }

        return response()->json([
            'message' => 'Carrera eliminada correctamente.',
        ]);
    }

    private function rules(?string $codigo = null): array
    {
        return [
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('pgsql.academico.carrera', 'codigo')->ignore($codigo, 'codigo'),
            ],
            'nombre' => ['required', 'string', 'max:500'],
            'estado' => ['nullable', Rule::in(['habilitada', 'inactiva'])],
        ];
    }

    private function formatCarrera(Carrera $carrera): array
    {
        return [
            'codigo' => $carrera->codigo,
            'nombre' => $carrera->nombre,
            'estado' => $carrera->estado ?? 'habilitada',
        ];
    }

    private function hasEstadoColumn(): bool
    {
        return DB::table('information_schema.columns')
            ->where('table_schema', 'academico')
            ->where('table_name', 'carrera')
            ->where('column_name', 'estado')
            ->exists();
    }
}
