<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Carrera;
use App\Models\Grupo;
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

        $catalogos = $this->catalogos();
        $interpretacion = $this->gemini->interpretarReporte($validated['comando'], $catalogos);
        $interpretacion = $this->validarInterpretacion($interpretacion, $catalogos);
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

    private function catalogos(): array
    {
        return [
            'fecha_actual' => now()->format('Y-m-d'),
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
            'grupos' => Grupo::orderBy('codigo')
                ->get(['codigo'])
                ->map(fn (Grupo $grupo): array => ['codigo' => $grupo->codigo])
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
}
