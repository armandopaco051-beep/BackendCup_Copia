<?php

namespace App\Http\Controllers;

use App\Models\ActaNota;
use App\Models\Bitacora;
use App\Models\PonderacionNota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PonderacionNotaController extends Controller
{
    public function index(): JsonResponse
    {
        $activa = PonderacionNota::activa();

        return response()->json([
            'caso_uso' => 'CU-31 Configurar ponderaciones de notas',
            'activa' => $this->formatPonderacion($activa),
            'ponderaciones' => PonderacionNota::orderByDesc('id')
                ->limit(20)
                ->get()
                ->map(fn (PonderacionNota $ponderacion): array => $this->formatPonderacion($ponderacion))
                ->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['nullable', 'string', 'max:100'],
            'nota1_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'nota2_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'nota3_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'recalcular' => ['nullable', 'boolean'],
        ]);

        $this->validarSuma($validated);

        $ponderacion = DB::transaction(function () use ($validated): PonderacionNota {
            PonderacionNota::where('estado', 'activa')->update(['estado' => 'inactiva']);

            return PonderacionNota::create([
                'nombre' => $validated['nombre'] ?? 'Ponderacion CUP',
                'nota1_porcentaje' => $validated['nota1_porcentaje'],
                'nota2_porcentaje' => $validated['nota2_porcentaje'],
                'nota3_porcentaje' => $validated['nota3_porcentaje'],
                'estado' => 'activa',
                'created_at' => now(),
            ]);
        });

        $recalculadas = ($validated['recalcular'] ?? false)
            ? $this->recalcularCalificaciones($ponderacion)
            : 0;

        Bitacora::registrar(
            'configurar_ponderaciones',
            'academico',
            "Ponderaciones de notas configuradas: {$ponderacion->nota1_porcentaje}/{$ponderacion->nota2_porcentaje}/{$ponderacion->nota3_porcentaje}.",
            [
                'caso_uso' => 'CU-31 Configurar ponderaciones de notas',
                'ponderacion_id' => $ponderacion->id,
                'nota1_porcentaje' => $ponderacion->nota1_porcentaje,
                'nota2_porcentaje' => $ponderacion->nota2_porcentaje,
                'nota3_porcentaje' => $ponderacion->nota3_porcentaje,
                'calificaciones_recalculadas' => $recalculadas,
            ],
            $request,
        );

        return response()->json([
            'message' => 'Ponderacion configurada correctamente.',
            'ponderacion' => $this->formatPonderacion($ponderacion),
            'calificaciones_recalculadas' => $recalculadas,
        ], 201);
    }

    public function recalcular(Request $request): JsonResponse
    {
        $ponderacion = PonderacionNota::activa();
        $total = $this->recalcularCalificaciones($ponderacion);

        Bitacora::registrar(
            'recalcular_promedios',
            'academico',
            "Promedios recalculados con ponderacion activa {$ponderacion->id}.",
            [
                'caso_uso' => 'CU-31 Configurar ponderaciones de notas',
                'ponderacion_id' => $ponderacion->id,
                'calificaciones_recalculadas' => $total,
            ],
            $request,
        );

        return response()->json([
            'message' => 'Promedios recalculados correctamente.',
            'calificaciones_recalculadas' => $total,
            'ponderacion' => $this->formatPonderacion($ponderacion),
        ]);
    }

    private function validarSuma(array $data): void
    {
        $total = round(
            (float) $data['nota1_porcentaje']
            + (float) $data['nota2_porcentaje']
            + (float) $data['nota3_porcentaje'],
            2,
        );

        if ($total !== 100.0) {
            throw ValidationException::withMessages([
                'ponderacion' => ["La suma de ponderaciones debe ser exactamente 100%. Actualmente suma {$total}%."],
            ]);
        }
    }

    private function recalcularCalificaciones(PonderacionNota $ponderacion): int
    {
        $calificaciones = ActaNota::all();

        foreach ($calificaciones as $calificacion) {
            $calificacion->update([
                'promedio' => round(
                    ((int) $calificacion->nota1 * ($ponderacion->nota1_porcentaje / 100))
                    + ((int) $calificacion->nota2 * ($ponderacion->nota2_porcentaje / 100))
                    + ((int) $calificacion->nota3 * ($ponderacion->nota3_porcentaje / 100)),
                    2,
                ),
            ]);
        }

        return $calificaciones->count();
    }

    private function formatPonderacion(PonderacionNota $ponderacion): array
    {
        return [
            'id' => $ponderacion->id,
            'nombre' => $ponderacion->nombre,
            'nota1_porcentaje' => $ponderacion->nota1_porcentaje,
            'nota2_porcentaje' => $ponderacion->nota2_porcentaje,
            'nota3_porcentaje' => $ponderacion->nota3_porcentaje,
            'total' => round($ponderacion->nota1_porcentaje + $ponderacion->nota2_porcentaje + $ponderacion->nota3_porcentaje, 2),
            'estado' => $ponderacion->estado,
            'created_at' => $ponderacion->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
