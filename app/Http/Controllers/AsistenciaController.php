<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
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

class AsistenciaController extends Controller
{
    public function index(): JsonResponse
    {
        $query = Asistencia::orderByDesc('fecha')->orderByDesc('id');

        if ($this->usuarioActualEsDocente()) {
            $query->whereIn('id_grupo', $this->codigosGruposDelDocente());
        }

        return response()->json([
            'asistencias' => $query
                ->get()
                ->map(fn (Asistencia $asistencia): array => $this->formatAsistencia($asistencia))
                ->values(),
        ]);
    }

    public function opciones(): JsonResponse
    {
        $grupos = $this->gruposDisponiblesParaUsuario();
        $codigosGrupos = $grupos->pluck('codigo')->all();

        return response()->json([
            'grupos' => $grupos
                ->map(fn (Grupo $grupo): array => [
                    'codigo' => $grupo->codigo,
                    'descripcion' => $grupo->descripcion,
                    'turno' => $grupo->turno ?? null,
                ])
                ->values(),
            'materias' => $this->materiasPorGrupos($codigosGrupos),
            'postulantes' => $this->postulantesPorGrupos($codigosGrupos),
        ]);
    }

    public function storeLote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_grupo' => ['required', 'string', 'max:100', Rule::exists('pgsql.academico.grupo', 'codigo')],
            'id_materia' => ['required', 'string', 'max:100', Rule::exists('pgsql.academico.materia', 'id')],
            'fecha' => ['required', 'date'],
            'asistencias' => ['required', 'array', 'min:1'],
            'asistencias.*.username_postulante' => ['required', 'string', 'max:500', Rule::exists('pgsql.academico.postulante', 'username_postulante')],
            'asistencias.*.estado' => ['required', Rule::in(['presente', 'retraso', 'falta'])],
            'asistencias.*.observacion' => ['nullable', 'string'],
        ]);

        $this->validarDocentePuedeRegistrarGrupo($validated['id_grupo']);
        $this->validarMateriaDelGrupo($validated['id_grupo'], $validated['id_materia']);

        $docente = $this->usernameDocenteParaRegistro();

        DB::transaction(function () use ($validated, $docente): void {
            foreach ($validated['asistencias'] as $item) {
                $this->validarPostulanteDelGrupo($item['username_postulante'], $validated['id_grupo']);

                Asistencia::updateOrCreate(
                    [
                        'username_postulante' => $item['username_postulante'],
                        'id_grupo' => $validated['id_grupo'],
                        'id_materia' => $validated['id_materia'],
                        'fecha' => $validated['fecha'],
                    ],
                    [
                        'username_docente' => $docente,
                        'estado' => $item['estado'],
                        'observacion' => $item['observacion'] ?? null,
                    ],
                );
            }
        });

        return response()->json([
            'caso_uso' => 'Registrar asistencia',
            'message' => 'Asistencia registrada correctamente.',
        ], 201);
    }

    public function destroy(Asistencia $asistencia): JsonResponse
    {
        $this->validarDocentePuedeRegistrarGrupo($asistencia->id_grupo);

        $asistencia->delete();

        return response()->json([
            'message' => 'Asistencia eliminada correctamente.',
        ]);
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

    private function materiasPorGrupos(array $codigosGrupos)
    {
        $materiaGrupos = collect();

        if ($this->tableExists('materia_grupo')) {
            $materiaGrupos = DB::table('academico.materia_grupo')
                ->whereIn('codigo_grupo', $codigosGrupos)
                ->get(['codigo_grupo', 'id_materia']);
        } elseif ($this->tableExists('horario')) {
            $materiaGrupos = DB::table('academico.horario')
                ->whereIn('id_grupo', $codigosGrupos)
                ->selectRaw('id_grupo as codigo_grupo, id_materia')
                ->distinct()
                ->get();
        }

        $idsMateria = $materiaGrupos->pluck('id_materia')->unique()->values()->all();
        $materias = Materia::whereIn('id', $idsMateria)
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->keyBy('id');

        return $materiaGrupos
            ->map(fn ($item): array => [
                'id' => $item->id_materia,
                'nombre' => $materias[$item->id_materia]->nombre ?? $item->id_materia,
                'grupo' => $item->codigo_grupo,
            ])
            ->values();
    }

    private function postulantesPorGrupos(array $codigosGrupos)
    {
        if (! $this->tableExists('horario')) {
            return collect();
        }

        $horarios = DB::table('academico.horario')
            ->whereIn('id_grupo', $codigosGrupos)
            ->get(['username_postulante', 'id_grupo']);

        $usernames = $horarios->pluck('username_postulante')->unique()->values()->all();
        $gruposPorPostulante = $horarios
            ->groupBy('username_postulante')
            ->map(fn ($items) => $items->pluck('id_grupo')->unique()->values()->all());

        return Postulante::whereIn('username_postulante', $usernames)
            ->where('estado', '!=', 'pendiente_pago')
            ->orderBy('nombre')
            ->get(['username_postulante', 'ci', 'nombre'])
            ->map(fn (Postulante $postulante): array => [
                'username' => $postulante->username_postulante,
                'ci' => $postulante->ci,
                'nombre' => $postulante->nombre,
                'grupos' => $gruposPorPostulante[$postulante->username_postulante] ?? [],
            ])
            ->values();
    }

    private function validarDocentePuedeRegistrarGrupo(string $codigoGrupo): void
    {
        if (! $this->usuarioActualEsDocente()) {
            return;
        }

        if (! in_array($codigoGrupo, $this->codigosGruposDelDocente(), true)) {
            throw ValidationException::withMessages([
                'id_grupo' => ['No tienes permiso para registrar asistencia en este grupo.'],
            ]);
        }
    }

    private function validarMateriaDelGrupo(string $codigoGrupo, string $idMateria): void
    {
        if ($this->tableExists('materia_grupo')) {
            $exists = DB::table('academico.materia_grupo')
                ->where('codigo_grupo', $codigoGrupo)
                ->where('id_materia', $idMateria)
                ->exists();

            if (! $exists) {
                throw ValidationException::withMessages([
                    'id_materia' => ['La materia seleccionada no pertenece al grupo.'],
                ]);
            }
        }
    }

    private function validarPostulanteDelGrupo(string $usernamePostulante, string $codigoGrupo): void
    {
        if (! $this->tableExists('horario')) {
            return;
        }

        $exists = DB::table('academico.horario')
            ->where('username_postulante', $usernamePostulante)
            ->where('id_grupo', $codigoGrupo)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'asistencias' => ["El postulante {$usernamePostulante} no pertenece al grupo {$codigoGrupo}."],
            ]);
        }
    }

    private function usernameDocenteParaRegistro(): string
    {
        if ($this->usuarioActualEsDocente()) {
            return Auth::user()->username;
        }

        $docente = DB::table('academico.docente')->orderBy('username_docente')->first();

        if (! $docente) {
            throw ValidationException::withMessages([
                'username_docente' => ['Debe existir al menos un docente para registrar asistencia.'],
            ]);
        }

        return $docente->username_docente;
    }

    private function formatAsistencia(Asistencia $asistencia): array
    {
        $postulante = Postulante::where('username_postulante', $asistencia->username_postulante)->first();
        $materia = Materia::where('id', $asistencia->id_materia)->first();
        $grupo = Grupo::where('codigo', $asistencia->id_grupo)->first();

        return [
            'id' => $asistencia->id,
            'fecha' => $asistencia->fecha?->format('Y-m-d'),
            'estado' => $asistencia->estado,
            'observacion' => $asistencia->observacion,
            'username_docente' => $asistencia->username_docente,
            'username_postulante' => $asistencia->username_postulante,
            'postulante' => [
                'username' => $asistencia->username_postulante,
                'nombre' => $postulante?->nombre ?? $asistencia->username_postulante,
                'ci' => $postulante?->ci,
            ],
            'id_grupo' => $asistencia->id_grupo,
            'grupo' => [
                'codigo' => $asistencia->id_grupo,
                'descripcion' => $grupo?->descripcion,
                'turno' => $grupo?->turno,
            ],
            'id_materia' => $asistencia->id_materia,
            'materia' => [
                'id' => $asistencia->id_materia,
                'nombre' => $materia?->nombre ?? $asistencia->id_materia,
            ],
        ];
    }

    private function tableExists(string $table): bool
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', 'academico')
            ->where('table_name', $table)
            ->exists();
    }
}
