<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Carrera;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\PeriodoAcademico;
use App\Services\ReporteExcelService;
use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReporteController extends Controller
{
    public function __construct(
        private readonly ReporteService $reportes,
        private readonly ReporteExcelService $excel,
    ) {
    }
    // hace la obtencion de las opciones para los reportes
    public function opciones(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $esDocente = $this->usuarioEsDocente($request);
        $docentes = Docente::orderBy('nombre')
            ->when($esDocente, fn ($query) => $query->where('username_docente', $usuario?->username))
            ->get(['username_docente', 'nombre']);

        $grupos = Grupo::orderBy('codigo')
            ->when($esDocente, function ($query) use ($usuario): void {
                $query->whereIn('codigo', function ($subquery) use ($usuario): void {
                    $subquery->select('id_grupo')
                        ->from('academico.horario_grupo')
                        ->where('username_docente', $usuario?->username);
                });
            })
            ->get(['codigo', 'turno', 'id_periodo_academico']);
        $materias = Materia::orderBy('nombre')
            ->when($esDocente, function ($query) use ($usuario): void {
                $query->whereIn('id', function ($subquery) use ($usuario): void {
                    $subquery->select('id_materia')
                        ->from('academico.horario_grupo')
                        ->where('username_docente', $usuario?->username);
                });
            })
            ->get(['id', 'nombre']);

        return response()->json([
            'gemini_configurado' => (string) config('services.gemini.key') !== '',
            'alcance' => [
                'docente_forzado' => $esDocente ? $usuario?->username : null,
                'descripcion' => $esDocente
                    ? 'Los reportes se limitan automaticamente a tus grupos, materias y alumnos.'
                    : 'Los reportes pueden consultar el alcance completo permitido por el rol.',
            ],
            'tipos' => $this->tiposDisponibles($request),
            'periodos' => PeriodoAcademico::orderByDesc('id')
                ->get()
                ->map(fn (PeriodoAcademico $periodo): array => [
                    'id' => $periodo->id,
                    'nombre' => $periodo->nombre
                        ?: 'Periodo '.($periodo->semestre ?? '').'-'.($periodo->año ?? $periodo->anio ?? ''),
                ])
                ->values(),
            'carreras' => Carrera::orderBy('nombre')
                ->get(['codigo', 'nombre'])
                ->map(fn (Carrera $carrera): array => [
                    'codigo' => $carrera->codigo,
                    'nombre' => $carrera->nombre,
                ])
                ->values(),
            'grupos' => $grupos
                ->map(fn (Grupo $grupo): array => [
                    'codigo' => $grupo->codigo,
                    'turno' => $grupo->turno,
                    'periodo' => $grupo->id_periodo_academico,
                ])
                ->values(),
            'docentes' => $docentes
                ->map(fn (Docente $docente): array => [
                    'username' => $docente->username_docente,
                    'nombre' => $docente->nombre.' ('.$docente->username_docente.')',
                ])
                ->values(),
            'materias' => $materias
                ->map(fn (Materia $materia): array => [
                    'id' => $materia->id,
                    'nombre' => $materia->nombre.' ('.$materia->id.')',
                ])
                ->values(),
            'estados' => [
                'postulantes' => ['pendiente', 'pagado', 'validado', 'habilitado', 'admitido', 'rechazado'],
                'lista_admitidos' => [],
                'postulantes_aprobados' => [],
                'postulantes_reprobados' => [],
                'pagos' => ['pendiente', 'registrado', 'pagado', 'rechazado'],
                'calificaciones' => ['aprobado', 'reprobado'],
                'resultados_estudiantes' => ['aprobado', 'reprobado'],
                'estadisticas_materia' => [],
                'grupos_habilitados' => [],
                'docentes_grupo' => ['propuesto', 'confirmado'],
                'rendimiento_grupos' => [],
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $filtros = $this->validarFiltros($request);

        return response()->json($this->reportes->generar($filtros, $filtros['limite'] ?? 500));
    }

    public function pdf(Request $request)
    {
        $filtros = $this->validarFiltros($request);
        $reporte = $this->reportes->generar($filtros, 10000);
        $usuario = $request->user();
        $logo = public_path('assets/brand/ficct-escudo.png');

        Bitacora::registrar(
            'exportar_reporte_pdf',
            'reportes',
            'Exporto '.$reporte['titulo'].' en PDF.',
            ['tipo' => $reporte['tipo'], 'filtros' => $filtros, 'resultados' => $reporte['resumen']['total_resultados']],
            $request,
        );

        return Pdf::loadView('pdf.reporte-administrativo', [
            'reporte' => $reporte,
            'usuario' => $usuario,
            'logoPath' => file_exists($logo) ? $logo : null,
        ])
            ->setPaper('a4', 'landscape')
            ->download($this->nombreArchivo($reporte['tipo']).'.pdf');
    }

    public function excel(Request $request): BinaryFileResponse
    {
        $filtros = $this->validarFiltros($request);
        $reporte = $this->reportes->generar($filtros, 10000);
        $archivo = $this->excel->crear($reporte);

        Bitacora::registrar(
            'exportar_reporte_excel',
            'reportes',
            'Exporto '.$reporte['titulo'].' en Excel.',
            ['tipo' => $reporte['tipo'], 'filtros' => $filtros, 'resultados' => $reporte['resumen']['total_resultados']],
            $request,
        );

        return response()
            ->download(
                $archivo['ruta'],
                $this->nombreArchivo($reporte['tipo']).'.'.$archivo['extension'],
                [
                    'Content-Type' => $archivo['content_type'],
                    'X-Content-Type-Options' => 'nosniff',
                    'Cache-Control' => 'no-store, no-cache, must-revalidate',
                ],
            )
            ->deleteFileAfterSend(true);
    }

    private function validarFiltros(Request $request): array
    {
        $validated = $request->validate([
            'tipo' => ['required', Rule::in(ReporteService::TIPOS)],
            'buscar' => ['nullable', 'string', 'max:150'],
            'periodo' => ['nullable', 'integer'],
            'carrera' => ['nullable', 'string', 'max:50'],
            'estado' => ['nullable', 'string', 'max:50'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date'],
            'grupo' => ['nullable', 'string', 'max:100'],
            'docente' => ['nullable', 'string', 'max:500'],
            'materia' => ['nullable', 'string', 'max:100', Rule::exists('pgsql.academico.materia', 'id')],
            'limite' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        if (! empty($validated['fecha_inicio'])
            && ! empty($validated['fecha_fin'])
            && $validated['fecha_fin'] < $validated['fecha_inicio']) {
            throw ValidationException::withMessages([
                'fecha_fin' => ['La fecha final debe ser igual o posterior a la fecha inicial.'],
            ]);
        }

        $permitidos = collect($this->tiposDisponibles($request))->pluck('codigo')->all();
        if (! in_array($validated['tipo'], $permitidos, true)) {
            throw ValidationException::withMessages([
                'tipo' => ['No tienes permiso para consultar ese tipo de reporte.'],
            ]);
        }

        return $this->aplicarAlcanceDocente($validated, $request);
    }

    private function nombreArchivo(string $tipo): string
    {
        return 'reporte-'.$tipo.'-'.now()->format('Ymd-His');
    }

    private function aplicarAlcanceDocente(array $filtros, Request $request): array
    {
        if ($this->usuarioEsDocente($request)) {
            $filtros['docente'] = $request->user()?->username;
        }

        return $filtros;
    }

    private function usuarioEsDocente(Request $request): bool
    {
        $usuario = $request->user();

        return $usuario?->tipo === 'docente'
            || $usuario?->rol?->nombre === 'docente';
    }

    private function tiposDisponibles(Request $request): array
    {
        $tipos = [
            ['codigo' => 'postulantes', 'nombre' => 'Lista general de postulantes'],
            ['codigo' => 'lista_admitidos', 'nombre' => 'Lista oficial de admitidos'],
            ['codigo' => 'postulantes_aprobados', 'nombre' => 'Postulantes aprobados y promedios'],
            ['codigo' => 'postulantes_reprobados', 'nombre' => 'Postulantes reprobados y promedios'],
            ['codigo' => 'resultados_estudiantes', 'nombre' => 'Aprobados, reprobados y promedios'],
            ['codigo' => 'estadisticas_materia', 'nombre' => 'Estadisticas por materia'],
            ['codigo' => 'grupos_habilitados', 'nombre' => 'Grupos habilitados'],
            ['codigo' => 'docentes_grupo', 'nombre' => 'Docentes por grupo'],
            ['codigo' => 'rendimiento_grupos', 'nombre' => 'Rendimiento por grupo'],
            ['codigo' => 'pagos', 'nombre' => 'Pagos de matricula'],
            ['codigo' => 'calificaciones', 'nombre' => 'Detalle de calificaciones'],
        ];

        if (! $this->usuarioEsDocente($request)) {
            return $tipos;
        }

        $permitidosDocente = [
            'postulantes_aprobados',
            'postulantes_reprobados',
            'resultados_estudiantes',
            'estadisticas_materia',
            'grupos_habilitados',
            'rendimiento_grupos',
            'calificaciones',
        ];

        return array_values(array_filter(
            $tipos,
            fn (array $tipo): bool => in_array($tipo['codigo'], $permitidosDocente, true),
        ));
    }
}
