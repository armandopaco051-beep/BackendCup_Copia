<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AulaController extends Controller
{
    private const CAPACIDAD_FIJA = 70;

    public function index(): JsonResponse
    {
        $columns = $this->columns();

        return response()->json([
            'aulas' => Aula::orderBy('nro_aula')
                ->get()
                ->map(fn (Aula $aula): array => $this->formatAula($aula, $columns))
                ->values(),
            'configuracion' => [
                'columnas_disponibles' => $columns,
                'soporta_capacidad_estado' => in_array('capacidad', $columns, true)
                    && in_array('estado', $columns, true),
            ],
        ]);
    }

    public function cupos(): JsonResponse
    {
        $columns = $this->columns();
        $ocupaciones = $this->ocupacionesPorAula();

        $aulas = Aula::orderBy('nro_aula')
            ->get()
            ->map(function (Aula $aula) use ($columns, $ocupaciones): array {
                $capacidad = self::CAPACIDAD_FIJA;
                $ocupacion = (int) ($ocupaciones[$aula->nro_aula] ?? 0);
                $porcentaje = $capacidad > 0 ? (int) round(($ocupacion / $capacidad) * 100) : 0;

                return [
                    ...$this->formatAula($aula, $columns),
                    'capacidad' => $capacidad,
                    'ocupacion' => $ocupacion,
                    'cupos_disponibles' => max($capacidad - $ocupacion, 0),
                    'porcentaje_uso' => min($porcentaje, 100),
                    'estado_cupo' => $this->estadoCupo($ocupacion, $capacidad),
                ];
            })
            ->values();

        return response()->json([
            'caso_uso' => 'Validar cupos por aula',
            'descripcion' => 'Valida la capacidad y ocupacion de cada aula segun horarios registrados.',
            'aulas' => $aulas,
            'resumen' => [
                'total_aulas' => $aulas->count(),
                'disponibles' => $aulas->where('estado_cupo', 'disponible')->count(),
                'casi_llenas' => $aulas->where('estado_cupo', 'casi_lleno')->count(),
                'sin_cupo' => $aulas->where('estado_cupo', 'sin_cupo')->count(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $columns = $this->columns();
        $validated = $request->validate($this->rules($columns));
        $validated['capacidad'] = self::CAPACIDAD_FIJA;

        $aula = Aula::create($this->payload($validated, $columns));

        return response()->json([
            'message' => 'Aula creada correctamente.',
            'aula' => $this->formatAula($aula, $columns),
        ], 201);
    }

    public function show(Aula $aula): JsonResponse
    {
        $columns = $this->columns();

        return response()->json([
            'aula' => $this->formatAula($aula, $columns),
        ]);
    }

    public function update(Request $request, Aula $aula): JsonResponse
    {
        $columns = $this->columns();
        $validated = $request->validate($this->rules($columns, true));
        $validated['capacidad'] = self::CAPACIDAD_FIJA;

        $aula->update($this->payload($validated, $columns));

        return response()->json([
            'message' => 'Aula actualizada correctamente.',
            'aula' => $this->formatAula($aula->fresh(), $columns),
        ]);
    }

    public function destroy(Aula $aula): JsonResponse
    {
        $aula->delete();

        return response()->json([
            'message' => 'Aula eliminada correctamente.',
        ]);
    }

    private function rules(array $columns, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        $rules = [
            'nro_aula' => [$required, 'integer', 'min:1'],
            'tipo' => [$required, 'string'],
            'piso' => [$required, 'string', 'max:100'],
        ];

        if (in_array('estado', $columns, true)) {
            $rules['estado'] = ['nullable', Rule::in(['disponible', 'mantenimiento', 'inactiva'])];
        }

        return $rules;
    }

    private function payload(array $validated, array $columns): array
    {
        $payload = collect($validated)
            ->only(['nro_aula', 'tipo', 'piso'])
            ->all();

        foreach (['capacidad', 'estado'] as $column) {
            if (array_key_exists($column, $validated) && in_array($column, $columns, true)) {
                $payload[$column] = $validated[$column];
            }
        }

        return $payload;
    }

    private function formatAula(Aula $aula, array $columns): array
    {
        return [
            'nro_aula' => $aula->nro_aula,
            'tipo' => $aula->tipo,
            'piso' => $aula->piso,
            'capacidad' => in_array('capacidad', $columns, true) ? self::CAPACIDAD_FIJA : null,
            'estado' => in_array('estado', $columns, true) ? $aula->estado : null,
        ];
    }

    private function columns(): array
    {
        return DB::table('information_schema.columns')
            ->where('table_schema', 'academico')
            ->where('table_name', 'aula')
            ->pluck('column_name')
            ->all();
    }

    private function ocupacionesPorAula(): array
    {
        if (! $this->tableExists('horario')) {
            return [];
        }

        return DB::table('academico.horario')
            ->select('id_aula', DB::raw('COUNT(DISTINCT username_postulante) as ocupacion'))
            ->groupBy('id_aula')
            ->pluck('ocupacion', 'id_aula')
            ->map(fn ($ocupacion): int => (int) $ocupacion)
            ->all();
    }

    private function estadoCupo(int $ocupacion, int $capacidad): string
    {
        if ($capacidad <= 0 || $ocupacion >= $capacidad) {
            return 'sin_cupo';
        }

        if (($ocupacion / $capacidad) >= 0.8) {
            return 'casi_lleno';
        }

        return 'disponible';
    }

    private function tableExists(string $table): bool
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', 'academico')
            ->where('table_name', $table)
            ->exists();
    }
}
