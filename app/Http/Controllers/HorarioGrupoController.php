<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\DocenteMateria;
use App\Models\Grupo;
use App\Models\HorarioGrupo;
use App\Models\Materia;
use App\Models\PeriodoAcademico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HorarioGrupoController extends Controller
{
    private const DURACION_MINUTOS = 75;

    private const BLOQUES_POR_TURNO = 4;

    private const DIAS = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];

    private const TURNOS = [
        'manana' => ['label' => 'mañana', 'inicio' => '07:00'],
        'tarde' => ['label' => 'tarde', 'inicio' => '13:00'],
        'noche' => ['label' => 'noche', 'inicio' => '18:00'],
    ];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'periodo_id' => ['nullable', 'integer', Rule::exists('pgsql.academico.periodo_academico', 'id')],
        ]);

        $periodo = $this->periodo($validated['periodo_id'] ?? null);

        $query = HorarioGrupo::query()->orderBy('id_dia')->orderBy('turno')->orderBy('id_grupo')->orderBy('hora_inicio');

        if ($periodo) {
            $query->where('id_periodo_academico', $periodo->id);
        }

        $horarios = $query->get();

        return response()->json([
            'caso_uso' => 'Generar horarios por grupo',
            'periodo' => $this->formatPeriodo($periodo),
            'horarios' => $horarios->map(fn (HorarioGrupo $horario): array => $this->formatHorario($horario))->values(),
            'resumen' => [
                'total_bloques' => $horarios->count(),
                'propuestos' => $horarios->where('estado', 'propuesto')->count(),
                'confirmados' => $horarios->where('estado', 'confirmado')->count(),
            ],
        ]);
    }

    public function opciones(): JsonResponse
    {
        $periodo = $this->periodo(null);

        return response()->json([
            'periodo' => $this->formatPeriodo($periodo),
            'turnos' => collect(self::TURNOS)
                ->map(fn (array $turno, string $key): array => [
                    'key' => $key,
                    'nombre' => $turno['label'],
                    'bloques' => $this->bloquesTurno($key),
                ])
                ->values(),
            'dias' => self::DIAS,
            'grupos' => $this->grupos($periodo)->map(fn (Grupo $grupo): array => $this->formatGrupo($grupo))->values(),
            'materias' => $this->materias()->map(fn (Materia $materia): array => [
                'id' => $materia->id,
                'nombre' => $materia->nombre,
                'estado' => $materia->estado ?? 'habilitada',
            ])->values(),
            'aulas' => $this->aulas()->map(fn (Aula $aula): array => $this->formatAula($aula))->values(),
        ]);
    }

    public function generar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'periodo_id' => ['nullable', 'integer', Rule::exists('pgsql.academico.periodo_academico', 'id')],
            'sobrescribir' => ['nullable', 'boolean'],
        ]);

        $periodo = $this->periodo($validated['periodo_id'] ?? null);
        $grupos = $this->grupos($periodo);
        $materias = $this->materias()->take(self::BLOQUES_POR_TURNO)->values();
        $aulas = $this->aulas()->values();

        if ($grupos->isEmpty()) {
            throw ValidationException::withMessages([
                'grupos' => 'Primero genera la distribucion de grupos.',
            ]);
        }

        if ($materias->count() < self::BLOQUES_POR_TURNO) {
            throw ValidationException::withMessages([
                'materias' => 'Debes tener al menos 4 materias habilitadas para crear los horarios.',
            ]);
        }

        $this->validarAulasPorTurno($grupos, $aulas);
        $docentesPorMateria = $this->docentesPorMateria($materias);

        $confirmados = $this->horariosPeriodo($periodo)->where('estado', 'confirmado');
        if ($confirmados->exists()) {
            throw ValidationException::withMessages([
                'horarios' => 'Este periodo ya tiene horarios confirmados. No se puede sobrescribir una version confirmada.',
            ]);
        }

        $existentes = $this->horariosPeriodo($periodo)->exists();
        if ($existentes && ! ($validated['sobrescribir'] ?? false)) {
            throw ValidationException::withMessages([
                'horarios' => 'Ya existe una propuesta. Marca sobrescribir para regenerarla.',
            ]);
        }

        $creados = DB::transaction(function () use ($periodo, $grupos, $materias, $aulas, $docentesPorMateria): Collection {
            $this->horariosPeriodo($periodo)->where('estado', 'propuesto')->delete();

            $dias = $this->dias();
            $aulasPorGrupo = $this->asignarAulas($grupos, $aulas);
            $ocupacionDocente = [];
            $ocupacionAula = [];
            $horarios = collect();

            $gruposPorTurno = $grupos->groupBy(fn (Grupo $grupo): string => $this->turnoKey($grupo->turno));

            foreach ($gruposPorTurno as $turnoKey => $gruposTurno) {
                $bloques = $this->bloquesTurno($turnoKey);

                foreach ($gruposTurno->values() as $indiceGrupo => $grupo) {
                    foreach (self::DIAS as $indiceDia => $diaNombre) {
                        $diaId = $dias[$diaNombre];

                        foreach ($bloques as $indiceBloque => $bloque) {
                            $materia = $materias[($indiceGrupo + $indiceDia + $indiceBloque) % $materias->count()];
                            $aula = $aulasPorGrupo[$grupo->codigo];
                            $llaveTiempo = "{$diaId}|{$bloque['inicio']}|{$bloque['fin']}";
                            $docente = $this->docenteDisponible($materia->id, $docentesPorMateria, $ocupacionDocente, $llaveTiempo);

                            if (! $docente) {
                                throw ValidationException::withMessages([
                                    'docentes' => "No hay docente libre para {$materia->nombre} en {$diaNombre} {$bloque['inicio']} - {$bloque['fin']}.",
                                ]);
                            }

                            $llaveAula = "{$aula->nro_aula}|{$llaveTiempo}";
                            if (isset($ocupacionAula[$llaveAula])) {
                                throw ValidationException::withMessages([
                                    'aulas' => "El aula {$aula->nro_aula} ya esta ocupada en {$diaNombre} {$bloque['inicio']} - {$bloque['fin']}.",
                                ]);
                            }

                            $ocupacionDocente["{$docente}|{$llaveTiempo}"] = true;
                            $ocupacionAula[$llaveAula] = true;

                            DB::table('academico.docente_grupo')->updateOrInsert([
                                'username_docente' => $docente,
                                'codigo_grupo' => $grupo->codigo,
                            ], [
                                'created_at' => now(),
                            ]);

                            $horarios->push(HorarioGrupo::create([
                                'id_grupo' => $grupo->codigo,
                                'id_materia' => $materia->id,
                                'id_aula' => $aula->nro_aula,
                                'username_docente' => $docente,
                                'id_dia' => $diaId,
                                'hora_inicio' => $bloque['inicio'],
                                'hora_fin' => $bloque['fin'],
                                'turno' => self::TURNOS[$turnoKey]['label'],
                                'id_periodo_academico' => $periodo?->id,
                                'estado' => 'propuesto',
                                'created_at' => now(),
                            ]));
                        }
                    }
                }
            }

            return $horarios;
        });

        return response()->json([
            'caso_uso' => 'Generar horarios por grupo',
            'message' => 'Propuesta de horarios generada correctamente.',
            'periodo' => $this->formatPeriodo($periodo),
            'total_bloques' => $creados->count(),
            'horarios' => $creados->map(fn (HorarioGrupo $horario): array => $this->formatHorario($horario))->values(),
        ], 201);
    }

    public function confirmar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'periodo_id' => ['nullable', 'integer', Rule::exists('pgsql.academico.periodo_academico', 'id')],
        ]);

        $periodo = $this->periodo($validated['periodo_id'] ?? null);
        $query = $this->horariosPeriodo($periodo)->where('estado', 'propuesto');
        $total = (clone $query)->count();

        if ($total === 0) {
            throw ValidationException::withMessages([
                'horarios' => 'No hay horarios propuestos para confirmar.',
            ]);
        }

        $query->update(['estado' => 'confirmado']);

        return response()->json([
            'message' => 'Horarios confirmados correctamente.',
            'periodo' => $this->formatPeriodo($periodo),
            'total_confirmados' => $total,
        ]);
    }

    public function destroy(HorarioGrupo $horarioGrupo): JsonResponse
    {
        if ($horarioGrupo->estado === 'confirmado') {
            throw ValidationException::withMessages([
                'horario' => 'No se puede eliminar un horario confirmado.',
            ]);
        }

        $horarioGrupo->delete();

        return response()->json([
            'message' => 'Bloque de horario eliminado correctamente.',
        ]);
    }

    private function grupos(?PeriodoAcademico $periodo): Collection
    {
        $query = Grupo::orderBy('codigo');
        $columns = $this->columns('grupo');

        if ($periodo && in_array('id_periodo_academico', $columns, true)) {
            $query->where('id_periodo_academico', $periodo->id);
        }

        if (in_array('estado', $columns, true)) {
            $query->where('estado', 'activo');
        }

        return $query->get();
    }

    private function materias(): Collection
    {
        $query = Materia::orderBy('nombre');

        if (in_array('estado', $this->columns('materia'), true)) {
            $query->where('estado', 'habilitada');
        }

        return $query->get();
    }

    private function aulas(): Collection
    {
        $query = Aula::orderBy('nro_aula');
        $columns = $this->columns('aula');

        if (in_array('estado', $columns, true)) {
            $query->where(function ($inner): void {
                $inner->where('estado', 'disponible')->orWhereNull('estado');
            });
        }

        return $query->get();
    }

    private function validarAulasPorTurno(Collection $grupos, Collection $aulas): void
    {
        if ($aulas->isEmpty()) {
            throw ValidationException::withMessages([
                'aulas' => 'Registra aulas disponibles antes de generar horarios.',
            ]);
        }

        $grupos->groupBy(fn (Grupo $grupo): string => $this->turnoKey($grupo->turno))
            ->each(function (Collection $gruposTurno, string $turno) use ($aulas): void {
                if ($gruposTurno->count() > $aulas->count()) {
                    throw ValidationException::withMessages([
                        'aulas' => "No hay aulas suficientes para el turno {$this->turnoNombre($turno)}. Se necesitan {$gruposTurno->count()} y hay {$aulas->count()}.",
                    ]);
                }
            });
    }

    private function asignarAulas(Collection $grupos, Collection $aulas): array
    {
        $asignadas = [];

        $grupos->groupBy(fn (Grupo $grupo): string => $this->turnoKey($grupo->turno))
            ->each(function (Collection $gruposTurno) use (&$asignadas, $aulas): void {
                foreach ($gruposTurno->values() as $index => $grupo) {
                    $asignadas[$grupo->codigo] = $aulas[$index];
                }
            });

        return $asignadas;
    }

    private function docentesPorMateria(Collection $materias): array
    {
        $materiasIds = $materias->pluck('id')->all();
        $asignaciones = DocenteMateria::whereIn('id_materia', $materiasIds)
            ->orderBy('username_docente')
            ->get()
            ->groupBy('id_materia');

        foreach ($materias as $materia) {
            if (! $asignaciones->has($materia->id) || $asignaciones[$materia->id]->isEmpty()) {
                throw ValidationException::withMessages([
                    'docentes' => "Asigna al menos un docente para la materia {$materia->nombre}.",
                ]);
            }
        }

        return $asignaciones
            ->map(fn (Collection $items): array => $items->pluck('username_docente')->values()->all())
            ->all();
    }

    private function docenteDisponible(string $materiaId, array $docentesPorMateria, array $ocupacionDocente, string $llaveTiempo): ?string
    {
        foreach ($docentesPorMateria[$materiaId] ?? [] as $docente) {
            if (! isset($ocupacionDocente["{$docente}|{$llaveTiempo}"])) {
                return $docente;
            }
        }

        return null;
    }

    private function dias(): array
    {
        $dias = [];

        foreach (self::DIAS as $dia) {
            $registro = DB::table('academico.dia')->where('nombre', $dia)->first();

            if (! $registro) {
                $id = DB::table('academico.dia')->insertGetId(['nombre' => $dia]);
                $dias[$dia] = $id;
                continue;
            }

            $dias[$dia] = (int) $registro->id;
        }

        return $dias;
    }

    private function bloquesTurno(string $turno): array
    {
        $turnoKey = $this->turnoKey($turno);
        $inicio = self::TURNOS[$turnoKey]['inicio'];
        [$hora, $minuto] = array_map('intval', explode(':', $inicio));
        $fecha = now()->setTime($hora, $minuto, 0);
        $bloques = [];

        for ($index = 0; $index < self::BLOQUES_POR_TURNO; $index++) {
            $fin = $fecha->copy()->addMinutes(self::DURACION_MINUTOS);
            $bloques[] = [
                'inicio' => $fecha->format('H:i:s'),
                'fin' => $fin->format('H:i:s'),
                'etiqueta' => $fecha->format('H:i').' - '.$fin->format('H:i'),
            ];
            $fecha = $fin;
        }

        return $bloques;
    }

    private function turnoKey(?string $turno): string
    {
        $normalizado = str_replace(['á', 'Ã¡', 'ñ', 'Ã±'], ['a', 'a', 'n', 'n'], mb_strtolower((string) $turno));

        return match (true) {
            str_contains($normalizado, 'tarde') => 'tarde',
            str_contains($normalizado, 'noche') => 'noche',
            default => 'manana',
        };
    }

    private function turnoNombre(string $turno): string
    {
        return self::TURNOS[$this->turnoKey($turno)]['label'];
    }

    private function periodo(?int $periodoId): ?PeriodoAcademico
    {
        if ($periodoId) {
            return PeriodoAcademico::find($periodoId);
        }

        return PeriodoAcademico::orderByDesc('id')->first();
    }

    private function horariosPeriodo(?PeriodoAcademico $periodo)
    {
        $query = HorarioGrupo::query();

        return $periodo
            ? $query->where('id_periodo_academico', $periodo->id)
            : $query->whereNull('id_periodo_academico');
    }

    private function formatHorario(HorarioGrupo $horario): array
    {
        $grupo = Grupo::find($horario->id_grupo);
        $materia = Materia::find($horario->id_materia);
        $aula = Aula::find($horario->id_aula);
        $docente = DB::table('academico.docente')->where('username_docente', $horario->username_docente)->first();
        $dia = DB::table('academico.dia')->where('id', $horario->id_dia)->first();

        return [
            'id' => $horario->id,
            'grupo' => [
                'codigo' => $horario->id_grupo,
                'descripcion' => $grupo?->descripcion,
                'turno' => $grupo?->turno ?? $horario->turno,
            ],
            'materia' => [
                'id' => $horario->id_materia,
                'nombre' => $materia?->nombre ?? $horario->id_materia,
            ],
            'aula' => $aula ? $this->formatAula($aula) : ['nro_aula' => $horario->id_aula],
            'docente' => [
                'username' => $horario->username_docente,
                'nombre' => $docente?->nombre ?? $horario->username_docente,
            ],
            'dia' => [
                'id' => $horario->id_dia,
                'nombre' => $dia?->nombre ?? 'Dia '.$horario->id_dia,
            ],
            'hora_inicio' => substr((string) $horario->hora_inicio, 0, 5),
            'hora_fin' => substr((string) $horario->hora_fin, 0, 5),
            'turno' => $horario->turno,
            'estado' => $horario->estado,
        ];
    }

    private function formatGrupo(Grupo $grupo): array
    {
        return [
            'codigo' => $grupo->codigo,
            'descripcion' => $grupo->descripcion,
            'turno' => $this->turnoNombre($grupo->turno),
            'cupo_maximo' => $grupo->cupo_maximo ?? 70,
            'estado' => $grupo->estado ?? 'activo',
        ];
    }

    private function formatAula(Aula $aula): array
    {
        return [
            'nro_aula' => $aula->nro_aula,
            'tipo' => $aula->tipo,
            'piso' => $aula->piso,
            'capacidad' => $aula->capacidad ?? 70,
            'estado' => $aula->estado ?? 'disponible',
        ];
    }

    private function formatPeriodo(?PeriodoAcademico $periodo): ?array
    {
        if (! $periodo) {
            return null;
        }

        return [
            'id' => $periodo->id,
            'nombre' => $periodo->nombre ?? 'Periodo CUP '.$periodo->semestre,
            'semestre' => $periodo->semestre,
            'anio' => $periodo->anio ?? $periodo->{'aÃ±o'} ?? null,
            'estado' => $periodo->estado ?? null,
        ];
    }

    private function columns(string $table): array
    {
        return DB::table('information_schema.columns')
            ->where('table_schema', 'academico')
            ->where('table_name', $table)
            ->pluck('column_name')
            ->all();
    }
}
