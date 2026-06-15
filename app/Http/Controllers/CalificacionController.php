<?php

namespace App\Http\Controllers;

use App\Models\ActaNota;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\PonderacionNota;
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
            $query->whereExists(function ($subquery): void {
                $subquery->select(DB::raw(1))
                    ->from('academico.horario_grupo')
                    ->whereColumn('horario_grupo.id_grupo', 'acta_nota.id_grupo')
                    ->whereColumn('horario_grupo.id_materia', 'acta_nota.id_materia')
                    ->where('horario_grupo.username_docente', Auth::user()->username);
            });
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
            'materias' => $this->materiasDisponiblesParaUsuario($codigosGrupos),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $this->validarDocentePuedeCalificarHorario($validated['id_grupo'], $validated['id_materia']);
        $this->validarPostulanteDelGrupo($validated['username_postulante'], $validated['id_grupo']);
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

        $this->validarDocentePuedeCalificarHorario($payload['id_grupo'], $payload['id_materia']);
        $this->validarPostulanteDelGrupo($payload['username_postulante'], $payload['id_grupo']);
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
        $this->validarDocentePuedeCalificarHorario($calificacion->id_grupo, $calificacion->id_materia);

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
        $ponderacion = PonderacionNota::activa();

        return round(
            ((int) $data['nota1'] * ($ponderacion->nota1_porcentaje / 100))
            + ((int) $data['nota2'] * ($ponderacion->nota2_porcentaje / 100))
            + ((int) $data['nota3'] * ($ponderacion->nota3_porcentaje / 100)),
            2,
        );
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

        return DB::table('academico.horario_grupo')
            ->where('username_docente', Auth::user()->username)
            ->pluck('id_grupo')
            ->unique()
            ->values()
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

        if ($this->tableExists('postulante_grupo')) {
            $inscripciones = DB::table('academico.postulante_grupo')
                ->where('estado', 'inscrito')
                ->whereIn('id_grupo', $codigosGrupos)
                ->get(['username_postulante', 'id_grupo']);

            $usernames = $inscripciones->pluck('username_postulante')->unique()->values()->all();
            $gruposPorPostulante = $inscripciones
                ->groupBy('username_postulante')
                ->map(fn ($items) => $items->pluck('id_grupo')->unique()->values()->all());

            return $query
                ->whereIn('username_postulante', $usernames)
                ->get(['username_postulante', 'ci', 'nombre'])
                ->map(fn (Postulante $postulante): array => [
                    'username' => $postulante->username_postulante,
                    'ci' => $postulante->ci,
                    'nombre' => $postulante->nombre,
                    'grupos' => $gruposPorPostulante[$postulante->username_postulante] ?? [],
                ])
                ->values();
        }

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

    private function validarPostulanteDelGrupo(string $usernamePostulante, string $codigoGrupo): void
    {
        if (! $this->tableExists('postulante_grupo')) {
            return;
        }

        $exists = DB::table('academico.postulante_grupo')
            ->where('username_postulante', $usernamePostulante)
            ->where('id_grupo', $codigoGrupo)
            ->where('estado', 'inscrito')
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'username_postulante' => ['El postulante no esta inscrito oficialmente en el grupo seleccionado.'],
            ]);
        }
    }

    private function validarDocentePuedeCalificarHorario(string $codigoGrupo, string $idMateria): void
    {
        if (! $this->usuarioActualEsDocente()) {
            return;
        }

        $exists = DB::table('academico.horario_grupo')
            ->where('username_docente', Auth::user()->username)
            ->where('id_grupo', $codigoGrupo)
            ->where('id_materia', $idMateria)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'id_materia' => ['No tienes permiso para registrar calificaciones en esa materia y grupo.'],
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
            'estado' => $calificacion->promedio >= 60 ? 'aprobado' : 'reprobado',
            'descripcion' => $calificacion->descripcion,
            'ponderacion' => PonderacionNota::activa()->only([
                'nota1_porcentaje',
                'nota2_porcentaje',
                'nota3_porcentaje',
            ]),
        ];
    }

    private function materiasDisponiblesParaUsuario(array $codigosGrupos)
    {
        if ($this->tableExists('horario_grupo')) {
            return DB::table('academico.horario_grupo')
                ->join('academico.materia', 'materia.id', '=', 'horario_grupo.id_materia')
                ->whereIn('horario_grupo.id_grupo', $codigosGrupos)
                ->when($this->usuarioActualEsDocente(), fn ($query) => $query->where('horario_grupo.username_docente', Auth::user()->username))
                ->selectRaw('horario_grupo.id_materia as id, materia.nombre, horario_grupo.id_grupo as grupo')
                ->distinct()
                ->orderBy('materia.nombre')
                ->get()
                ->map(fn ($materia): array => [
                    'id' => $materia->id,
                    'nombre' => $materia->nombre,
                    'grupo' => $materia->grupo,
                ])
                ->values();
        }

        return $this->materiasHabilitadas()
            ->map(fn (Materia $materia): array => [
                'id' => $materia->id,
                'nombre' => $materia->nombre,
            ])
            ->values();
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

    private function tableExists(string $table): bool
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', 'academico')
            ->where('table_name', $table)
            ->exists();
    }
}
