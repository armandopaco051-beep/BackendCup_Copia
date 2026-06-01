<?php

namespace App\Http\Controllers;

use App\Models\Postulante;
use App\Models\RequisitoPostulante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RequisitoPostulanteController extends Controller
{
    /**
     * CU-07: Validar requisitos fisicos.
     *
     * Registra la entrega de documentos obligatorios:
     * CI, titulo de bachiller y libretas.
     */
    public function store(Request $request, string $username): JsonResponse
    {
        Postulante::where('username_postulante', $username)->firstOrFail();

        $validated = $request->validate([
            'ci_entregado' => ['required', 'boolean'],
            'titulo_entregado' => ['required', 'boolean'],
            'libretas_entregadas' => ['required', 'boolean'],
            'observacion' => ['nullable', 'string'],
            'validado_por' => [
                'nullable',
                'string',
                Rule::exists('pgsql.seguridad.usuario', 'username'),
            ],
        ]);

        $validadoPor = $validated['validado_por'] ?? Auth::id();

        $requisitos = RequisitoPostulante::updateOrCreate(
            ['username_postulante' => $username],
            [
                'ci_entregado' => $validated['ci_entregado'],
                'titulo_entregado' => $validated['titulo_entregado'],
                'libretas_entregadas' => $validated['libretas_entregadas'],
                'observacion' => $validated['observacion'] ?? null,
                'validado_por' => $validadoPor,
                'fecha_validacion' => now(),
            ]
        );

        $estado = $this->documentosCompletos($requisitos)
            ? 'validado'
            : 'pendiente';

        return response()->json([
            'caso_uso' => 'CU-07 Validar requisitos fisicos',
            'message' => 'Requisitos fisicos registrados correctamente.',
            'estado' => $estado,
            'requisitos' => $requisitos,
        ]);
    }

    public function show(string $username): JsonResponse
    {
        Postulante::where('username_postulante', $username)->firstOrFail();

        $requisitos = RequisitoPostulante::where('username_postulante', $username)->first();

        return response()->json([
            'caso_uso' => 'CU-07 Validar requisitos fisicos',
            'estado' => $requisitos && $this->documentosCompletos($requisitos)
                ? 'validado'
                : 'pendiente',
            'requisitos' => $requisitos,
        ]);
    }

    private function documentosCompletos(RequisitoPostulante $requisitos): bool
    {
        return $requisitos->ci_entregado
            && $requisitos->titulo_entregado
            && $requisitos->libretas_entregadas;
    }
}
