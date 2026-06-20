<?php

namespace App\Services;

use App\Models\ActaNota;
use App\Models\AsignacionCarrera;
use App\Models\Carrera;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Pago;
use App\Models\PeriodoAcademico;
use App\Models\Postulante;
use App\Models\PostulanteCarrera;
use App\Models\PostulanteGrupo;
use App\Models\RequisitoPostulante;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReporteService
{
    private const NOTA_MINIMA = 60;

    public const TIPOS = [
        'postulantes',
        'lista_admitidos',
        'postulantes_aprobados',
        'postulantes_reprobados',
        'pagos',
        'calificaciones',
        'resultados_estudiantes',
        'estadisticas_materia',
        'grupos_habilitados',
        'docentes_grupo',
        'rendimiento_grupos',
    ];
    // hace la generacion de los reportes
    public function generar(array $filtros, int $limite = 500): array
    {
        return match ($filtros['tipo'] ?? 'postulantes') {
            'lista_admitidos' => $this->reporteListaAdmitidos($filtros, $limite),
            'postulantes_aprobados' => $this->reporteResultadosEstudiantes([
                ...$filtros,
                'estado' => 'aprobado',
            ], $limite),
            'postulantes_reprobados' => $this->reporteResultadosEstudiantes([
                ...$filtros,
                'estado' => 'reprobado',
            ], $limite),
            'pagos' => $this->reportePagos($filtros, $limite),
            'calificaciones' => $this->reporteCalificaciones($filtros, $limite),
            'resultados_estudiantes' => $this->reporteResultadosEstudiantes($filtros, $limite),
            'estadisticas_materia' => $this->reporteEstadisticasMateria($filtros, $limite),
            'grupos_habilitados' => $this->reporteGruposHabilitados($filtros, $limite),
            'docentes_grupo' => $this->reporteDocentesGrupo($filtros, $limite),
            'rendimiento_grupos' => $this->reporteRendimientoGrupos($filtros, $limite),
            default => $this->reportePostulantes($filtros, $limite),
        };
    }
    // hace el reporte de lista de admitidos
    private function reporteListaAdmitidos(array $filtros, int $limite): array
    {
        $periodoId = $filtros['periodo'] ?? $this->periodoActivoId();
        $query = DB::table('academico.asignacion_carrera as asignacion')
            ->join(
                'academico.postulante as postulante',
                'postulante.username_postulante',
                '=',
                'asignacion.username_postulante',
            )
            ->join('academico.carrera as carrera', 'carrera.codigo', '=', 'asignacion.id_carrera')
            ->where('asignacion.estado', 'asignado')
            ->select(
                'asignacion.username_postulante',
                'postulante.ci',
                'postulante.nombre',
                'asignacion.id_periodo_academico',
                'asignacion.promedio_final',
                'asignacion.nota3_promedio',
                'asignacion.nota2_promedio',
                'asignacion.nota1_promedio',
                'asignacion.opcion_asignada',
                'asignacion.id_carrera',
                'carrera.nombre as carrera',
                'asignacion.motivo',
            );

        if ($periodoId) {
            $query->where('asignacion.id_periodo_academico', $periodoId);
        }

        if (! empty($filtros['buscar'])) {
            $buscar = mb_strtolower($filtros['buscar']);
            $query->where(function ($subquery) use ($buscar): void {
                $subquery
                    ->whereRaw('lower(asignacion.username_postulante) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(postulante.ci) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(postulante.nombre) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(carrera.nombre) like ?', ["%{$buscar}%"]);
            });
        }

        if (! empty($filtros['carrera'])) {
            $query->where('asignacion.id_carrera', $filtros['carrera']);
        }

        if (! empty($filtros['grupo'])) {
            $query->whereExists(function ($subquery) use ($filtros): void {
                $subquery->selectRaw('1')
                    ->from('academico.postulante_grupo as inscripcion')
                    ->whereColumn(
                        'inscripcion.username_postulante',
                        'asignacion.username_postulante',
                    )
                    ->where('inscripcion.estado', 'inscrito');

                if (! empty($filtros['grupo'])) {
                    $subquery->where('inscripcion.id_grupo', $filtros['grupo']);
                }
            });
        }
        $this->filtrarDocentePostulanteQueryBuilder($query, $filtros, 'asignacion.username_postulante');

        $total = (clone $query)->count();
        $admitidos = $query
            ->orderByDesc('asignacion.promedio_final')
            ->orderByDesc('asignacion.nota3_promedio')
            ->orderByDesc('asignacion.nota2_promedio')
            ->orderByDesc('asignacion.nota1_promedio')
            ->orderBy('asignacion.username_postulante')
            ->limit($limite)
            ->get();

        $filas = $admitidos->values()->map(fn (object $admitido, int $indice): array => [
            'posicion' => $indice + 1,
            'folio' => $admitido->username_postulante,
            'ci' => $admitido->ci,
            'postulante' => $admitido->nombre,
            'promedio_final' => number_format((float) $admitido->promedio_final, 2, '.', ''),
            'carrera' => $admitido->carrera,
            'opcion_asignada' => $admitido->opcion_asignada
                ? 'Opcion '.$admitido->opcion_asignada
                : '-',
            'estado' => 'admitido',
            'motivo' => $admitido->motivo ?? 'Asignado por promedio y cupo disponible.',
        ]);

        $respuesta = $this->respuesta(
            'lista_admitidos',
            'Lista oficial de admitidos al CUP',
            [
                'posicion' => 'Posicion',
                'folio' => 'Folio',
                'ci' => 'CI',
                'postulante' => 'Postulante',
                'promedio_final' => 'Promedio final',
                'carrera' => 'Carrera asignada',
                'opcion_asignada' => 'Opcion',
                'estado' => 'Estado',
                'motivo' => 'Criterio de asignacion',
            ],
            $filas,
            $total,
            [
                'admitidos' => $total,
                'carreras_con_admitidos' => $admitidos->pluck('id_carrera')->unique()->count(),
                'promedio_general' => round($admitidos->avg('promedio_final') ?? 0, 2),
            ],
            $filtros,
        );

        return [
            ...$respuesta,
            'lista_generada' => AsignacionCarrera::when($periodoId, fn ($query) => $query->where('id_periodo_academico', $periodoId))->exists(),
            'generar_url' => '/dashboard/asignacion-carreras',
        ];
    }
    // hace el reporte de postulantes
    private function reportePostulantes(array $filtros, int $limite): array
    {
        $query = Postulante::query()
            ->with('periodoAcademico')
            ->where('estado', '!=', 'pendiente_pago')
            ->orderByDesc('username_postulante');

        if (! empty($filtros['buscar'])) {
            $buscar = mb_strtolower($filtros['buscar']);
            $query->where(function (Builder $subquery) use ($buscar): void {
                $subquery
                    ->whereRaw('lower(username_postulante) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(ci) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(nombre) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(correo) like ?', ["%{$buscar}%"]);
            });
        }

        $this->filtrarEstadoPostulante($query, $filtros['estado'] ?? null);

        $this->filtrarCarrera($query, $filtros);
        $this->filtrarGrupoPeriodo($query, $filtros, false, true);
        $this->filtrarDocentePostulante($query, $filtros);
        $this->filtrarFechaPago($query, $filtros);

        $total = (clone $query)->count();
        $postulantes = $query->limit($limite)->get();
        $contexto = $this->contextoPostulantes($postulantes->pluck('username_postulante'));

        $filas = $postulantes->map(function (Postulante $postulante) use ($contexto): array {
            $username = $postulante->username_postulante;
            $pago = $contexto['pagos']->get($username);
            $requisito = $contexto['requisitos']->get($username);

            return [
                'folio' => $username,
                'ci' => $postulante->ci,
                'postulante' => $postulante->nombre,
                'correo' => $postulante->correo,
                'carrera' => $this->nombresCarreras($username, $contexto),
                'grupo' => $this->nombresGrupos($username, $contexto),
                'periodo' => $postulante->periodoAcademico?->nombre
                    ?? $this->nombresPeriodos($username, $contexto),
                'estado' => $postulante->estado,
                'pago' => $pago?->estado ?? 'sin_pago',
                'requisitos' => $this->estadoRequisitos($requisito),
                'fecha' => $pago?->fecha_pago?->format('Y-m-d') ?? '-',
            ];
        })->values();

        return $this->respuesta(
            'postulantes',
            'Reporte administrativo de postulantes',
            [
                'folio' => 'Folio',
                'ci' => 'CI',
                'postulante' => 'Postulante',
                'correo' => 'Correo',
                'carrera' => 'Carrera',
                'grupo' => 'Grupo',
                'periodo' => 'Periodo',
                'estado' => 'Estado',
                'pago' => 'Pago',
                'requisitos' => 'Requisitos',
                'fecha' => 'Fecha de pago',
            ],
            $filas,
            $total,
            [
                'habilitados' => $filas->whereIn('estado', ['habilitado', 'admitido'])->count(),
                'pagados' => $filas->whereIn('pago', ['pagado', 'registrado'])->count(),
                'pendientes' => $filas->whereNotIn('estado', ['habilitado', 'admitido'])->count(),
            ],
            $filtros,
        );
    }
    // hace el reporte de pagos
    private function reportePagos(array $filtros, int $limite): array
    {
        $query = Pago::query()->orderByDesc('id');

        if (! empty($filtros['buscar'])) {
            $buscar = mb_strtolower($filtros['buscar']);
            $postulantes = Postulante::query()
                ->select('username_postulante')
                ->where(function (Builder $subquery) use ($buscar): void {
                    $subquery
                        ->whereRaw('lower(username_postulante) like ?', ["%{$buscar}%"])
                        ->orWhereRaw('lower(ci) like ?', ["%{$buscar}%"])
                        ->orWhereRaw('lower(nombre) like ?', ["%{$buscar}%"]);
                });

            $query->where(function (Builder $subquery) use ($buscar, $postulantes): void {
                $subquery
                    ->whereRaw('lower(nro_comprobante) like ?', ["%{$buscar}%"])
                    ->orWhereIn('username_postulante', $postulantes);
            });
        }

        if (! empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        $this->filtrarCarrera($query, $filtros);
        $this->filtrarGrupoPeriodo($query, $filtros);
        $this->filtrarDocentePostulante($query, $filtros);

        if (! empty($filtros['fecha_inicio'])) {
            $query->whereDate('fecha_pago', '>=', $filtros['fecha_inicio']);
        }
        if (! empty($filtros['fecha_fin'])) {
            $query->whereDate('fecha_pago', '<=', $filtros['fecha_fin']);
        }

        $total = (clone $query)->count();
        $pagos = $query->limit($limite)->get();
        $postulantes = Postulante::whereIn('username_postulante', $pagos->pluck('username_postulante'))
            ->get()
            ->keyBy('username_postulante');

        $filas = $pagos->map(function (Pago $pago) use ($postulantes): array {
            $postulante = $postulantes->get($pago->username_postulante);

            return [
                'folio' => $pago->username_postulante,
                'postulante' => $postulante?->nombre ?? $pago->username_postulante,
                'ci' => $postulante?->ci ?? '-',
                'comprobante' => $pago->nro_comprobante,
                'monto' => number_format((float) $pago->monto, 2, '.', ''),
                'fecha' => $pago->fecha_pago?->format('Y-m-d') ?? '-',
                'estado' => $pago->estado,
                'registrado_por' => $pago->registrado_por ?? 'Stripe',
                'observacion' => $pago->observacion ?? '-',
            ];
        })->values();

        return $this->respuesta(
            'pagos',
            'Reporte administrativo de pagos',
            [
                'folio' => 'Folio',
                'postulante' => 'Postulante',
                'ci' => 'CI',
                'comprobante' => 'Comprobante',
                'monto' => 'Monto (Bs.)',
                'fecha' => 'Fecha',
                'estado' => 'Estado',
                'registrado_por' => 'Registrado por',
                'observacion' => 'Observacion',
            ],
            $filas,
            $total,
            [
                'confirmados' => $filas->whereIn('estado', ['pagado', 'registrado'])->count(),
                'pendientes' => $filas->where('estado', 'pendiente')->count(),
                'monto_mostrado' => round($pagos->sum(fn (Pago $pago): float => (float) $pago->monto), 2),
            ],
            $filtros,
        );
    }
    // hace el reporte de calificaciones
    private function reporteCalificaciones(array $filtros, int $limite): array
    {
        $query = ActaNota::query()->orderByDesc('id');

        if (! empty($filtros['buscar'])) {
            $buscar = mb_strtolower($filtros['buscar']);
            $postulantes = Postulante::query()
                ->select('username_postulante')
                ->where(function (Builder $subquery) use ($buscar): void {
                    $subquery
                        ->whereRaw('lower(username_postulante) like ?', ["%{$buscar}%"])
                        ->orWhereRaw('lower(ci) like ?', ["%{$buscar}%"])
                        ->orWhereRaw('lower(nombre) like ?', ["%{$buscar}%"]);
                });

            $query->where(function (Builder $subquery) use ($buscar, $postulantes): void {
                $subquery
                    ->whereRaw('lower(id_grupo) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(id_materia) like ?', ["%{$buscar}%"])
                    ->orWhereIn('username_postulante', $postulantes);
            });
        }

        if (($filtros['estado'] ?? null) === 'aprobado') {
            $query->where('promedio', '>=', 60);
        } elseif (($filtros['estado'] ?? null) === 'reprobado') {
            $query->where('promedio', '<', 60);
        }

        if (! empty($filtros['grupo'])) {
            $query->where('id_grupo', $filtros['grupo']);
        }
        if (! empty($filtros['materia'])) {
            $query->where('id_materia', $filtros['materia']);
        }

        $this->filtrarCarrera($query, $filtros);
        $this->filtrarGrupoPeriodo($query, $filtros, true);
        $this->filtrarDocenteNotas($query, $filtros);

        $total = (clone $query)->count();
        $calificaciones = $query->limit($limite)->get();
        $usernames = $calificaciones->pluck('username_postulante')->unique();
        $postulantes = Postulante::whereIn('username_postulante', $usernames)
            ->get()
            ->keyBy('username_postulante');
        $materias = Materia::whereIn('id', $calificaciones->pluck('id_materia')->unique())
            ->get()
            ->keyBy('id');

        $filas = $calificaciones->map(function (ActaNota $nota) use ($postulantes, $materias): array {
            $postulante = $postulantes->get($nota->username_postulante);
            $promedio = round((float) $nota->promedio, 2);

            return [
                'folio' => $nota->username_postulante,
                'postulante' => $postulante?->nombre ?? $nota->username_postulante,
                'ci' => $postulante?->ci ?? '-',
                'grupo' => $nota->id_grupo,
                'materia' => $materias->get($nota->id_materia)?->nombre ?? $nota->id_materia,
                'nota1' => $nota->nota1 ?? '-',
                'nota2' => $nota->nota2 ?? '-',
                'nota3' => $nota->nota3 ?? '-',
                'promedio' => number_format($promedio, 2, '.', ''),
                'estado' => $promedio >= 60 ? 'aprobado' : 'reprobado',
            ];
        })->values();

        return $this->respuesta(
            'calificaciones',
            'Reporte administrativo de calificaciones',
            [
                'folio' => 'Folio',
                'postulante' => 'Postulante',
                'ci' => 'CI',
                'grupo' => 'Grupo',
                'materia' => 'Materia',
                'nota1' => 'Nota 1',
                'nota2' => 'Nota 2',
                'nota3' => 'Nota 3',
                'promedio' => 'Promedio',
                'estado' => 'Estado',
            ],
            $filas,
            $total,
            [
                'aprobados' => $filas->where('estado', 'aprobado')->count(),
                'reprobados' => $filas->where('estado', 'reprobado')->count(),
                'promedio_mostrado' => round($calificaciones->avg('promedio') ?? 0, 2),
            ],
            $filtros,
        );
    }
    // hace el reporte de resultados de estudiantes
    private function reporteResultadosEstudiantes(array $filtros, int $limite): array
    {
        $tipoSolicitado = $filtros['tipo'] ?? 'resultados_estudiantes';
        $estadoSolicitado = match ($tipoSolicitado) {
            'postulantes_aprobados' => 'aprobado',
            'postulantes_reprobados' => 'reprobado',
            default => $filtros['estado'] ?? null,
        };

        $query = DB::table('academico.acta_nota as nota')
            ->join('academico.postulante as postulante', 'postulante.username_postulante', '=', 'nota.username_postulante')
            ->selectRaw('
                nota.username_postulante,
                postulante.ci,
                postulante.nombre,
                COUNT(DISTINCT nota.id_materia) as materias_evaluadas,
                ROUND(AVG(nota.nota1)::numeric, 2) as nota1_promedio,
                ROUND(AVG(nota.nota2)::numeric, 2) as nota2_promedio,
                ROUND(AVG(nota.nota3)::numeric, 2) as nota3_promedio,
                ROUND(AVG(nota.promedio)::numeric, 2) as promedio_general,
                STRING_AGG(DISTINCT nota.id_grupo, \', \' ORDER BY nota.id_grupo) as grupos
            ')
            ->whereNotNull('nota.promedio')
            ->groupBy('nota.username_postulante', 'postulante.ci', 'postulante.nombre');

        if (! empty($filtros['buscar'])) {
            $buscar = mb_strtolower($filtros['buscar']);
            $query->where(function ($subquery) use ($buscar): void {
                $subquery
                    ->whereRaw('lower(nota.username_postulante) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(postulante.ci) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(postulante.nombre) like ?', ["%{$buscar}%"]);
            });
        }

        if (! empty($filtros['grupo'])) {
            $query->where('nota.id_grupo', $filtros['grupo']);
        }
        if (! empty($filtros['materia'])) {
            $query->where('nota.id_materia', $filtros['materia']);
        }

        if (! empty($filtros['periodo'])) {
            $query->whereExists(function ($subquery) use ($filtros): void {
                $subquery->selectRaw('1')
                    ->from('academico.postulante_grupo as inscripcion')
                    ->whereColumn('inscripcion.username_postulante', 'nota.username_postulante')
                    ->whereColumn('inscripcion.id_grupo', 'nota.id_grupo')
                    ->where('inscripcion.id_periodo_academico', $filtros['periodo'])
                    ->where('inscripcion.estado', 'inscrito');
            });
        }

        $this->filtrarCarreraQueryBuilder($query, $filtros, 'nota.username_postulante');
        $this->filtrarDocenteNotasQueryBuilder($query, $filtros);

        if ($estadoSolicitado === 'aprobado') {
            $query->havingRaw('AVG(nota.promedio) >= ?', [self::NOTA_MINIMA]);
        } elseif ($estadoSolicitado === 'reprobado') {
            $query->havingRaw('AVG(nota.promedio) < ?', [self::NOTA_MINIMA]);
        }

        $total = DB::query()->fromSub(clone $query, 'resultados')->count();
        $resultados = $query
            ->orderByDesc(DB::raw('AVG(nota.promedio)'))
            ->limit($limite)
            ->get();
        $contexto = $this->contextoPostulantes($resultados->pluck('username_postulante'));

        $filas = $resultados->map(function (object $resultado) use ($contexto): array {
            $promedio = round((float) $resultado->promedio_general, 2);

            return [
                'folio' => $resultado->username_postulante,
                'ci' => $resultado->ci,
                'postulante' => $resultado->nombre,
                'carrera' => $this->nombresCarreras($resultado->username_postulante, $contexto),
                'grupo' => $resultado->grupos ?: 'Sin grupo',
                'materias_evaluadas' => (int) $resultado->materias_evaluadas,
                'nota1_promedio' => number_format((float) $resultado->nota1_promedio, 2, '.', ''),
                'nota2_promedio' => number_format((float) $resultado->nota2_promedio, 2, '.', ''),
                'nota3_promedio' => number_format((float) $resultado->nota3_promedio, 2, '.', ''),
                'promedio_general' => number_format($promedio, 2, '.', ''),
                'estado' => $promedio >= self::NOTA_MINIMA ? 'aprobado' : 'reprobado',
            ];
        })->values();

        return $this->respuesta(
            $tipoSolicitado,
            match ($tipoSolicitado) {
                'postulantes_aprobados' => 'Postulantes aprobados y promedios finales',
                'postulantes_reprobados' => 'Postulantes reprobados y promedios finales',
                default => 'Resultados generales por estudiante',
            },
            [
                'folio' => 'Folio',
                'ci' => 'CI',
                'postulante' => 'Postulante',
                'carrera' => 'Carrera',
                'grupo' => 'Grupo',
                'materias_evaluadas' => 'Materias evaluadas',
                'nota1_promedio' => 'Promedio Nota 1',
                'nota2_promedio' => 'Promedio Nota 2',
                'nota3_promedio' => 'Promedio Nota 3',
                'promedio_general' => 'Promedio final',
                'estado' => 'Resultado',
            ],
            $filas,
            $total,
            [
                'aprobados' => $filas->where('estado', 'aprobado')->count(),
                'reprobados' => $filas->where('estado', 'reprobado')->count(),
                'promedio_general' => round($resultados->avg('promedio_general') ?? 0, 2),
                'nota_minima' => self::NOTA_MINIMA,
            ],
            $filtros,
        );
    }
    // hace el reporte de estadisticas de materia
    private function reporteEstadisticasMateria(array $filtros, int $limite): array
    {
        $query = DB::table('academico.acta_nota as nota')
            ->join('academico.materia as materia', 'materia.id', '=', 'nota.id_materia')
            ->selectRaw('
                nota.id_materia,
                materia.nombre as materia,
                COUNT(DISTINCT nota.username_postulante) as estudiantes,
                ROUND(AVG(nota.promedio)::numeric, 2) as promedio,
                COUNT(DISTINCT nota.username_postulante) FILTER (WHERE nota.promedio >= 60) as aprobados,
                COUNT(DISTINCT nota.username_postulante) FILTER (WHERE nota.promedio < 60) as reprobados,
                ROUND(
                    (COUNT(DISTINCT nota.username_postulante) FILTER (WHERE nota.promedio >= 60) * 100.0)
                    / NULLIF(COUNT(DISTINCT nota.username_postulante), 0),
                    2
                ) as porcentaje_aprobacion
            ')
            ->whereNotNull('nota.promedio')
            ->groupBy('nota.id_materia', 'materia.nombre');

        if (! empty($filtros['buscar'])) {
            $buscar = mb_strtolower($filtros['buscar']);
            $query->where(function ($subquery) use ($buscar): void {
                $subquery
                    ->whereRaw('lower(nota.id_materia) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(materia.nombre) like ?', ["%{$buscar}%"]);
            });
        }

        if (! empty($filtros['grupo'])) {
            $query->where('nota.id_grupo', $filtros['grupo']);
        }
        if (! empty($filtros['materia'])) {
            $query->where('nota.id_materia', $filtros['materia']);
        }

        if (! empty($filtros['periodo'])) {
            $query->whereIn('nota.id_grupo', Grupo::query()
                ->select('codigo')
                ->where('id_periodo_academico', $filtros['periodo']));
        }

        $this->filtrarCarreraQueryBuilder($query, $filtros, 'nota.username_postulante');
        $this->filtrarDocenteNotasQueryBuilder($query, $filtros);

        $total = DB::query()->fromSub(clone $query, 'estadisticas')->count();
        $estadisticas = $query->orderByDesc('promedio')->limit($limite)->get();
        $filas = $estadisticas->map(fn (object $materia): array => [
            'codigo' => $materia->id_materia,
            'materia' => $materia->materia,
            'estudiantes' => (int) $materia->estudiantes,
            'promedio' => number_format((float) $materia->promedio, 2, '.', ''),
            'aprobados' => (int) $materia->aprobados,
            'reprobados' => (int) $materia->reprobados,
            'porcentaje_aprobacion' => number_format((float) ($materia->porcentaje_aprobacion ?? 0), 2, '.', '').'%',
        ])->values();

        return $this->respuesta(
            'estadisticas_materia',
            'Estadisticas academicas por materia',
            [
                'codigo' => 'Codigo',
                'materia' => 'Materia',
                'estudiantes' => 'Estudiantes',
                'promedio' => 'Promedio',
                'aprobados' => 'Aprobados',
                'reprobados' => 'Reprobados',
                'porcentaje_aprobacion' => '% aprobacion',
            ],
            $filas,
            $total,
            [
                'materias_evaluadas' => $total,
                'estudiantes_evaluados' => (int) $estadisticas->sum('estudiantes'),
                'promedio_materias' => round($estadisticas->avg('promedio') ?? 0, 2),
            ],
            $filtros,
        );
    }
    // hace el reporte de grupos habilitados
    private function reporteGruposHabilitados(array $filtros, int $limite): array
    {
        $query = Grupo::query()->where('estado', 'activo')->orderBy('codigo');

        if (! empty($filtros['buscar'])) {
            $buscar = mb_strtolower($filtros['buscar']);
            $query->where(function (Builder $subquery) use ($buscar): void {
                $subquery
                    ->whereRaw('lower(codigo) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(coalesce(descripcion, \'\')) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(coalesce(turno, \'\')) like ?', ["%{$buscar}%"]);
            });
        }

        if (! empty($filtros['grupo'])) {
            $query->where('codigo', $filtros['grupo']);
        }
        if (! empty($filtros['periodo'])) {
            $query->where('id_periodo_academico', $filtros['periodo']);
        }
        if (! empty($filtros['materia'])) {
            $query->whereIn('codigo', function ($subquery) use ($filtros): void {
                $subquery->select('horario.id_grupo')
                    ->from('academico.horario_grupo as horario')
                    ->where('horario.id_materia', $filtros['materia']);

                if (! empty($filtros['periodo'])) {
                    $subquery->where('horario.id_periodo_academico', $filtros['periodo']);
                }
            });
        }
        if (! empty($filtros['docente'])) {
            $query->whereIn('codigo', function ($subquery) use ($filtros): void {
                $subquery->select('horario.id_grupo')
                    ->from('academico.horario_grupo as horario')
                    ->where('horario.username_docente', $filtros['docente']);

                if (! empty($filtros['periodo'])) {
                    $subquery->where('horario.id_periodo_academico', $filtros['periodo']);
                }
            });
        }

        $total = (clone $query)->count();
        $grupos = $query->limit($limite)->get();
        $ocupaciones = PostulanteGrupo::whereIn('id_grupo', $grupos->pluck('codigo'))
            ->where('estado', 'inscrito')
            ->selectRaw('id_grupo, COUNT(*) as total')
            ->groupBy('id_grupo')
            ->pluck('total', 'id_grupo');
        $docentes = DB::table('academico.horario_grupo')
            ->whereIn('id_grupo', $grupos->pluck('codigo'))
            ->selectRaw('id_grupo, COUNT(DISTINCT username_docente) as total')
            ->groupBy('id_grupo')
            ->pluck('total', 'id_grupo');
        $periodos = PeriodoAcademico::whereIn('id', $grupos->pluck('id_periodo_academico')->filter())
            ->get()
            ->keyBy('id');

        $filas = $grupos->map(function (Grupo $grupo) use ($ocupaciones, $docentes, $periodos): array {
            $inscritos = (int) ($ocupaciones[$grupo->codigo] ?? 0);
            $cupo = (int) ($grupo->cupo_maximo ?? 70);
            $periodo = $periodos->get($grupo->id_periodo_academico);

            return [
                'grupo' => $grupo->codigo,
                'descripcion' => $grupo->descripcion ?? '-',
                'turno' => $grupo->turno ?? '-',
                'periodo' => $periodo?->nombre ?? 'Sin periodo',
                'cupo_maximo' => $cupo,
                'inscritos' => $inscritos,
                'cupos_disponibles' => max($cupo - $inscritos, 0),
                'docentes' => (int) ($docentes[$grupo->codigo] ?? 0),
                'estado' => $grupo->estado,
            ];
        })->values();

        return $this->respuesta(
            'grupos_habilitados',
            'Grupos habilitados del CUP',
            [
                'grupo' => 'Grupo',
                'descripcion' => 'Descripcion',
                'turno' => 'Turno',
                'periodo' => 'Periodo',
                'cupo_maximo' => 'Cupo maximo',
                'inscritos' => 'Inscritos',
                'cupos_disponibles' => 'Cupos disponibles',
                'docentes' => 'Docentes',
                'estado' => 'Estado',
            ],
            $filas,
            $total,
            [
                'grupos_habilitados' => $total,
                'capacidad_total' => $filas->sum('cupo_maximo'),
                'inscritos' => $filas->sum('inscritos'),
                'cupos_disponibles' => $filas->sum('cupos_disponibles'),
            ],
            $filtros,
        );
    }
    // hace el reporte de docentes de grupo
    private function reporteDocentesGrupo(array $filtros, int $limite): array
    {
        $query = DB::table('academico.horario_grupo as horario')
            ->join('academico.docente as docente', 'docente.username_docente', '=', 'horario.username_docente')
            ->join('academico.materia as materia', 'materia.id', '=', 'horario.id_materia')
            ->leftJoin('academico.dia as dia', 'dia.id', '=', 'horario.id_dia')
            ->selectRaw('
                horario.id_grupo,
                horario.username_docente,
                docente.nombre as docente,
                materia.id as id_materia,
                materia.nombre as materia,
                horario.turno,
                horario.estado,
                COUNT(*) as bloques,
                STRING_AGG(DISTINCT COALESCE(dia.nombre, horario.id_dia::text), \', \') as dias,
                MIN(horario.hora_inicio) as hora_inicio,
                MAX(horario.hora_fin) as hora_fin
            ')
            ->groupBy(
                'horario.id_grupo',
                'horario.username_docente',
                'docente.nombre',
                'materia.id',
                'materia.nombre',
                'horario.turno',
                'horario.estado',
            );

        if (! empty($filtros['buscar'])) {
            $buscar = mb_strtolower($filtros['buscar']);
            $query->where(function ($subquery) use ($buscar): void {
                $subquery
                    ->whereRaw('lower(docente.nombre) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(horario.username_docente) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(materia.nombre) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(horario.id_grupo) like ?', ["%{$buscar}%"]);
            });
        }

        if (! empty($filtros['grupo'])) {
            $query->where('horario.id_grupo', $filtros['grupo']);
        }
        if (! empty($filtros['docente'])) {
            $query->where('horario.username_docente', $filtros['docente']);
        }
        if (! empty($filtros['materia'])) {
            $query->where('horario.id_materia', $filtros['materia']);
        }
        if (! empty($filtros['periodo'])) {
            $query->where('horario.id_periodo_academico', $filtros['periodo']);
        }
        if (! empty($filtros['estado'])) {
            $query->where('horario.estado', $filtros['estado']);
        }

        $total = DB::query()->fromSub(clone $query, 'docentes')->count();
        $asignaciones = $query
            ->orderBy('horario.id_grupo')
            ->orderBy('docente.nombre')
            ->limit($limite)
            ->get();
        $filas = $asignaciones->map(fn (object $asignacion): array => [
            'grupo' => $asignacion->id_grupo,
            'docente' => $asignacion->docente,
            'usuario_docente' => $asignacion->username_docente,
            'materia' => $asignacion->materia,
            'turno' => $asignacion->turno,
            'dias' => $asignacion->dias,
            'horario' => substr((string) $asignacion->hora_inicio, 0, 5).' - '.substr((string) $asignacion->hora_fin, 0, 5),
            'bloques' => (int) $asignacion->bloques,
            'estado' => $asignacion->estado,
        ])->values();

        return $this->respuesta(
            'docentes_grupo',
            'Docentes asignados por grupo',
            [
                'grupo' => 'Grupo',
                'docente' => 'Docente',
                'usuario_docente' => 'Usuario',
                'materia' => 'Materia',
                'turno' => 'Turno',
                'dias' => 'Dias',
                'horario' => 'Horario',
                'bloques' => 'Bloques',
                'estado' => 'Estado',
            ],
            $filas,
            $total,
            [
                'asignaciones' => $total,
                'docentes' => $asignaciones->pluck('username_docente')->unique()->count(),
                'grupos' => $asignaciones->pluck('id_grupo')->unique()->count(),
                'materias' => $asignaciones->pluck('id_materia')->unique()->count(),
            ],
            $filtros,
        );
    }
    //  hace el reporte de rendimiento de grupos
    private function reporteRendimientoGrupos(array $filtros, int $limite): array
    {
        $promediosEstudiante = DB::table('academico.acta_nota as nota')
            ->selectRaw('
                nota.id_grupo,
                nota.username_postulante,
                ROUND(AVG(nota.promedio)::numeric, 2) as promedio_final
            ')
            ->whereNotNull('nota.promedio')
            ->groupBy('nota.id_grupo', 'nota.username_postulante');

        if (! empty($filtros['grupo'])) {
            $promediosEstudiante->where('nota.id_grupo', $filtros['grupo']);
        }
        if (! empty($filtros['materia'])) {
            $promediosEstudiante->where('nota.id_materia', $filtros['materia']);
        }

        $this->filtrarCarreraQueryBuilder($promediosEstudiante, $filtros, 'nota.username_postulante');
        $this->filtrarDocenteNotasQueryBuilder($promediosEstudiante, $filtros);

        $query = DB::query()
            ->fromSub($promediosEstudiante, 'resultado')
            ->join('academico.grupo as grupo', 'grupo.codigo', '=', 'resultado.id_grupo')
            ->selectRaw('
                resultado.id_grupo,
                grupo.turno,
                grupo.id_periodo_academico,
                COUNT(*) as evaluados,
                COUNT(*) FILTER (WHERE resultado.promedio_final >= 60) as aprobados,
                COUNT(*) FILTER (WHERE resultado.promedio_final < 60) as reprobados,
                ROUND(AVG(resultado.promedio_final)::numeric, 2) as promedio_grupo,
                ROUND(
                    (COUNT(*) FILTER (WHERE resultado.promedio_final >= 60) * 100.0)
                    / NULLIF(COUNT(*), 0),
                    2
                ) as porcentaje_aprobacion
            ')
            ->groupBy('resultado.id_grupo', 'grupo.turno', 'grupo.id_periodo_academico');

        if (! empty($filtros['periodo'])) {
            $query->where('grupo.id_periodo_academico', $filtros['periodo']);
        }
        if (! empty($filtros['buscar'])) {
            $buscar = mb_strtolower($filtros['buscar']);
            $query->whereRaw('lower(resultado.id_grupo) like ?', ["%{$buscar}%"]);
        }

        $total = DB::query()->fromSub(clone $query, 'rendimiento')->count();
        $resultados = $query
            ->orderByDesc('aprobados')
            ->orderByDesc('promedio_grupo')
            ->limit($limite)
            ->get();
        $periodos = PeriodoAcademico::whereIn('id', $resultados->pluck('id_periodo_academico')->filter())
            ->get()
            ->keyBy('id');

        $filas = $resultados->values()->map(function (object $grupo, int $indice) use ($periodos): array {
            $periodo = $periodos->get($grupo->id_periodo_academico);

            return [
                'posicion' => $indice + 1,
                'grupo' => $grupo->id_grupo,
                'turno' => $grupo->turno ?? '-',
                'periodo' => $periodo?->nombre ?? 'Sin periodo',
                'evaluados' => (int) $grupo->evaluados,
                'aprobados' => (int) $grupo->aprobados,
                'reprobados' => (int) $grupo->reprobados,
                'promedio_grupo' => number_format((float) $grupo->promedio_grupo, 2, '.', ''),
                'porcentaje_aprobacion' => number_format((float) ($grupo->porcentaje_aprobacion ?? 0), 2, '.', '').'%',
            ];
        });

        return $this->respuesta(
            'rendimiento_grupos',
            'Grupos con mayor cantidad de aprobados',
            [
                'posicion' => 'Ranking',
                'grupo' => 'Grupo',
                'turno' => 'Turno',
                'periodo' => 'Periodo',
                'evaluados' => 'Evaluados',
                'aprobados' => 'Aprobados',
                'reprobados' => 'Reprobados',
                'promedio_grupo' => 'Promedio del grupo',
                'porcentaje_aprobacion' => '% aprobacion',
            ],
            $filas,
            $total,
            [
                'grupos_evaluados' => $total,
                'aprobados' => $resultados->sum('aprobados'),
                'reprobados' => $resultados->sum('reprobados'),
                'mejor_grupo' => $filas->first()['grupo'] ?? 'Sin datos',
            ],
            $filtros,
        );
    }
    // filtra la carrera del query builder
    private function filtrarCarreraQueryBuilder($query, array $filtros, string $usernameColumn): void
    {
        if (empty($filtros['carrera'])) {
            return;
        }

        $query->whereExists(function ($subquery) use ($filtros, $usernameColumn): void {
            $subquery->selectRaw('1')
                ->from('academico.postulante_carrera as opcion_carrera')
                ->whereColumn('opcion_carrera.username_postulante', $usernameColumn)
                ->where('opcion_carrera.id_carrera', $filtros['carrera']);
        });
    }

    private function filtrarCarrera(Builder $query, array $filtros): void
    {
        if (empty($filtros['carrera'])) {
            return;
        }

        $query->whereIn(
            'username_postulante',
            PostulanteCarrera::query()
                ->select('username_postulante')
                ->where('id_carrera', $filtros['carrera']),
        );
    }

    private function filtrarDocentePostulante(Builder $query, array $filtros, string $usernameColumn = 'username_postulante'): void
    {
        if (empty($filtros['docente'])) {
            return;
        }

        $query->whereIn($usernameColumn, function ($subquery) use ($filtros): void {
            $subquery->select('inscripcion.username_postulante')
                ->from('academico.postulante_grupo as inscripcion')
                ->where('inscripcion.estado', 'inscrito')
                ->whereExists(function ($horario) use ($filtros): void {
                    $horario->selectRaw('1')
                        ->from('academico.horario_grupo as horario')
                        ->whereColumn('horario.id_grupo', 'inscripcion.id_grupo')
                        ->where('horario.username_docente', $filtros['docente']);

                    if (! empty($filtros['periodo'])) {
                        $horario->where('horario.id_periodo_academico', $filtros['periodo']);
                    }
                });

            if (! empty($filtros['periodo'])) {
                $subquery->where('inscripcion.id_periodo_academico', $filtros['periodo']);
            }
        });
    }
    // filtra el docente del query builder
    private function filtrarDocentePostulanteQueryBuilder($query, array $filtros, string $usernameColumn): void
    {
        if (empty($filtros['docente'])) {
            return;
        }

        $query->whereExists(function ($subquery) use ($filtros, $usernameColumn): void {
            $subquery->selectRaw('1')
                ->from('academico.postulante_grupo as inscripcion')
                ->whereColumn('inscripcion.username_postulante', $usernameColumn)
                ->where('inscripcion.estado', 'inscrito')
                ->whereExists(function ($horario) use ($filtros): void {
                    $horario->selectRaw('1')
                        ->from('academico.horario_grupo as horario')
                        ->whereColumn('horario.id_grupo', 'inscripcion.id_grupo')
                        ->where('horario.username_docente', $filtros['docente']);

                    if (! empty($filtros['periodo'])) {
                        $horario->where('horario.id_periodo_academico', $filtros['periodo']);
                    }
                });

            if (! empty($filtros['periodo'])) {
                $subquery->where('inscripcion.id_periodo_academico', $filtros['periodo']);
            }
        });
    }

    // filtra el docente del query builder
    private function filtrarDocenteNotas(Builder $query, array $filtros): void
    {
        if (empty($filtros['docente'])) {
            return;
        }

        $query->whereExists(function ($subquery) use ($filtros): void {
            $subquery->selectRaw('1')
                ->from('academico.horario_grupo as horario')
                ->whereRaw('horario.id_grupo = acta_nota.id_grupo')
                ->whereRaw('horario.id_materia = acta_nota.id_materia')
                ->where('horario.username_docente', $filtros['docente']);

            if (! empty($filtros['periodo'])) {
                $subquery->where('horario.id_periodo_academico', $filtros['periodo']);
            }
        });
    }

    // filtra el docente del query builder
    private function filtrarDocenteNotasQueryBuilder(
        $query,
        array $filtros,
        string $grupoColumn = 'nota.id_grupo',
        string $materiaColumn = 'nota.id_materia',
    ): void {
        if (empty($filtros['docente'])) {
            return;
        }

        $query->whereExists(function ($subquery) use ($filtros, $grupoColumn, $materiaColumn): void {
            $subquery->selectRaw('1')
                ->from('academico.horario_grupo as horario')
                ->whereColumn('horario.id_grupo', $grupoColumn)
                ->whereColumn('horario.id_materia', $materiaColumn)
                ->where('horario.username_docente', $filtros['docente']);

            if (! empty($filtros['periodo'])) {
                $subquery->where('horario.id_periodo_academico', $filtros['periodo']);
            }
        });
    }

    // filtra el estado del postulante
    private function filtrarEstadoPostulante(Builder $query, ?string $estado): void
    {
        if (! $estado) {
            return;
        }

        if ($estado === 'pagado') {
            $query->whereIn(
                'username_postulante',
                Pago::query()
                    ->select('username_postulante')
                    ->whereIn('estado', ['pagado', 'registrado']),
            );

            return;
        }

        if ($estado === 'validado') {
            $query->whereIn(
                'username_postulante',
                RequisitoPostulante::query()
                    ->select('username_postulante')
                    ->where('ci_entregado', true)
                    ->where('titulo_entregado', true)
                    ->where('libretas_entregadas', true),
            );

            return;
        }

        if ($estado === 'rechazado') {
            $query->where(function (Builder $subquery): void {
                $subquery
                    ->where('estado', 'rechazado')
                    ->orWhereIn(
                        'username_postulante',
                        Pago::query()
                            ->select('username_postulante')
                            ->where('estado', 'rechazado'),
                    );
            });

            return;
        }

        $query->where('estado', $estado);
    }
    // hace el filtro de grupo y periodo
    private function filtrarGrupoPeriodo(
        Builder $query,
        array $filtros,
        bool $grupoDirecto = false,
        bool $periodoDirectoPostulante = false,
    ): void
    {
        $grupos = PostulanteGrupo::query()
            ->select('username_postulante')
            ->where('estado', 'inscrito');

        if (! empty($filtros['grupo'])) {
            $grupos->where('id_grupo', $filtros['grupo']);
        }
        if (! empty($filtros['periodo'])) {
            $grupos->where('id_periodo_academico', $filtros['periodo']);
        }

        if ($periodoDirectoPostulante && ! empty($filtros['periodo'])) {
            $query->where('id_periodo_academico', $filtros['periodo']);
        }

        $filtrarPorInscripcion = (! $periodoDirectoPostulante && ! empty($filtros['periodo']))
            || (! $grupoDirecto && ! empty($filtros['grupo']));

        if ($filtrarPorInscripcion) {
            $query->whereIn('username_postulante', $grupos);
        }
    }

    // hace el filtro de fecha de pago
    private function filtrarFechaPago(Builder $query, array $filtros): void
    {
        if (empty($filtros['fecha_inicio']) && empty($filtros['fecha_fin'])) {
            return;
        }

        $pagos = Pago::query()->select('username_postulante');

        if (! empty($filtros['fecha_inicio'])) {
            $pagos->whereDate('fecha_pago', '>=', $filtros['fecha_inicio']);
        }
        if (! empty($filtros['fecha_fin'])) {
            $pagos->whereDate('fecha_pago', '<=', $filtros['fecha_fin']);
        }

        $query->whereIn('username_postulante', $pagos);
    }

    // hace el contexto de los postulantes
    private function contextoPostulantes(Collection $usernames): array
    {
        $carrerasPostulante = PostulanteCarrera::whereIn('username_postulante', $usernames)
            ->get()
            ->groupBy('username_postulante');
        $carreras = Carrera::whereIn('codigo', $carrerasPostulante->flatten()->pluck('id_carrera')->unique())
            ->pluck('nombre', 'codigo');
        $gruposPostulante = PostulanteGrupo::whereIn('username_postulante', $usernames)
            ->where('estado', 'inscrito')
            ->get()
            ->groupBy('username_postulante');
        $grupos = Grupo::whereIn('codigo', $gruposPostulante->flatten()->pluck('id_grupo')->unique())
            ->get()
            ->keyBy('codigo');
        $periodos = PeriodoAcademico::whereIn('id', $gruposPostulante->flatten()->pluck('id_periodo_academico')->filter()->unique())
            ->get()
            ->keyBy('id');
        $pagos = Pago::whereIn('username_postulante', $usernames)
            ->orderByDesc('id')
            ->get()
            ->unique('username_postulante')
            ->keyBy('username_postulante');
        $requisitos = RequisitoPostulante::whereIn('username_postulante', $usernames)
            ->get()
            ->keyBy('username_postulante');

        return compact('carrerasPostulante', 'carreras', 'gruposPostulante', 'grupos', 'periodos', 'pagos', 'requisitos');
    }

    private function nombresCarreras(string $username, array $contexto): string
    {
        return $contexto['carrerasPostulante']->get($username, collect())
            ->map(fn (PostulanteCarrera $item): string => $contexto['carreras']->get($item->id_carrera, $item->id_carrera))
            ->unique()
            ->join(' / ') ?: 'Sin carrera';
    }

    private function nombresGrupos(string $username, array $contexto): string
    {
        return $contexto['gruposPostulante']->get($username, collect())
            ->map(fn (PostulanteGrupo $item): string => $contexto['grupos']->get($item->id_grupo)?->codigo ?? $item->id_grupo)
            ->unique()
            ->join(' / ') ?: 'Sin grupo';
    }

    private function nombresPeriodos(string $username, array $contexto): string
    {
        return $contexto['gruposPostulante']->get($username, collect())
            ->map(function (PostulanteGrupo $item) use ($contexto): string {
                $periodo = $contexto['periodos']->get($item->id_periodo_academico);

                return $periodo
                    ? ($periodo->nombre ?: 'Periodo '.($periodo->semestre ?? '').'-'.($periodo->año ?? $periodo->anio ?? ''))
                    : 'Sin periodo';
            })
            ->unique()
            ->join(' / ') ?: 'Sin periodo';
    }

    private function estadoRequisitos(?RequisitoPostulante $requisito): string
    {
        if (! $requisito) {
            return 'pendiente';
        }

        return $requisito->ci_entregado && $requisito->titulo_entregado && $requisito->libretas_entregadas
            ? 'validado'
            : 'observado';
    }

    private function periodoActivoId(): ?int
    {
        return PeriodoAcademico::where('estado', 'activo')->orderByDesc('id')->value('id');
    }

    private function respuesta(
        string $tipo,
        string $titulo,
        array $columnas,
        Collection $filas,
        int $total,
        array $metricas,
        array $filtros,
    ): array {
        return [
            'caso_uso' => 'CU-34 Consultar reportes dinamicos',
            'tipo' => $tipo,
            'titulo' => $titulo,
            'columnas' => $columnas,
            'datos' => $filas->all(),
            'resumen' => [
                'total_resultados' => $total,
                'mostrados' => $filas->count(),
                ...$metricas,
            ],
            'filtros' => $filtros,
            'generado_en' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
