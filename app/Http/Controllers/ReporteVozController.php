<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Carrera;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\PeriodoAcademico;
use App\Services\GeminiService;
use App\Services\ReporteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReporteVozController extends Controller
{
    public function __construct(
        private readonly GeminiService $gemini,
        private readonly ReporteService $reportes,
    ) {
    }

    public function procesar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'comando' => ['required', 'string', 'min:5', 'max:600'],
        ]);

        $catalogos = $this->catalogos($request);
        $interpretacion = $this->gemini->interpretarReporte($validated['comando'], $catalogos);
        $interpretacion = $this->validarInterpretacion($interpretacion, $catalogos);
        $interpretacion['filtros'] = $this->aplicarAlcanceDocente($interpretacion['filtros'], $request);
        $filtros = [
            'tipo' => $interpretacion['tipo'],
            ...$interpretacion['filtros'],
            'limite' => 500,
        ];
        $reporte = $this->reportes->generar($filtros, 500);

        Bitacora::registrar(
            'consultar_reporte_voz_ia',
            'reportes',
            'Interpreto un comando de voz para generar un reporte.',
            [
                'comando' => $validated['comando'],
                'interpretacion' => $interpretacion,
                'resultados' => $reporte['resumen']['total_resultados'],
            ],
            $request,
        );

        return response()->json([
            'caso_uso' => 'CU-35 Generar reportes mediante comando de voz con IA',
            'message' => $interpretacion['respuesta'],
            'interpretacion' => $interpretacion,
            'reporte' => $reporte,
            'descarga' => $interpretacion['formato'] === 'pantalla'
                ? null
                : '/api/reportes/'.$interpretacion['formato'].'?'.http_build_query($filtros),
        ]);
    }

    private function validarInterpretacion(array $interpretacion, array $catalogos): array
    {
        $validator = Validator::make($interpretacion, [
            'accion' => ['required', Rule::in(['consultar', 'exportar'])],
            'tipo' => ['required', Rule::in(ReporteService::TIPOS)],
            'formato' => ['required', Rule::in(['pantalla', 'pdf', 'excel'])],
            'respuesta' => ['required', 'string', 'max:300'],
            'filtros' => ['present', 'array'],
            'filtros.buscar' => ['nullable', 'string', 'max:150'],
            'filtros.periodo' => ['nullable', 'integer'],
            'filtros.carrera' => ['nullable', 'string', 'max:50'],
            'filtros.estado' => ['nullable', 'string', 'max:50'],
            'filtros.fecha_inicio' => ['nullable', 'date_format:Y-m-d'],
            'filtros.fecha_fin' => ['nullable', 'date_format:Y-m-d'],
            'filtros.grupo' => ['nullable', 'string', 'max:100'],
            'filtros.docente' => ['nullable', 'string', 'max:500'],
            'filtros.materia' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'gemini' => ['Gemini interpreto el comando con filtros no validos. Intenta expresarlo de otra forma.'],
            ]);
        }

        $data = $validator->validated();
        $filtros = array_filter(
            $data['filtros'] ?? [],
            fn ($value): bool => $value !== null && $value !== '',
        );

        $this->validarCatalogo($filtros, 'periodo', collect($catalogos['periodos'])->pluck('id')->all());
        $this->validarCatalogo($filtros, 'carrera', collect($catalogos['carreras'])->pluck('codigo')->all());
        $this->validarCatalogo($filtros, 'grupo', collect($catalogos['grupos'])->pluck('codigo')->all());
        $this->validarCatalogo($filtros, 'docente', collect($catalogos['docentes'])->pluck('username')->all());
        $this->validarCatalogo($filtros, 'materia', collect($catalogos['materias'])->pluck('id')->all());
        $this->validarCatalogo(['tipo' => $data['tipo']], 'tipo', collect($catalogos['tipos'])->pluck('codigo')->all());

        $estados = $catalogos['estados'][$data['tipo']] ?? [];
        $this->validarCatalogo($filtros, 'estado', $estados);

        if (! empty($filtros['fecha_inicio'])
            && ! empty($filtros['fecha_fin'])
            && $filtros['fecha_fin'] < $filtros['fecha_inicio']) {
            throw ValidationException::withMessages([
                'gemini' => ['Gemini interpreto un rango de fechas invalido.'],
            ]);
        }

        if ($data['formato'] !== 'pantalla') {
            $data['accion'] = 'exportar';
        }

        return [
            ...$data,
            'filtros' => $filtros,
        ];
    }
    private function validarCatalogo(array $filtros, string $campo, array $permitidos): void
    {
        if (isset($filtros[$campo]) && ! in_array($filtros[$campo], $permitidos, false)) {
            throw ValidationException::withMessages([
                'gemini' => ["Gemini selecciono un valor no disponible para {$campo}."],
            ]);
        }
    }

    private function catalogos(Request $request): array
    {
        $usuario = $request->user();
        $esDocente = $this->usuarioEsDocente($request);
        $grupos = Grupo::orderBy('codigo')
            ->when($esDocente, function ($query) use ($usuario): void {
                $query->whereIn('codigo', function ($subquery) use ($usuario): void {
                    $subquery->select('id_grupo')
                        ->from('academico.horario_grupo')
                        ->where('username_docente', $usuario?->username);
                });
            })
            ->get(['codigo']);
        $docentes = Docente::orderBy('nombre')
            ->when($esDocente, fn ($query) => $query->where('username_docente', $usuario?->username))
            ->get(['username_docente', 'nombre']);
        $materias = Materia::orderBy('nombre')
            ->when($esDocente, function ($query) use ($usuario): void {
                $query->whereIn('id', function ($subquery) use ($usuario): void {
                    $subquery->select('id_materia')
                        ->from('academico.horario_grupo')
                        ->where('username_docente', $usuario?->username);
                });
            })
            ->get(['id', 'nombre']);

        return [
            'fecha_actual' => now()->format('Y-m-d'),
            'tipos' => $this->tiposDisponibles($request),
            'periodos' => PeriodoAcademico::orderByDesc('id')
                ->get()
                ->map(fn (PeriodoAcademico $periodo): array => [
                    'id' => $periodo->id,
                    'nombre' => $periodo->nombre
                        ?: 'Periodo '.($periodo->semestre ?? '').'-'.($periodo->anio ?? ''),
                ])
                ->values()
                ->all(),
            'carreras' => Carrera::orderBy('nombre')
                ->get(['codigo', 'nombre'])
                ->map(fn (Carrera $carrera): array => [
                    'codigo' => $carrera->codigo,
                    'nombre' => $carrera->nombre,
                ])
                ->values()
                ->all(),
            'grupos' => $grupos
                ->map(fn (Grupo $grupo): array => ['codigo' => $grupo->codigo])
                ->values()
                ->all(),
            'docentes' => $docentes
                ->map(fn (Docente $docente): array => [
                    'username' => $docente->username_docente,
                    'nombre' => $docente->nombre,
                ])
                ->values()
                ->all(),
            'materias' => $materias
                ->map(fn (Materia $materia): array => [
                    'id' => $materia->id,
                    'nombre' => $materia->nombre,
                ])
                ->values()
                ->all(),
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
        ];
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
