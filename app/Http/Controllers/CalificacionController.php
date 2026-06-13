<?php

namespace App\Http\Controllers;

use App\Models\ActaNota;
use App\Models\DocenteGrupo;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Postulante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CalificacionController extends Controller
{
    public function index(): JsonResponse
    {
        $query = ActaNota::orderByDesc('id');

        if ($this->usuarioActualEsDocente()) {
            $query->whereIn('id_grupo', $this->codigosGruposDelDocente());
        }

        return response()->json([
            'calificaciones' => $query
                ->get()
                ->map(fn (ActaNota $calificacion): array => $this->formatCalificacion($calificacion))
                ->values(),
        ]);
    }

    public function opciones(): JsonResponse
    {
        $grupos = $this->gruposDisponiblesParaUsuario();
        $codigosGrupos = $grupos->pluck('codigo')->all();

        return response()->json([
            'postulantes' => $this->postulantesPorGrupos($codigosGrupos),
            'grupos' => $grupos
                ->map(fn (Grupo $grupo): array => [
                    'codigo' => $grupo->codigo,
                    'descripcion' => $grupo->descripcion,
                    'turno' => $grupo->turno ?? null,
                ])
                ->values(),
            'materias' => $this->materiasHabilitadas()
                ->map(fn (Materia $materia): array => [
                    'id' => $materia->id,
                    'nombre' => $materia->nombre,
                ])
                ->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $this->validarDocentePuedeCalificarGrupo($validated['id_grupo']);
        $this->validarDuplicado($validated);
        $validated['promedio'] = $this->promedio($validated);

        $calificacion = ActaNota::create($validated);

        return response()->json([
            'caso_uso' => 'Registrar calificaciones',
            'message' => 'Calificacion registrada correctamente.',
            'calificacion' => $this->formatCalificacion($calificacion),
        ], 201);
    }

    public function show(ActaNota $calificacion): JsonResponse
    {
        return response()->json([
            'calificacion' => $this->formatCalificacion($calificacion),
        ]);
    }

    public function update(Request $request, ActaNota $calificacion): JsonResponse
    {
        $validated = $request->validate($this->rules(true));
        $payload = array_merge($calificacion->only([
            'username_postulante',
            'id_grupo',
            'id_materia',
            'nota1',
            'nota2',
            'nota3',
            'descripcion',
        ]), $validated);

        $this->validarDocentePuedeCalificarGrupo($payload['id_grupo']);
        $this->validarDuplicado($payload, $calificacion->id);
        $payload['promedio'] = $this->promedio($payload);

        $calificacion->update($payload);

        return response()->json([
            'message' => 'Calificacion actualizada correctamente.',
            'calificacion' => $this->formatCalificacion($calificacion->fresh()),
        ]);
    }

    public function destroy(ActaNota $calificacion): JsonResponse
    {
        $this->validarDocentePuedeCalificarGrupo($calificacion->id_grupo);

        $calificacion->delete();

        return response()->json([
            'message' => 'Calificacion eliminada correctamente.',
        ]);
    }

    private function rules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'username_postulante' => [$required, 'string', 'max:100', Rule::exists('pgsql.academico.postulante', 'username_postulante')],
            'id_grupo' => [$required, 'string', 'max:100', Rule::exists('pgsql.academico.grupo', 'codigo')],
            'id_materia' => [$required, 'string', 'max:100', Rule::exists('pgsql.academico.materia', 'id')],
            'nota1' => [$required, 'integer', 'min:0', 'max:100'],
            'nota2' => [$required, 'integer', 'min:0', 'max:100'],
            'nota3' => [$required, 'integer', 'min:0', 'max:100'],
            'descripcion' => ['nullable', 'string'],
        ];
    }

    private function validarDuplicado(array $data, ?int $exceptId = null): void
    {
        $query = ActaNota::where('username_postulante', $data['username_postulante'])
            ->where('id_grupo', $data['id_grupo'])
            ->where('id_materia', $data['id_materia']);

        if ($exceptId) {
            $query->where('id', '<>', $exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'username_postulante' => ['Ese postulante ya tiene calificacion registrada para el grupo y materia seleccionados.'],
            ]);
        }
    }

    private function promedio(array $data): float
    {
        return round(((int) $data['nota1'] + (int) $data['nota2'] + (int) $data['nota3']) / 3, 2);
    }

    private function usuarioActualEsDocente(): bool
    {
        return Auth::check() && Auth::user()->tipo === 'docente';
    }

    private function codigosGruposDelDocente(): array
    {
        if (! Auth::check()) {
            return [];
        }

        return DocenteGrupo::where('username_docente', Auth::user()->username)
            ->pluck('codigo_grupo')
            ->all();
    }

    private function gruposDisponiblesParaUsuario()
    {
        $query = Grupo::orderBy('codigo');

        if ($this->usuarioActualEsDocente()) {
            $query->whereIn('codigo', $this->codigosGruposDelDocente());
        }

        return $query->get(['codigo', 'descripcion', 'turno']);
    }

    private function postulantesPorGrupos(array $codigosGrupos)
    {
        $query = Postulante::orderBy('nombre')
            ->where('estado', '!=', 'pendiente_pago');

        if ($this->usuarioActualEsDocente()) {
            $query->whereIn('username_postulante', function ($subquery) use ($codigosGrupos): void {
                $subquery->select('username_postulante')
                    ->from('academico.horario')
                    ->whereIn('id_grupo', $codigosGrupos);
            });
        }

        return $query
            ->get(['username_postulante', 'ci', 'nombre'])
            ->map(fn (Postulante $postulante): array => [
                'username' => $postulante->username_postulante,
                'ci' => $postulante->ci,
                'nombre' => $postulante->nombre,
                'grupos' => DB::table('academico.horario')
                    ->where('username_postulante', $postulante->username_postulante)
                    ->pluck('id_grupo')
                    ->values()
                    ->all(),
            ])
            ->values();
    }

    private function validarDocentePuedeCalificarGrupo(string $codigoGrupo): void
    {
        if (! $this->usuarioActualEsDocente()) {
            return;
        }

        if (! in_array($codigoGrupo, $this->codigosGruposDelDocente(), true)) {
            throw ValidationException::withMessages([
                'id_grupo' => ['No tienes permiso para registrar calificaciones en este grupo.'],
            ]);
        }
    }

    private function formatCalificacion(ActaNota $calificacion): array
    {
        $postulante = Postulante::where('username_postulante', $calificacion->username_postulante)->first();
        $grupo = Grupo::where('codigo', $calificacion->id_grupo)->first();
        $materia = Materia::where('id', $calificacion->id_materia)->first();

        return [
            'id' => $calificacion->id,
            'username_postulante' => $calificacion->username_postulante,
            'postulante' => [
                'username' => $calificacion->username_postulante,
                'nombre' => $postulante?->nombre ?? $calificacion->username_postulante,
                'ci' => $postulante?->ci,
            ],
            'id_grupo' => $calificacion->id_grupo,
            'grupo' => [
                'codigo' => $calificacion->id_grupo,
                'descripcion' => $grupo?->descripcion,
            ],
            'id_materia' => $calificacion->id_materia,
            'materia' => [
                'id' => $calificacion->id_materia,
                'nombre' => $materia?->nombre ?? $calificacion->id_materia,
            ],
            'nota1' => $calificacion->nota1,
            'nota2' => $calificacion->nota2,
            'nota3' => $calificacion->nota3,
            'promedio' => $calificacion->promedio,
            'estado' => $calificacion->promedio >= 51 ? 'aprobado' : 'reprobado',
            'descripcion' => $calificacion->descripcion,
        ];
    }

    private function materiasHabilitadas()
    {
        $query = Materia::orderBy('nombre');

        if (DB::table('information_schema.columns')
            ->where('table_schema', 'academico')
            ->where('table_name', 'materia')
            ->where('column_name', 'estado')
            ->exists()) {
            $query->where('estado', 'habilitada');
        }

        return $query->get(['id', 'nombre']);
    }
}
