<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Pago;
use App\Models\Postulante;
use App\Models\PostulanteCarrera;
use App\Models\RequisitoPostulante;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $postulantes = Postulante::where('estado', '!=', 'pendiente_pago')->count();
        $preinscripciones = Postulante::where('estado', '!=', 'pendiente_pago')->count();
        $matriculasPagadas = Pago::whereIn('estado', ['pagado', 'registrado'])
            ->distinct('username_postulante')
            ->count('username_postulante');
        $admitidos = Postulante::whereIn('estado', ['habilitado', 'admitido'])->count();

        return response()->json([
            'metricas' => [
                'postulantes' => $postulantes,
                'preinscripciones' => $preinscripciones,
                'matriculas_pagadas' => $matriculasPagadas,
                'admitidos' => $admitidos,
            ],
            'preinscripciones_recientes' => $this->preinscripcionesRecientes(),
        ]);
    }

    private function preinscripcionesRecientes(): array
    {
        return Postulante::orderByDesc('username_postulante')
            ->where('estado', '!=', 'pendiente_pago')
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
