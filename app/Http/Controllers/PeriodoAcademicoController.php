<?php

namespace App\Http\Controllers;

use App\Models\PeriodoAcademico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PeriodoAcademicoController extends Controller
{
    private array $extendedColumns = [
        'nombre',
        'fecha_inicio_preinscripcion',
        'fecha_fin_preinscripcion',
        'fecha_inicio_requisitos',
        'fecha_fin_requisitos',
        'fecha_inicio_pago',
        'fecha_fin_pago',
        'estado',
    ];

    public function index(): JsonResponse
    {
        $columns = $this->columns();

        return response()->json([
            'periodos' => PeriodoAcademico::orderByDesc('id')
                ->get()
                ->map(fn (PeriodoAcademico $periodo): array => $this->formatPeriodo($periodo, $columns))
                ->values(),
            'configuracion' => $this->configuracion($columns),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $columns = $this->columns();
        $validated = $this->validatePeriodo($request, $columns);

        $periodo = PeriodoAcademico::create($this->payload($validated, $columns));

        return response()->json([
            'message' => 'Periodo academico creado correctamente.',
            'periodo' => $this->formatPeriodo($periodo, $columns),
            'configuracion' => $this->configuracion($columns),
        ], 201);
    }

    public function show(PeriodoAcademico $periodoAcademico): JsonResponse
    {
        $columns = $this->columns();

        return response()->json([
            'periodo' => $this->formatPeriodo($periodoAcademico, $columns),
            'configuracion' => $this->configuracion($columns),
        ]);
    }

    public function update(Request $request, PeriodoAcademico $periodoAcademico): JsonResponse
    {
        $columns = $this->columns();
        $validated = $this->validatePeriodo($request, $columns, true);

        $periodoAcademico->update($this->payload($validated, $columns));

        return response()->json([
            'message' => 'Periodo academico actualizado correctamente.',
            'periodo' => $this->formatPeriodo($periodoAcademico->fresh(), $columns),
            'configuracion' => $this->configuracion($columns),
        ]);
    }

    public function destroy(PeriodoAcademico $periodoAcademico): JsonResponse
    {
        $periodoAcademico->delete();

        return response()->json([
            'message' => 'Periodo academico eliminado correctamente.',
        ]);
    }

    private function validatePeriodo(Request $request, array $columns, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        $rules = [
            'semestre' => [$required, 'integer', Rule::in([1, 2])],
            'anio' => [$required, 'integer', 'min:2000', 'max:2100'],
        ];

        if (in_array('nombre', $columns, true)) {
            $rules['nombre'] = ['nullable', 'string', 'max:100'];
        }

        foreach ($this->extendedColumns as $column) {
            if (str_starts_with($column, 'fecha_') && in_array($column, $columns, true)) {
                $rules[$column] = ['nullable', 'date'];
            }
        }

        if (in_array('estado', $columns, true)) {
            $rules['estado'] = ['nullable', Rule::in(['pendiente', 'activo', 'cerrado'])];
        }

        return $request->validate($rules);
    }

    private function payload(array $validated, array $columns): array
    {
        $payload = [];

        if (array_key_exists('semestre', $validated)) {
            $payload['semestre'] = $validated['semestre'];
        }

        if (array_key_exists('anio', $validated)) {
            $payload['año'] = $validated['anio'];
        }

        foreach ($this->extendedColumns as $column) {
            if (array_key_exists($column, $validated) && in_array($column, $columns, true)) {
                $payload[$column] = $validated[$column];
            }
        }

        return $payload;
    }

    private function columns(): array
    {
        return DB::table('information_schema.columns')
            ->where('table_schema', 'academico')
            ->where('table_name', 'periodo_academico')
            ->pluck('column_name')
            ->all();
    }

    private function configuracion(array $columns): array
    {
        return [
            'columnas_disponibles' => $columns,
            'soporta_fechas' => count(array_intersect($this->extendedColumns, $columns)) > 0,
            'columnas_faltantes' => array_values(array_diff($this->extendedColumns, $columns)),
        ];
    }

    private function formatPeriodo(PeriodoAcademico $periodo, array $columns): array
    {
        $data = [
            'id' => $periodo->id,
            'semestre' => $periodo->semestre,
            'anio' => $periodo->{'año'},
            'nombre' => in_array('nombre', $columns, true)
                ? $periodo->nombre
                : 'Periodo CUP '.$periodo->{'año'}.'-'.$periodo->semestre,
        ];

        foreach ($this->extendedColumns as $column) {
            if (in_array($column, $columns, true)) {
                $data[$column] = $periodo->{$column};
            }
        }

        return $data;
    }
}
