<?php

namespace App\Http\Controllers;

use App\Models\AsignacionCarrera;
use App\Models\Carrera;
use App\Models\Postulante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsignacionCarreraController extends Controller
{
    private const NOTA_MINIMA = 60.00;

    public function index(): JsonResponse
    {
        $asignaciones = AsignacionCarrera::orderByDesc('promedio_final')
            ->orderByDesc('nota3_promedio')
            ->orderByDesc('nota2_promedio')
            ->orderByDesc('nota1_promedio')
            ->get();

        return response()->json([
            'caso_uso' => 'Asignar carrera segun cupo',
            'regla' => 'Promedio final >= 60.00, prioridad por promedio exacto y cupo disponible.',
            'resumen' => $this->resumenCupos($asignaciones),
            'asignaciones' => $asignaciones
                ->map(fn (AsignacionCarrera $asignacion): array => $this->formatAsignacion($asignacion))
                ->values(),
        ]);
    }

    public function generar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sobrescribir' => ['nullable', 'boolean'],
        ]);

        $existentes = AsignacionCarrera::exists();
        if ($existentes && ! ($validated['sobrescribir'] ?? false)) {
            throw ValidationException::withMessages([
                'asignaciones' => 'Ya existe una asignacion generada. Marca sobrescribir para recalcularla.',
            ]);
        }

        $carreras = Carrera::where('estado', 'habilitada')->orderBy('nombre')->get()->keyBy('codigo');
        $postulantes = $this->postulantesConPromedio();

        if ($postulantes->isEmpty()) {
            throw ValidationException::withMessages([
                'calificaciones' => 'No hay postulantes habilitados con calificaciones registradas.',
            ]);
        }

        $resultado = DB::transaction(function () use ($postulantes, $carreras): Collection {
            AsignacionCarrera::query()->delete();

            $ocupacion = Carrera::where('estado', 'habilitada')
                ->pluck('cupo_maximo', 'codigo')
                ->map(fn ($cupo): array => [
                    'cupo' => max((int) $cupo, 0),
                    'usado' => 0,
                ])
                ->all();

            $asignaciones = $postulantes
                ->map(function (object $postulante) use (&$ocupacion, $carreras): AsignacionCarrera {
                    $opciones = $this->opcionesCarrera($postulante->username_postulante);
                    $primera = $opciones[1] ?? null;
                    $segunda = $opciones[2] ?? null;
                    $promedio = round((float) $postulante->promedio_final, 2);

                    $payload = [
                        'username_postulante' => $postulante->username_postulante,
                        'primera_opcion' => $primera,
                        'segunda_opcion' => $segunda,
                        'promedio_final' => $promedio,
                        'nota3_promedio' => round((float) $postulante->nota3_promedio, 2),
                        'nota2_promedio' => round((float) $postulante->nota2_promedio, 2),
                        'nota1_promedio' => round((float) $postulante->nota1_promedio, 2),
                        'created_at' => now(),
                    ];

                    if ($promedio < self::NOTA_MINIMA) {
                        return AsignacionCarrera::create([
                            ...$payload,
                            'estado' => 'reprobado',
                            'motivo' => 'Promedio final menor a 60.00.',
                        ]);
                    }

                    if (! $primera && ! $segunda) {
                        return AsignacionCarrera::create([
                            ...$payload,
                            'estado' => 'sin_opcion',
                            'motivo' => 'El postulante no tiene carreras seleccionadas.',
                        ]);
                    }

                    foreach ([[1, $primera], [2, $segunda]] as [$opcion, $codigoCarrera]) {
                        if (! $codigoCarrera || ! $carreras->has($codigoCarrera)) {
                            continue;
                        }

                        $cupo = $ocupacion[$codigoCarrera]['cupo'] ?? 0;
                        $usado = $ocupacion[$codigoCarrera]['usado'] ?? 0;

                        if ($cupo > 0 && $usado < $cupo) {
                            $ocupacion[$codigoCarrera]['usado']++;

                            return AsignacionCarrera::create([
                                ...$payload,
                                'id_carrera' => $codigoCarrera,
                                'opcion_asignada' => $opcion,
                                'estado' => 'asignado',
                                'motivo' => $opcion === 1
                                    ? 'Asignado a primera opcion por cupo disponible.'
                                    : 'Primera opcion sin cupo; asignado a segunda opcion.',
                            ]);
                        }
                    }

                    return AsignacionCarrera::create([
                        ...$payload,
                        'estado' => 'lista_espera',
                        'motivo' => 'Promedio aprobado, pero sin cupos disponibles en sus opciones.',
                    ]);
                });

            $evaluados = $asignaciones->pluck('username_postulante');
            $admitidos = $asignaciones
                ->where('estado', 'asignado')
                ->pluck('username_postulante');

            Postulante::whereIn('username_postulante', $evaluados)
                ->where('estado', 'admitido')
                ->update(['estado' => 'habilitado']);
            Postulante::whereIn('username_postulante', $admitidos)
                ->update(['estado' => 'admitido']);

            return $asignaciones;
        });

        return response()->json([
            'caso_uso' => 'Asignar carrera segun cupo',
            'message' => 'Asignacion de carreras generada correctamente.',
            'nota_minima' => self::NOTA_MINIMA,
            'resumen' => $this->resumenCupos($resultado),
            'asignaciones' => $resultado
                ->map(fn (AsignacionCarrera $asignacion): array => $this->formatAsignacion($asignacion))
                ->values(),
        ], 201);
    }

    private function postulantesConPromedio(): Collection
    {
        $promedios = DB::table('academico.acta_nota')
            ->select(
                'username_postulante',
                DB::raw('ROUND(AVG(promedio)::numeric, 2) as promedio_final'),
                DB::raw('ROUND(AVG(nota3)::numeric, 2) as nota3_promedio'),
                DB::raw('ROUND(AVG(nota2)::numeric, 2) as nota2_promedio'),
                DB::raw('ROUND(AVG(nota1)::numeric, 2) as nota1_promedio')
            )
            ->groupBy('username_postulante');

        return DB::table('academico.postulante as postulante')
            ->joinSub($promedios, 'promedios', function ($join): void {
                $join->on('promedios.username_postulante', '=', 'postulante.username_postulante');
            })
            ->whereIn('postulante.estado', ['habilitado', 'admitido'])
            ->select(
                'postulante.username_postulante',
                'postulante.ci',
                'postulante.nombre',
                'postulante.estado',
                'promedios.promedio_final',
                'promedios.nota3_promedio',
                'promedios.nota2_promedio',
                'promedios.nota1_promedio'
            )
            ->orderByDesc('promedios.promedio_final')
            ->orderByDesc('promedios.nota3_promedio')
            ->orderByDesc('promedios.nota2_promedio')
            ->orderByDesc('promedios.nota1_promedio')
            ->orderBy('postulante.username_postulante')
            ->get();
    }

    private function opcionesCarrera(string $username): array
    {
        return DB::table('academico.postulante_carrera')
            ->where('username_postulante', $username)
            ->get()
            ->mapWithKeys(function (object $registro): array {
                $descripcion = mb_strtolower((string) $registro->descripcion);
                $opcion = str_contains($descripcion, 'segunda') ? 2 : 1;

                return [$opcion => $registro->id_carrera];
            })
            ->all();
    }

    private function resumenCupos(Collection $asignaciones): array
    {
        $asignadosPorCarrera = $asignaciones
            ->where('estado', 'asignado')
            ->groupBy('id_carrera')
            ->map(fn (Collection $items): int => $items->count());

        $carreras = Carrera::orderBy('nombre')->get();

        return [
            'total_evaluados' => $asignaciones->count(),
            'asignados' => $asignaciones->where('estado', 'asignado')->count(),
            'lista_espera' => $asignaciones->where('estado', 'lista_espera')->count(),
            'reprobados' => $asignaciones->where('estado', 'reprobado')->count(),
            'sin_opcion' => $asignaciones->where('estado', 'sin_opcion')->count(),
            'carreras' => $carreras
                ->map(function (Carrera $carrera) use ($asignadosPorCarrera): array {
                    $cupo = (int) ($carrera->cupo_maximo ?? 0);
                    $asignados = (int) ($asignadosPorCarrera[$carrera->codigo] ?? 0);

                    return [
                        'codigo' => $carrera->codigo,
                        'nombre' => $carrera->nombre,
                        'cupo_maximo' => $cupo,
                        'asignados' => $asignados,
                        'disponibles' => max($cupo - $asignados, 0),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function formatAsignacion(AsignacionCarrera $asignacion): array
    {
        $postulante = Postulante::where('username_postulante', $asignacion->username_postulante)->first();
        $carreras = Carrera::whereIn('codigo', array_filter([
            $asignacion->id_carrera,
            $asignacion->primera_opcion,
            $asignacion->segunda_opcion,
        ]))->get()->keyBy('codigo');

        return [
            'id' => $asignacion->id,
            'postulante' => [
                'username' => $asignacion->username_postulante,
                'nombre' => $postulante?->nombre ?? $asignacion->username_postulante,
                'ci' => $postulante?->ci,
            ],
            'primera_opcion' => $this->formatCarrera($asignacion->primera_opcion, $carreras),
            'segunda_opcion' => $this->formatCarrera($asignacion->segunda_opcion, $carreras),
            'carrera_asignada' => $this->formatCarrera($asignacion->id_carrera, $carreras),
            'promedio_final' => $asignacion->promedio_final,
            'nota3_promedio' => $asignacion->nota3_promedio,
            'nota2_promedio' => $asignacion->nota2_promedio,
            'nota1_promedio' => $asignacion->nota1_promedio,
            'opcion_asignada' => $asignacion->opcion_asignada,
            'estado' => $asignacion->estado,
            'motivo' => $asignacion->motivo,
        ];
    }

    private function formatCarrera(?string $codigo, Collection $carreras): ?array
    {
        if (! $codigo) {
            return null;
        }

        return [
            'codigo' => $codigo,
            'nombre' => $carreras[$codigo]->nombre ?? $codigo,
        ];
    }
}
