<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Postulante;
use App\Models\RequisitoPostulante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CU-09: Habilitar postulante.
 *
 * Confirma la habilitacion definitiva del estudiante tras validar requisitos
 * fisicos y pago de matricula.
 */
class HabilitacionPostulanteController extends Controller
{
    public function show(string $username): JsonResponse
    {
        $postulante = Postulante::where('username_postulante', $username)->firstOrFail();

        $requisitos = $this->requisitos($username);
        $pago = $this->pago($username);

        return response()->json([
            'caso_uso' => 'CU-09 Habilitar postulante',
            'estado_postulante' => $postulante->estado,
            'puede_habilitarse' => $this->requisitosCompletos($requisitos) && $this->pagoValido($pago),
            'validaciones' => [
                'requisitos_fisicos' => $this->requisitosCompletos($requisitos) ? 'validado' : 'pendiente',
                'pago_matricula' => $this->pagoValido($pago) ? 'validado' : 'pendiente',
            ],
            'requisitos' => $requisitos,
            'pago' => $pago,
        ]);
    }

    public function store(Request $request, string $username): JsonResponse
    {
        $postulante = Postulante::where('username_postulante', $username)->firstOrFail();

        $validated = $request->validate([
            'observacion' => ['nullable', 'string'],
        ]);

        $requisitos = $this->requisitos($username);
        $pago = $this->pago($username);

        if (! $this->requisitosCompletos($requisitos)) {
            return response()->json([
                'caso_uso' => 'CU-09 Habilitar postulante',
                'message' => 'No se puede habilitar: faltan requisitos fisicos por validar.',
                'estado_postulante' => $postulante->estado,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $this->pagoValido($pago)) {
            return response()->json([
                'caso_uso' => 'CU-09 Habilitar postulante',
                'message' => 'No se puede habilitar: falta validar el pago de matricula.',
                'estado_postulante' => $postulante->estado,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $postulante->update([
            'estado' => 'habilitado',
        ]);

        return response()->json([
            'caso_uso' => 'CU-09 Habilitar postulante',
            'message' => 'Postulante habilitado correctamente.',
            'estado_postulante' => $postulante->estado,
            'observacion' => $validated['observacion'] ?? null,
            'postulante' => $postulante,
        ]);
    }

    private function requisitos(string $username): ?RequisitoPostulante
    {
        return RequisitoPostulante::where('username_postulante', $username)->first();
    }

    private function pago(string $username): ?Pago
    {
        return Pago::where('username_postulante', $username)
            ->latest('id')
            ->first();
    }

    private function requisitosCompletos(?RequisitoPostulante $requisitos): bool
    {
        return (bool) $requisitos
            && $requisitos->ci_entregado
            && $requisitos->titulo_entregado
            && $requisitos->libretas_entregadas;
    }

    private function pagoValido(?Pago $pago): bool
    {
        return (bool) $pago && in_array($pago->estado, ['pagado', 'registrado'], true);
    }
}
