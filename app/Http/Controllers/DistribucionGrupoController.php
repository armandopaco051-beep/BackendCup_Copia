<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\Postulante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DistribucionGrupoController extends Controller
{
    private const CUPO_MAXIMO = 70;

    private array $turnos = ['mañana', 'tarde', 'noche'];

    private array $grupoExtraColumns = [
        'cupo_maximo',
        'turno',
        'id_periodo_academico',
        'estado',
        'created_at',
    ];

    public function calcular(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'periodo_id' => ['nullable', 'integer', Rule::exists('pgsql.academico.periodo_academico', 'id')],
            'cupo_maximo' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $periodo = $this->periodo($validated['periodo_id'] ?? null);
        $cupoMaximo = $validated['cupo_maximo'] ?? self::CUPO_MAXIMO;
        $totalPostulantes = $this->totalPostulantes();
        $cantidadGrupos = $this->cantidadGrupos($totalPostulantes, $cupoMaximo);

        return response()->json([
            'caso_uso' => 'CU-13 Calcular distribucion de grupos',
            'periodo' => $this->formatPeriodo($periodo),
            'periodo_cerrado' => $this->periodoCerrado($periodo),
            'total_postulantes' => $totalPostulantes,
            'cupo_maximo' => $cupoMaximo,
            'cantidad_grupos' => $cantidadGrupos,
            'turnos' => $this->turnos,
            'grupos_calculados' => $this->gruposCalculados($cantidadGrupos, $cupoMaximo, $periodo),
        ]);
    }

    public function generar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'periodo_id' => ['nullable', 'integer', Rule::exists('pgsql.academico.periodo_academico', 'id')],
            'cupo_maximo' => ['nullable', 'integer', 'min:1', 'max:200'],
            'forzar' => ['nullable', 'boolean'],
        ]);

        $periodo = $this->periodo($validated['periodo_id'] ?? null);
        $cupoMaximo = $validated['cupo_maximo'] ?? self::CUPO_MAXIMO;
        $totalPostulantes = $this->totalPostulantes();
        $cantidadGrupos = $this->cantidadGrupos($totalPostulantes, $cupoMaximo);
        $gruposCalculados = $this->gruposCalculados($cantidadGrupos, $cupoMaximo, $periodo);

        if (! ($validated['forzar'] ?? false) && $this->periodoTieneEstado($periodo) && ! $this->periodoCerrado($periodo)) {
            return response()->json([
                'caso_uso' => 'CU-13 Calcular distribucion de grupos',
                'message' => 'El periodo aun no esta cerrado. Si deseas generar de todos modos envia forzar=true.',
                'periodo' => $this->formatPeriodo($periodo),
            ], 422);
        }

        $columns = $this->grupoColumns();
        $grupos = DB::transaction(function () use ($gruposCalculados, $columns): array {
            return collect($gruposCalculados)
                ->map(fn (array $grupo): Grupo => Grupo::updateOrCreate(
                    ['codigo' => $grupo['codigo']],
                    $this->grupoPayload($grupo, $columns),
                ))
                ->values()
                ->all();
        });

        return response()->json([
            'caso_uso' => 'CU-13 Calcular distribucion de grupos',
            'message' => 'Grupos generados correctamente.',
            'total_postulantes' => $totalPostulantes,
            'cupo_maximo' => $cupoMaximo,
            'cantidad_grupos' => $cantidadGrupos,
            'grupos' => $grupos,
        ], 201);
    }

    private function totalPostulantes(): int
    {
        return Postulante::whereIn('estado', ['habilitado', 'admitido'])->count();
    }

    private function cantidadGrupos(int $totalPostulantes, int $cupoMaximo): int
    {
        if ($totalPostulantes === 0) {
            return 0;
        }

        return (int) ceil($totalPostulantes / $cupoMaximo);
    }

    private function gruposCalculados(int $cantidadGrupos, int $cupoMaximo, ?PeriodoAcademico $periodo): array
    {
        if ($cantidadGrupos === 0) {
            return [];
        }

        $periodLabel = $periodo
            ? 'Periodo '.$periodo->{'año'}.'-'.$periodo->semestre
            : 'Periodo sin definir';

        return collect(range(1, $cantidadGrupos))
            ->map(function (int $index) use ($cupoMaximo, $periodo, $periodLabel): array {
                $turno = $this->turnos[($index - 1) % count($this->turnos)];
                $codigo = 'Grupo-G'.str_pad((string) $index, 2, '0', STR_PAD_LEFT);

                return [
                    'codigo' => $codigo,
                    'descripcion' => "{$periodLabel} | Turno: {$turno} | Cupo maximo: {$cupoMaximo}",
                    'turno' => $turno,
                    'cupo_maximo' => $cupoMaximo,
                    'id_periodo_academico' => $periodo?->id,
                    'estado' => 'activo',
                    'created_at' => now(),
                ];
            })
            ->all();
    }

    private function grupoPayload(array $grupo, array $columns): array
    {
        $payload = [
            'descripcion' => $grupo['descripcion'],
        ];

        foreach ($this->grupoExtraColumns as $column) {
            if (in_array($column, $columns, true)) {
                $payload[$column] = $grupo[$column];
            }
        }

        return $payload;
    }

    private function periodo(?int $periodoId): ?PeriodoAcademico
    {
        if ($periodoId) {
            return PeriodoAcademico::find($periodoId);
        }

        return PeriodoAcademico::orderByDesc('id')->first();
    }

    private function periodoCerrado(?PeriodoAcademico $periodo): bool
    {
        if (! $periodo) {
            return false;
        }

        return array_key_exists('estado', $periodo->getAttributes()) && $periodo->estado === 'cerrado';
    }

    private function periodoTieneEstado(?PeriodoAcademico $periodo): bool
    {
        return (bool) $periodo && array_key_exists('estado', $periodo->getAttributes());
    }

    private function formatPeriodo(?PeriodoAcademico $periodo): ?array
    {
        if (! $periodo) {
            return null;
        }

        return [
            'id' => $periodo->id,
            'semestre' => $periodo->semestre,
            'anio' => $periodo->{'año'},
            'estado' => $periodo->estado ?? null,
        ];
    }

    private function grupoColumns(): array
    {
        return DB::table('information_schema.columns')
            ->where('table_schema', 'academico')
            ->where('table_name', 'grupo')
            ->pluck('column_name')
            ->all();
    }
}
