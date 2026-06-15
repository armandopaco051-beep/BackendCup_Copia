<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\Docente;
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
        $dias = $this->dias();

        return response()->json([
            'periodo' => $this->formatPeriodo($periodo),
            'turnos' => collect(self::TURNOS)
                ->map(fn (array $turno, string $key): array => [
                    'key' => $key,
                    'nombre' => $turno['label'],
                    'bloques' => $this->bloquesTurno($key),
                ])
                ->values(),
            'grupos' => $this->grupos($periodo)->map(fn (Grupo $grupo): array => $this->formatGrupo($grupo))->values(),
            'materias' => $this->materias()->map(fn (Materia $materia): array => [
                'id' => $materia->id,
                'nombre' => $materia->nombre,
                'estado' => $materia->estado ?? 'habilitada',
            ])->values(),
            'aulas' => $this->aulas()->map(fn (Aula $aula): array => $this->formatAula($aula))->values(),
            'docentes' => $this->docentes(),
            'dias' => collect($dias)
                ->map(fn (int $id, string $nombre): array => [
                    'id' => $id,
                    'nombre' => $nombre,
                ])
                ->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedHorario($request);
        $this->validarDocenteMateria($validated['username_docente'], $validated['id_materia']);
        $this->validarCruces($validated);
        $this->validarCargaHorariaDocente($validated);

        $horario = HorarioGrupo::create([
            ...$validated,
            'turno' => $this->turnoNombre($validated['turno'] ?? null),
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Bloque de horario creado correctamente.',
            'horario' => $this->formatHorario($horario),
        ], 201);
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
            $cargaDocente = $this->cargaDocentesActual($periodo);
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
                            $docente = $this->docenteDisponible(
                                $materia->id,
                                $docentesPorMateria,
                                $ocupacionDocente,
                                $llaveTiempo,
                                $grupo->codigo,
                                $bloque['inicio'],
                                $bloque['fin'],
                                $cargaDocente,
                            );

                            if (! $docente) {
                                throw ValidationException::withMessages([
                                    'docentes' => "No hay docente libre con carga disponible para {$materia->nombre} en {$diaNombre} {$bloque['inicio']} - {$bloque['fin']}.",
                                ]);
                            }

                            $llaveAula = "{$aula->nro_aula}|{$llaveTiempo}";
                            if (isset($ocupacionAula[$llaveAula])) {
                                throw ValidationException::withMessages([
                                    'aulas' => "El aula {$aula->nro_aula} ya esta ocupada en {$diaNombre} {$bloque['inicio']} - {$bloque['fin']}.",
                                ]);
                            }

                            $ocupacionDocente["{$docente}|{$llaveTiempo}"] = true;
                            $this->registrarCargaDocente($cargaDocente, $docente, $grupo->codigo, $bloque['inicio'], $bloque['fin']);
                            $ocupacionAula[$llaveAula] = true;

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

    public function update(Request $request, HorarioGrupo $horarioGrupo): JsonResponse
    {
        $validated = $this->validatedHorario($request);
        $this->validarDocenteMateria($validated['username_docente'], $validated['id_materia']);
        $this->validarCruces($validated, $horarioGrupo->id);
        $this->validarCargaHorariaDocente($validated, $horarioGrupo->id);

        $horarioGrupo->update([
            ...$validated,
            'turno' => $this->turnoNombre($validated['turno'] ?? null),
        ]);

        return response()->json([
            'message' => 'Bloque de horario actualizado correctamente.',
            'horario' => $this->formatHorario($horarioGrupo->fresh()),
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

    private function validatedHorario(Request $request): array
    {
        $validated = $request->validate([
            'id_grupo' => ['required', 'string', 'max:100', Rule::exists('pgsql.academico.grupo', 'codigo')],
            'id_materia' => ['required', 'string', 'max:100', Rule::exists('pgsql.academico.materia', 'id')],
            'id_aula' => ['required', 'integer', Rule::exists('pgsql.academico.aula', 'nro_aula')],
            'username_docente' => ['required', 'string', 'max:500', Rule::exists('pgsql.academico.docente', 'username_docente')],
            'id_dia' => ['required', 'integer', Rule::exists('pgsql.academico.dia', 'id')],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i'],
            'turno' => ['required', 'string', Rule::in(['mañana', 'manana', 'maÃ±ana', 'tarde', 'noche'])],
            'id_periodo_academico' => ['nullable', 'integer', Rule::exists('pgsql.academico.periodo_academico', 'id')],
            'estado' => ['required', Rule::in(['propuesto', 'confirmado'])],
        ]);

        if (strtotime($validated['hora_fin']) <= strtotime($validated['hora_inicio'])) {
            throw ValidationException::withMessages([
                'hora_fin' => ['La hora final debe ser mayor a la hora inicial.'],
            ]);
        }

        $validated['hora_inicio'] = $validated['hora_inicio'].':00';
        $validated['hora_fin'] = $validated['hora_fin'].':00';
        $validated['id_periodo_academico'] = $validated['id_periodo_academico'] ?? $this->periodo(null)?->id;

        return $validated;
    }

    private function validarDocenteMateria(string $usernameDocente, string $idMateria): void
    {
        $this->validarDocenteHabilitado($usernameDocente);

        $exists = DocenteMateria::where('username_docente', $usernameDocente)
            ->where('id_materia', $idMateria)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'username_docente' => ['El docente seleccionado no tiene asignada esa materia.'],
            ]);
        }
    }

    private function validarDocenteHabilitado(string $usernameDocente): void
    {
        $docente = Docente::where('username_docente', $usernameDocente)->firstOrFail();

        if (! $docente->estaHabilitadoProfesionalmente()) {
            throw ValidationException::withMessages([
                'username_docente' => ['El docente no esta habilitado profesionalmente para ser asignado a horarios.'],
            ]);
        }
    }

    private function validarCruces(array $data, ?int $exceptId = null): void
    {
        $base = HorarioGrupo::where('id_dia', $data['id_dia'])
            ->where('hora_inicio', '<', $data['hora_fin'])
            ->where('hora_fin', '>', $data['hora_inicio'])
            ->whereIn('estado', ['propuesto', 'confirmado']);

        if ($exceptId) {
            $base->where('id', '<>', $exceptId);
        }

        if ((clone $base)->where('id_grupo', $data['id_grupo'])->exists()) {
            throw ValidationException::withMessages([
                'id_grupo' => ['El grupo ya tiene una materia asignada en ese horario.'],
            ]);
        }

        if ((clone $base)->where('id_aula', $data['id_aula'])->exists()) {
            throw ValidationException::withMessages([
                'id_aula' => ['El aula ya esta ocupada en ese horario.'],
            ]);
        }

        if ((clone $base)->where('username_docente', $data['username_docente'])->exists()) {
            throw ValidationException::withMessages([
                'username_docente' => ['El docente ya tiene otro horario asignado en ese bloque.'],
            ]);
        }
    }

    private function validarCargaHorariaDocente(array $data, ?int $exceptId = null): void
    {
        $docente = Docente::where('username_docente', $data['username_docente'])->firstOrFail();
        $maxGrupos = (int) ($docente->max_grupos_periodo ?? 3);
        $maxHoras = (float) ($docente->max_horas_semana ?? 30);

        $query = HorarioGrupo::where('username_docente', $data['username_docente'])
            ->whereIn('estado', ['propuesto', 'confirmado']);

        if ($exceptId) {
            $query->where('id', '<>', $exceptId);
        }

        $query = $data['id_periodo_academico']
            ? $query->where('id_periodo_academico', $data['id_periodo_academico'])
            : $query->whereNull('id_periodo_academico');

        $horarios = $query->get(['id_grupo', 'hora_inicio', 'hora_fin']);
        $grupos = $horarios->pluck('id_grupo')->push($data['id_grupo'])->unique()->values();

        if ($grupos->count() > $maxGrupos) {
            throw ValidationException::withMessages([
                'username_docente' => ["El docente supera la cantidad maxima de {$maxGrupos} grupo(s) permitida para este periodo."],
            ]);
        }

        $minutosActuales = $horarios->sum(fn (HorarioGrupo $horario): int => $this->minutosEntre($horario->hora_inicio, $horario->hora_fin));
        $minutosTotales = $minutosActuales + $this->minutosEntre($data['hora_inicio'], $data['hora_fin']);
        $horasTotales = round($minutosTotales / 60, 2);

        if ($horasTotales > $maxHoras) {
            throw ValidationException::withMessages([
                'username_docente' => ["El docente supera la carga maxima de {$maxHoras} hora(s) semanales. Carga calculada: {$horasTotales} hora(s)."],
            ]);
        }
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
            ->whereIn('username_docente', $this->docentesHabilitados()->pluck('username_docente')->all())
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

    private function docentes(): Collection
    {
        $materiasPorDocente = DocenteMateria::orderBy('username_docente')
            ->get()
            ->groupBy('username_docente')
            ->map(fn (Collection $items): array => $items->pluck('id_materia')->values()->all());

        return DB::table('academico.docente')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($docente): array => [
                'username' => $docente->username_docente,
                'nombre' => $docente->nombre,
                'correo' => $docente->correo ?? null,
                'titulo_profesional' => $docente->titulo_profesional ?? null,
                'estado_profesional' => $docente->estado_profesional ?? 'pendiente_revision',
                'max_grupos_periodo' => $docente->max_grupos_periodo ?? 3,
                'max_horas_semana' => $docente->max_horas_semana ?? 30,
                'materias_ids' => $materiasPorDocente[$docente->username_docente] ?? [],
            ]);
    }

    private function docentesHabilitados(): Collection
    {
        return Docente::where('estado_profesional', 'habilitado')
            ->whereNotNull('titulo_profesional')
            ->where('titulo_profesional', '<>', '')
            ->get(['username_docente']);
    }

    private function docenteDisponible(
        string $materiaId,
        array $docentesPorMateria,
        array $ocupacionDocente,
        string $llaveTiempo,
        string $codigoGrupo,
        string $horaInicio,
        string $horaFin,
        array $cargaDocente,
    ): ?string
    {
        foreach ($docentesPorMateria[$materiaId] ?? [] as $docente) {
            if (! isset($ocupacionDocente["{$docente}|{$llaveTiempo}"])
                && $this->docenteTieneCargaDisponible($docente, $codigoGrupo, $horaInicio, $horaFin, $cargaDocente)) {
                return $docente;
            }
        }

        return null;
    }

    private function cargaDocentesActual(?PeriodoAcademico $periodo): array
    {
        $query = HorarioGrupo::whereIn('estado', ['propuesto', 'confirmado']);
        $query = $periodo
            ? $query->where('id_periodo_academico', $periodo->id)
            : $query->whereNull('id_periodo_academico');

        $carga = [];

        foreach ($query->get(['username_docente', 'id_grupo', 'hora_inicio', 'hora_fin']) as $horario) {
            $this->registrarCargaDocente(
                $carga,
                $horario->username_docente,
                $horario->id_grupo,
                $horario->hora_inicio,
                $horario->hora_fin,
            );
        }

        return $carga;
    }

    private function docenteTieneCargaDisponible(string $usernameDocente, string $codigoGrupo, string $horaInicio, string $horaFin, array $cargaDocente): bool
    {
        $docente = Docente::where('username_docente', $usernameDocente)->first();
        $maxGrupos = (int) ($docente?->max_grupos_periodo ?? 3);
        $maxMinutos = (float) ($docente?->max_horas_semana ?? 30) * 60;
        $carga = $cargaDocente[$usernameDocente] ?? ['grupos' => [], 'minutos' => 0];
        $grupos = collect($carga['grupos'])->push($codigoGrupo)->unique();

        return $grupos->count() <= $maxGrupos
            && ((int) $carga['minutos'] + $this->minutosEntre($horaInicio, $horaFin)) <= $maxMinutos;
    }

    private function registrarCargaDocente(array &$cargaDocente, string $usernameDocente, string $codigoGrupo, string $horaInicio, string $horaFin): void
    {
        $cargaDocente[$usernameDocente] ??= ['grupos' => [], 'minutos' => 0];
        $cargaDocente[$usernameDocente]['grupos'] = collect($cargaDocente[$usernameDocente]['grupos'])
            ->push($codigoGrupo)
            ->unique()
            ->values()
            ->all();
        $cargaDocente[$usernameDocente]['minutos'] += $this->minutosEntre($horaInicio, $horaFin);
    }

    private function minutosEntre(string $inicio, string $fin): int
    {
        return max((int) ((strtotime($fin) - strtotime($inicio)) / 60), 0);
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
