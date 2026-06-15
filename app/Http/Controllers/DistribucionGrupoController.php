<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\Postulante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'periodo_id' => ['required', 'integer', Rule::exists('pgsql.academico.periodo_academico', 'id')],
        ]);

        $periodo = $this->periodo($validated['periodo_id']);
        $grupos = $this->gruposPeriodo($periodo);

        return response()->json([
            'caso_uso' => 'CU-13 Consultar distribucion de grupos',
            'periodo' => $this->formatPeriodo($periodo),
            'total_postulantes' => $this->totalPostulantes(),
            'grupos' => $grupos->map(fn (Grupo $grupo): array => $this->formatGrupo($grupo))->values(),
            'resumen' => $this->resumenDistribucion($grupos),
        ]);
    }

    public function calcular(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'periodo_id' => ['nullable', 'integer', Rule::exists('pgsql.academico.periodo_academico', 'id')],
            'cupo_maximo' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $periodo = $this->periodo($validated['periodo_id'] ?? null);
        $cupoMaximo = $validated['cupo_maximo'] ?? self::CUPO_MAXIMO;
        $totalPostulantes = $this->totalPostulantes();
        $gruposExistentes = $this->gruposPeriodo($periodo);
        $capacidadExistente = $this->capacidadActiva($gruposExistentes);
        $postulantesSinCupo = max($totalPostulantes - $capacidadExistente, 0);
        $cantidadGruposNuevos = $this->cantidadGrupos($postulantesSinCupo, $cupoMaximo);
        $gruposCalculados = $this->gruposCalculados(
            $cantidadGruposNuevos,
            $cupoMaximo,
            $periodo,
            $this->siguienteIndiceGrupo(),
        );

        return response()->json([
            'caso_uso' => 'CU-13 Calcular distribucion de grupos',
            'periodo' => $this->formatPeriodo($periodo),
            'periodo_cerrado' => $this->periodoCerrado($periodo),
            'total_postulantes' => $totalPostulantes,
            'cupo_maximo' => $cupoMaximo,
            'cantidad_grupos' => $gruposExistentes->where('estado', 'activo')->count() + $cantidadGruposNuevos,
            'cantidad_grupos_existentes' => $gruposExistentes->count(),
            'cantidad_grupos_nuevos' => $cantidadGruposNuevos,
            'capacidad_existente' => $capacidadExistente,
            'postulantes_sin_cupo' => $postulantesSinCupo,
            'turnos' => $this->turnos,
            'grupos_existentes' => $gruposExistentes
                ->map(fn (Grupo $grupo): array => $this->formatGrupo($grupo))
                ->values(),
            'grupos_calculados' => $gruposCalculados,
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
        $gruposExistentes = $this->gruposPeriodo($periodo);
        $capacidadExistente = $this->capacidadActiva($gruposExistentes);
        $postulantesSinCupo = max($totalPostulantes - $capacidadExistente, 0);
        $cantidadGruposNuevos = $this->cantidadGrupos($postulantesSinCupo, $cupoMaximo);
        $gruposCalculados = $this->gruposCalculados(
            $cantidadGruposNuevos,
            $cupoMaximo,
            $periodo,
            $this->siguienteIndiceGrupo(),
        );

        if (! ($validated['forzar'] ?? false) && $this->periodoTieneEstado($periodo) && ! $this->periodoCerrado($periodo)) {
            return response()->json([
                'caso_uso' => 'CU-13 Calcular distribucion de grupos',
                'message' => 'El periodo aun no esta cerrado. Si deseas generar de todos modos envia forzar=true.',
                'periodo' => $this->formatPeriodo($periodo),
            ], 422);
        }

        $columns = $this->grupoColumns();
        DB::transaction(function () use ($gruposCalculados, $columns): void {
            collect($gruposCalculados)->each(fn (array $grupo) => Grupo::create([
                'codigo' => $grupo['codigo'],
                ...$this->grupoPayload($grupo, $columns),
            ]));
        });
        $grupos = $this->gruposPeriodo($periodo);

        return response()->json([
            'caso_uso' => 'CU-13 Calcular distribucion de grupos',
            'message' => $cantidadGruposNuevos > 0
                ? "{$cantidadGruposNuevos} grupo(s) nuevo(s) agregado(s) a la distribucion."
                : 'La distribucion existente ya tiene cupo suficiente. No fue necesario crear otro grupo.',
            'periodo' => $this->formatPeriodo($periodo),
            'periodo_cerrado' => $this->periodoCerrado($periodo),
            'total_postulantes' => $totalPostulantes,
            'cupo_maximo' => $cupoMaximo,
            'cantidad_grupos' => $grupos->where('estado', 'activo')->count(),
            'cantidad_grupos_nuevos' => $cantidadGruposNuevos,
            'grupos' => $grupos->map(fn (Grupo $grupo): array => $this->formatGrupo($grupo))->values(),
            'resumen' => $this->resumenDistribucion($grupos),
        ], $cantidadGruposNuevos > 0 ? 201 : 200);
    }

    public function update(Request $request, Grupo $grupo): JsonResponse
    {
        $validated = $request->validate([
            'cupo_maximo' => ['required', 'integer', 'min:1', 'max:200'],
            'turno' => ['required', Rule::in($this->turnos)],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ]);
        $ocupacion = $this->ocupacionGrupo($grupo->codigo);

        if ($validated['cupo_maximo'] < $ocupacion) {
            throw ValidationException::withMessages([
                'cupo_maximo' => ["El grupo tiene {$ocupacion} postulante(s) inscrito(s). El cupo no puede ser menor."],
            ]);
        }

        if ($validated['estado'] === 'inactivo' && $ocupacion > 0) {
            throw ValidationException::withMessages([
                'estado' => ["No puedes desactivar el grupo mientras tenga {$ocupacion} postulante(s) inscrito(s)."],
            ]);
        }

        $grupo->update($validated);

        return response()->json([
            'caso_uso' => 'CU-13 Editar distribucion de grupos',
            'message' => "El grupo {$grupo->codigo} fue actualizado correctamente.",
            'grupo' => $this->formatGrupo($grupo->fresh()),
        ]);
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

    private function gruposCalculados(
        int $cantidadGrupos,
        int $cupoMaximo,
        ?PeriodoAcademico $periodo,
        int $indiceInicial = 1,
    ): array
    {
        if ($cantidadGrupos === 0) {
            return [];
        }

        $periodLabel = $periodo
            ? 'Periodo '.$periodo->{'año'}.'-'.$periodo->semestre
            : 'Periodo sin definir';

        return collect(range($indiceInicial, $indiceInicial + $cantidadGrupos - 1))
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

    private function gruposPeriodo(?PeriodoAcademico $periodo)
    {
        return Grupo::query()
            ->when(
                $periodo,
                fn ($query) => $query->where('id_periodo_academico', $periodo->id),
                fn ($query) => $query->whereNull('id_periodo_academico'),
            )
            ->orderBy('codigo')
            ->get();
    }

    private function capacidadActiva($grupos): int
    {
        return (int) $grupos
            ->where('estado', 'activo')
            ->sum(fn (Grupo $grupo): int => (int) ($grupo->cupo_maximo ?? self::CUPO_MAXIMO));
    }

    private function resumenDistribucion($grupos): array
    {
        $activos = $grupos->where('estado', 'activo');
        $ocupacion = $grupos->sum(fn (Grupo $grupo): int => $this->ocupacionGrupo($grupo->codigo));
        $capacidad = $this->capacidadActiva($grupos);

        return [
            'grupos_guardados' => $grupos->count(),
            'grupos_activos' => $activos->count(),
            'capacidad_total' => $capacidad,
            'ocupacion_total' => $ocupacion,
            'cupos_disponibles' => max($capacidad - $ocupacion, 0),
        ];
    }

    private function formatGrupo(Grupo $grupo): array
    {
        $ocupacion = $this->ocupacionGrupo($grupo->codigo);
        $cupo = (int) ($grupo->cupo_maximo ?? self::CUPO_MAXIMO);

        return [
            'codigo' => $grupo->codigo,
            'descripcion' => $grupo->descripcion,
            'turno' => $grupo->turno,
            'cupo_maximo' => $cupo,
            'ocupacion' => $ocupacion,
            'cupos_disponibles' => max($cupo - $ocupacion, 0),
            'id_periodo_academico' => $grupo->id_periodo_academico,
            'estado' => $grupo->estado ?? 'activo',
        ];
    }

    private function ocupacionGrupo(string $codigo): int
    {
        return DB::table('academico.postulante_grupo')
            ->where('id_grupo', $codigo)
            ->where('estado', 'inscrito')
            ->count();
    }

    private function siguienteIndiceGrupo(): int
    {
        $maximo = Grupo::pluck('codigo')
            ->map(function (string $codigo): int {
                preg_match('/G(\d+)$/', $codigo, $coincidencias);

                return isset($coincidencias[1]) ? (int) $coincidencias[1] : 0;
            })
            ->max();

        return ((int) $maximo) + 1;
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
            'nombre' => $periodo->nombre
                ?: 'Periodo CUP '.$periodo->{'año'}.'-'.$periodo->semestre,
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
