<?php

namespace App\Http\Middleware;

use App\Models\Bitacora;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrarBitacoraWeb
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user()
            && $request->isMethod('GET')
            && $request->is('dashboard*')
            && $response->isSuccessful()) {
            Bitacora::registrar(
                'visita_pagina',
                $this->modulo($request->path()),
                'Ingreso a '.$request->path(),
                [],
                $request,
            );
        }

        return $response;
    }

    private function modulo(string $path): string
    {
        return match (true) {
            str_contains($path, 'usuarios') => 'usuarios',
            str_contains($path, 'roles-permisos') => 'roles_permisos',
            str_contains($path, 'bitacora') => 'bitacora',
            str_contains($path, 'password') => 'contrasenas',
            str_contains($path, 'preinscripciones') => 'preinscripciones',
            str_contains($path, 'requisitos') => 'requisitos_fisicos',
            str_contains($path, 'pagos') => 'pagos',
            str_contains($path, 'habilitacion') => 'habilitacion',
            str_contains($path, 'periodo-academico') => 'periodo_academico',
            str_contains($path, 'distribucion-grupos') => 'distribucion_grupos',
            str_contains($path, 'aulas') => 'aulas',
            str_contains($path, 'calificaciones') => 'calificaciones',
            str_contains($path, 'catalogos-academicos') => 'catalogos_academicos',
            str_contains($path, 'perfil') => 'perfil',
            default => 'dashboard',
        };
    }
}
