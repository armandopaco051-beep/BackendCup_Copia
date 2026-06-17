<?php

namespace App\Http\Controllers;

use App\Models\PeriodoAcademico;
use Illuminate\Database\QueryException;
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

        $payload = $this->payload($validated, $columns);
        $periodo = DB::transaction(function () use ($payload): PeriodoAcademico {
            $this->cerrarOtrosPeriodosActivos($payload);

            return PeriodoAcademico::create($payload);
        });

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

        $payload = $this->payload($validated, $columns);
        DB::transaction(function () use ($periodoAcademico, $payload): void {
            $this->cerrarOtrosPeriodosActivos($payload, $periodoAcademico->id);
            $periodoAcademico->update($payload);
        });

        return response()->json([
            'message' => 'Periodo academico actualizado correctamente.',
            'periodo' => $this->formatPeriodo($periodoAcademico->fresh(), $columns),
            'configuracion' => $this->configuracion($columns),
        ]);
    }

    public function destroy(PeriodoAcademico $periodoAcademico): JsonResponse
    {
        try {
            $periodoAcademico->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'No se puede eliminar este periodo porque ya esta relacionado con otros registros.',
            ], 409);
        }

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
        $yearColumn = $this->yearColumn($columns);

        if (array_key_exists('semestre', $validated)) {
            $payload['semestre'] = $validated['semestre'];
        }

        if (array_key_exists('anio', $validated)) {
            $payload[$yearColumn] = $validated['anio'];
        }

        foreach ($this->extendedColumns as $column) {
            if (array_key_exists($column, $validated) && in_array($column, $columns, true)) {
                $payload[$column] = $validated[$column];
            }
        }

        $payload = $this->mirrorProcessWindow($payload, $columns);

        return $payload;
    }

    private function mirrorProcessWindow(array $payload, array $columns): array
    {
        if (array_key_exists('fecha_inicio_preinscripcion', $payload)) {
            foreach (['fecha_inicio_requisitos', 'fecha_inicio_pago'] as $column) {
                if (in_array($column, $columns, true)) {
                    $payload[$column] = $payload['fecha_inicio_preinscripcion'];
                }
            }
        }

        if (array_key_exists('fecha_fin_preinscripcion', $payload)) {
            foreach (['fecha_fin_requisitos', 'fecha_fin_pago'] as $column) {
                if (in_array($column, $columns, true)) {
                    $payload[$column] = $payload['fecha_fin_preinscripcion'];
                }
            }
        }

        return $payload;
    }

    private function cerrarOtrosPeriodosActivos(array $payload, ?int $exceptoId = null): void
    {
        if (($payload['estado'] ?? null) !== 'activo') {
            return;
        }

        PeriodoAcademico::where('estado', 'activo')
            ->when($exceptoId, fn ($query) => $query->where('id', '!=', $exceptoId))
            ->update(['estado' => 'cerrado']);
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
            'columna_anio' => $this->yearColumn($columns),
            'soporta_fechas' => count(array_intersect($this->extendedColumns, $columns)) > 0,
            'columnas_faltantes' => array_values(array_diff($this->extendedColumns, $columns)),
        ];
    }

    private function formatPeriodo(PeriodoAcademico $periodo, array $columns): array
    {
        $yearColumn = $this->yearColumn($columns);
        $year = $periodo->{$yearColumn};

        $data = [
            'id' => $periodo->id,
            'semestre' => $periodo->semestre,
            'anio' => $year,
            'nombre' => in_array('nombre', $columns, true)
                ? $periodo->nombre
                : 'Periodo CUP '.$year.'-'.$periodo->semestre,
        ];

        foreach ($this->extendedColumns as $column) {
            if (in_array($column, $columns, true)) {
                $data[$column] = $periodo->{$column};
            }
        }

        return $data;
    }

    private function yearColumn(array $columns): string
    {
        $spanishColumn = "a\u{00F1}o";

        if (in_array($spanishColumn, $columns, true)) {
            return $spanishColumn;
        }

        if (in_array('anio', $columns, true)) {
            return 'anio';
        }

        return $spanishColumn;
    }
}
