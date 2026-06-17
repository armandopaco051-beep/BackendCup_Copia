<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use App\Models\PeriodoAcademico;
use App\Models\Postulante;
use App\Models\PostulanteGrupo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PostulanteGrupoController extends Controller
{
    public function index(): JsonResponse
    {
        $periodo = $this->periodoActual();
        $inscripciones = PostulanteGrupo::where('estado', 'inscrito')
            ->when($periodo, fn ($query) => $query->where('id_periodo_academico', $periodo->id))
            ->get()
            ->map(fn (PostulanteGrupo $inscripcion): array => $this->formatInscripcion($inscripcion))
            ->sortBy([
                ['grupo.codigo', 'asc'],
                ['postulante.nombre', 'asc'],
            ])
            ->values();

        return response()->json([
            'caso_uso' => 'Asignar estudiantes a grupos',
            'periodo' => $periodo,
            'grupos' => $this->gruposConCupos($periodo?->id),
            'postulantes_disponibles' => $this->postulantesDisponibles($periodo?->id),
            'inscripciones' => $inscripciones,
        ]);
    }
// hace la inscripcion de un postulante a un grupo
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username_postulante' => ['required', 'string', 'max:500', Rule::exists('pgsql.academico.postulante', 'username_postulante')],
            'id_grupo' => ['required', 'string', 'max:100', Rule::exists('pgsql.academico.grupo', 'codigo')],
        ]);

        $inscripcion = $this->inscribir($validated['username_postulante'], $validated['id_grupo']);

        return response()->json([
            'caso_uso' => 'Asignar estudiantes a grupos',
            'message' => 'Postulante inscrito al grupo correctamente.',
            'inscripcion' => $this->formatInscripcion($inscripcion),
        ], 201);
    }
// elimina la inscripcion de un postulante de un grupo
    public function destroy(string $username, string $grupo): JsonResponse
    {
        $inscripcion = PostulanteGrupo::where('username_postulante', $username)
            ->where('id_grupo', $grupo)
            ->where('estado', 'inscrito')
            ->firstOrFail();

        PostulanteGrupo::where('username_postulante', $username)
            ->where('id_grupo', $grupo)
            ->update(['estado' => 'retirado']);

        return response()->json([
            'message' => 'Postulante retirado del grupo correctamente.',
        ]);
    }

// devuelve los grupos disponibles para un postulante
    public function disponiblesPostulante(): JsonResponse
    {
        $usuario = Auth::user();

        if (! $usuario || $usuario->tipo !== 'postulante') {
            abort(403);
        }

        $actual = PostulanteGrupo::where('username_postulante', $usuario->username)
            ->where('estado', 'inscrito')
            ->first();
        $postulante = Postulante::where('username_postulante', $usuario->username)->first();
        $periodoId = $postulante?->id_periodo_academico;

        return response()->json([
            'inscripcion_actual' => $actual ? $this->formatInscripcion($actual) : null,
            'grupos' => $this->gruposConCupos($periodoId)
                ->filter(fn (array $grupo): bool => $grupo['cupos_disponibles'] > 0)
                ->values(),
        ]);
    }

// inscribe un postulante a un grupo
    public function inscribirPostulante(Request $request): JsonResponse
    {
        $usuario = Auth::user();

        if (! $usuario || $usuario->tipo !== 'postulante') {
            abort(403);
        }

        $validated = $request->validate([
            'id_grupo' => ['required', 'string', 'max:100', Rule::exists('pgsql.academico.grupo', 'codigo')],
        ]);

        $inscripcion = $this->inscribir($usuario->username, $validated['id_grupo']);

        return response()->json([
            'message' => 'Te inscribiste al grupo correctamente.',
            'inscripcion' => $this->formatInscripcion($inscripcion),
        ], 201);
    }

// inscribe un postulante a un grupo
    private function inscribir(string $username, string $codigoGrupo): PostulanteGrupo
    {
        $postulante = Postulante::where('username_postulante', $username)->firstOrFail();
        $grupo = Grupo::where('codigo', $codigoGrupo)->firstOrFail();
        $periodoId = $grupo->id_periodo_academico ?? $this->periodoActual()?->id;

        if (! in_array($postulante->estado, ['habilitado', 'admitido'], true)) {
            throw ValidationException::withMessages([
                'username_postulante' => ['El postulante debe estar habilitado para inscribirse a un grupo.'],
            ]);
        }

        if ((int) $postulante->id_periodo_academico !== (int) $periodoId) {
            throw ValidationException::withMessages([
                'id_grupo' => ['El postulante y el grupo pertenecen a periodos academicos diferentes.'],
            ]);
        }

        $periodo = PeriodoAcademico::find($periodoId);
        if (! $periodo || $periodo->estado !== 'activo') {
            throw ValidationException::withMessages([
                'id_grupo' => ['Solo se permiten inscripciones en el periodo academico activo.'],
            ]);
        }

        if (($grupo->estado ?? 'activo') !== 'activo') {
            throw ValidationException::withMessages([
                'id_grupo' => ['El grupo seleccionado no esta activo.'],
            ]);
        }

        $inscripcionExistente = PostulanteGrupo::where('username_postulante', $username)
            ->where('estado', 'inscrito')
            ->when($periodoId, fn ($query) => $query->where('id_periodo_academico', $periodoId))
            ->first();

        if ($inscripcionExistente) {
            throw ValidationException::withMessages([
                'username_postulante' => ["El postulante ya esta inscrito en el grupo {$inscripcionExistente->id_grupo}."],
            ]);
        }

        $ocupacion = $this->ocupacionGrupo($codigoGrupo);
        $cupoMaximo = (int) ($grupo->cupo_maximo ?? 70);

        if ($ocupacion >= $cupoMaximo) {
            throw ValidationException::withMessages([
                'id_grupo' => ['El grupo seleccionado ya no tiene cupos disponibles.'],
            ]);
        }

        $retirado = PostulanteGrupo::where('username_postulante', $username)
            ->where('estado', 'retirado')
            ->when($periodoId, fn ($query) => $query->where('id_periodo_academico', $periodoId))
            ->first();

        if ($retirado) {
            DB::table('academico.postulante_grupo')
                ->where('username_postulante', $username)
                ->where('id_grupo', $retirado->id_grupo)
                ->update([
                    'id_grupo' => $codigoGrupo,
                    'id_periodo_academico' => $periodoId,
                    'estado' => 'inscrito',
                    'created_at' => now(),
                ]);

            return PostulanteGrupo::where('username_postulante', $username)
                ->where('id_grupo', $codigoGrupo)
                ->firstOrFail();
        }

        PostulanteGrupo::create([
            'username_postulante' => $username,
            'id_grupo' => $codigoGrupo,
            'id_periodo_academico' => $periodoId,
            'estado' => 'inscrito',
            'created_at' => now(),
        ]);

        return PostulanteGrupo::where('username_postulante', $username)
            ->where('id_grupo', $codigoGrupo)
            ->firstOrFail();
    }
    // devuelve los grupos con cupos disponibles
    private function gruposConCupos(?int $periodoId): Collection
    {
        $ocupaciones = DB::table('academico.postulante_grupo')
            ->where('estado', 'inscrito')
            ->select('id_grupo', DB::raw('COUNT(*) as total'))
            ->groupBy('id_grupo')
            ->pluck('total', 'id_grupo')
            ->map(fn ($total): int => (int) $total);

        return Grupo::orderBy('codigo')
            ->when($periodoId, fn ($query) => $query->where('id_periodo_academico', $periodoId))
            ->get()
            ->map(function (Grupo $grupo) use ($ocupaciones): array {
                $cupoMaximo = (int) ($grupo->cupo_maximo ?? 70);
                $ocupacion = (int) ($ocupaciones[$grupo->codigo] ?? 0);
                $horarios = DB::table('academico.horario_grupo')
                    ->where('id_grupo', $grupo->codigo)
                    ->where('estado', 'confirmado')
                    ->exists();

                return [
                    'codigo' => $grupo->codigo,
                    'descripcion' => $grupo->descripcion,
                    'turno' => $grupo->turno ?? null,
                    'estado' => $grupo->estado ?? 'activo',
                    'cupo_maximo' => $cupoMaximo,
                    'ocupacion' => $ocupacion,
                    'cupos_disponibles' => max($cupoMaximo - $ocupacion, 0),
                    'porcentaje_uso' => $cupoMaximo > 0 ? min((int) round(($ocupacion / $cupoMaximo) * 100), 100) : 0,
                    'tiene_horario_confirmado' => $horarios,
                ];
            })
            ->values();
    }

    // devuelve los postulantes disponibles para inscribir
    private function postulantesDisponibles(?int $periodoId): Collection
    {
        $inscritos = DB::table('academico.postulante_grupo')
            ->where('estado', 'inscrito')
            ->pluck('username_postulante')
            ->all();

        return Postulante::whereIn('estado', ['habilitado', 'admitido'])
            ->when($periodoId, fn ($query) => $query->where('id_periodo_academico', $periodoId))
            ->whereNotIn('username_postulante', $inscritos)
            ->orderBy('nombre')
            ->get(['username_postulante', 'ci', 'nombre', 'estado'])
            ->map(fn (Postulante $postulante): array => [
                'username' => $postulante->username_postulante,
                'ci' => $postulante->ci,
                'nombre' => $postulante->nombre,
                'estado' => $postulante->estado,
            ])
            ->values();
    }

    // formatea la inscripcion para la respuesta
    private function formatInscripcion(PostulanteGrupo $inscripcion): array
    {
        $postulante = Postulante::where('username_postulante', $inscripcion->username_postulante)->first();
        $grupo = Grupo::where('codigo', $inscripcion->id_grupo)->first();

        return [
            'username_postulante' => $inscripcion->username_postulante,
            'id_grupo' => $inscripcion->id_grupo,
            'id_periodo_academico' => $inscripcion->id_periodo_academico,
            'estado' => $inscripcion->estado,
            'created_at' => $inscripcion->created_at,
            'postulante' => [
                'username' => $inscripcion->username_postulante,
                'nombre' => $postulante?->nombre ?? $inscripcion->username_postulante,
                'ci' => $postulante?->ci,
            ],
            'grupo' => [
                'codigo' => $inscripcion->id_grupo,
                'descripcion' => $grupo?->descripcion,
                'turno' => $grupo?->turno,
            ],
        ];
    }

    // devuelve la ocupacion de un grupo
    private function ocupacionGrupo(string $codigoGrupo): int
    {
        return PostulanteGrupo::where('id_grupo', $codigoGrupo)
            ->where('estado', 'inscrito')
            ->count();
    }

    private function periodoActual(): ?PeriodoAcademico
    {
        return PeriodoAcademico::where('estado', 'activo')->orderByDesc('id')->first();
    }
}
