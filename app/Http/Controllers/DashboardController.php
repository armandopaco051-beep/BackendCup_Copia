<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Grupo;
use App\Models\Pago;
use App\Models\PeriodoAcademico;
use App\Models\Postulante;
use App\Models\PostulanteCarrera;
use App\Models\RequisitoPostulante;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const NOTA_MINIMA_APROBACION = 60;

    public function index(): JsonResponse
    {
        $promedios = DB::table('academico.acta_nota')
            ->whereNotNull('promedio')
            ->select(
                'username_postulante',
                DB::raw('AVG(promedio) as promedio_final'),
            )
            ->groupBy('username_postulante');

        $aprobados = DB::query()
            ->fromSub(clone $promedios, 'promedios')
            ->where('promedio_final', '>=', self::NOTA_MINIMA_APROBACION)
            ->count();

        $reprobados = DB::query()
            ->fromSub(clone $promedios, 'promedios')
            ->where('promedio_final', '<', self::NOTA_MINIMA_APROBACION)
            ->count();

        $periodo = PeriodoAcademico::where('estado', 'activo')->orderByDesc('id')->first()
            ?? PeriodoAcademico::orderByDesc('id')->first();
        $periodoId = $periodo?->id;

        if ($periodoId) {
            $promedios->whereIn(
                'username_postulante',
                Postulante::query()
                    ->select('username_postulante')
                    ->where('id_periodo_academico', $periodoId),
            );
            $aprobados = DB::query()
                ->fromSub(clone $promedios, 'promedios')
                ->where('promedio_final', '>=', self::NOTA_MINIMA_APROBACION)
                ->count();
            $reprobados = DB::query()
                ->fromSub(clone $promedios, 'promedios')
                ->where('promedio_final', '<', self::NOTA_MINIMA_APROBACION)
                ->count();
        }

        return response()->json([
            'metricas' => [
                'inscritos' => Postulante::where('estado', '!=', 'pendiente_pago')
                    ->when($periodoId, fn ($query) => $query->where('id_periodo_academico', $periodoId))
                    ->count(),
                'aprobados' => $aprobados,
                'reprobados' => $reprobados,
                'grupos_habilitados' => Grupo::where('estado', 'activo')
                    ->when($periodoId, fn ($query) => $query->where('id_periodo_academico', $periodoId))
                    ->count(),
            ],
            'criterios' => [
                'nota_minima_aprobacion' => self::NOTA_MINIMA_APROBACION,
                'inscritos' => 'Preinscripciones confirmadas y persistidas en el sistema.',
                'resultados' => 'Promedio general por estudiante de todas sus materias registradas.',
                'grupos' => 'Grupos con estado activo.',
            ],
            'periodo' => $periodo ? [
                'id' => $periodo->id,
                'nombre' => $periodo->nombre
                    ?: 'Periodo '.($periodo->semestre ?? '').'-'.($periodo->{'año'} ?? $periodo->anio ?? ''),
            ] : null,
            'preinscripciones_recientes' => $this->preinscripcionesRecientes($periodoId),
        ]);
    }

    private function preinscripcionesRecientes(?int $periodoId): array
    {
        return Postulante::orderByDesc('username_postulante')
            ->where('estado', '!=', 'pendiente_pago')
            ->when($periodoId, fn ($query) => $query->where('id_periodo_academico', $periodoId))
            ->limit(4)
            ->get()
            ->map(fn (Postulante $postulante): array => $this->formatearPostulante($postulante))
            ->values()
            ->all();
    }

    private function formatearPostulante(Postulante $postulante): array
    {
        $postulanteCarrera = PostulanteCarrera::where('username_postulante', $postulante->username_postulante)->first();
        $carrera = $postulanteCarrera
            ? Carrera::where('codigo', $postulanteCarrera->id_carrera)->first()
            : null;
        $pago = Pago::where('username_postulante', $postulante->username_postulante)
            ->latest('id')
            ->first();
        $requisitos = RequisitoPostulante::where('username_postulante', $postulante->username_postulante)->first();

        return [
            'username' => $postulante->username_postulante,
            'nombre' => $postulante->nombre,
            'codigo_preinscripcion' => strtoupper($postulante->username_postulante),
            'carrera' => $carrera?->nombre ?? $postulanteCarrera?->descripcion ?? 'Sin carrera asignada',
            'estado' => $this->estadoResumen($postulante, $pago, $requisitos),
        ];
    }

    private function estadoResumen(Postulante $postulante, ?Pago $pago, ?RequisitoPostulante $requisitos): array
    {
        if (in_array($postulante->estado, ['habilitado', 'admitido'], true)) {
            return [
                'label' => 'Admitido',
                'tipo' => 'admitido',
            ];
        }

        if ($pago && in_array($pago->estado, ['pagado', 'registrado'], true)) {
            return [
                'label' => 'Pagado',
                'tipo' => 'pagado',
            ];
        }

        if ($requisitos
            && $requisitos->ci_entregado
            && $requisitos->titulo_entregado
            && $requisitos->libretas_entregadas) {
            return [
                'label' => 'Validado',
                'tipo' => 'validado',
            ];
        }

        return [
            'label' => 'Pendiente',
            'tipo' => 'pendiente',
        ];
    }
}
