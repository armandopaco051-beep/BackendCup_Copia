<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BitacoraController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->autorizarAdministrador($request);

        $validated = $request->validate([
            'buscar' => ['nullable', 'string', 'max:200'],
            'modulo' => ['nullable', 'string', 'max:120'],
            'accion' => ['nullable', 'string', 'max:120'],
            'limite' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $query = Bitacora::query()->orderByDesc('id');

        if (! empty($validated['buscar'])) {
            $buscar = mb_strtolower($validated['buscar']);

            $query->where(function ($subquery) use ($buscar): void {
                $subquery
                    ->whereRaw('lower(username) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(accion) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(modulo) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(descripcion) like ?', ["%{$buscar}%"])
                    ->orWhereRaw('lower(ruta) like ?', ["%{$buscar}%"]);
            });
        }

        if (! empty($validated['modulo'])) {
            $query->where('modulo', $validated['modulo']);
        }

        if (! empty($validated['accion'])) {
            $query->where('accion', $validated['accion']);
        }

        $limite = $validated['limite'] ?? 80;
        $registros = $query->limit($limite)->get();

        return response()->json([
            'bitacora' => $registros->map(fn (Bitacora $registro): array => $this->formatear($registro))->values(),
            'resumen' => [
                'total_mostrado' => $registros->count(),
                'limite' => $limite,
            ],
        ]);
    }

    public function movimiento(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'accion' => ['required', 'string', 'max:120'],
            'modulo' => ['nullable', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string'],
            'datos' => ['nullable', 'array'],
        ]);

        Bitacora::registrar(
            $validated['accion'],
            $validated['modulo'] ?? null,
            $validated['descripcion'] ?? null,
            $validated['datos'] ?? [],
            $request,
        );

        return response()->json([
            'message' => 'Movimiento registrado.',
        ], Response::HTTP_CREATED);
    }

    private function autorizarAdministrador(Request $request): void
    {
        /** @var Usuario|null $usuario */
        $usuario = $request->user();
        $usuario?->loadMissing('rol');

        abort_unless($usuario && $usuario->rol?->nombre === 'administrador', Response::HTTP_FORBIDDEN);
    }

    private function formatear(Bitacora $registro): array
    {
        return [
            'id' => $registro->id,
            'username' => $registro->username,
            'rol' => $registro->rol,
            'tipo_usuario' => $registro->tipo_usuario,
            'accion' => $registro->accion,
            'modulo' => $registro->modulo,
            'metodo' => $registro->metodo,
            'ruta' => $registro->ruta,
            'descripcion' => $registro->descripcion,
            'ip' => $registro->ip,
            'datos' => $registro->datos,
            'created_at' => $registro->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
