<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\HorarioGrupo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DocenteHorarioController extends Controller
{
    public function index(): JsonResponse
    {
        $horarios = HorarioGrupo::orderBy('id_dia')
            ->orderBy('turno')
            ->orderBy('id_grupo')
            ->orderBy('hora_inicio')
            ->get();

        return response()->json([
            'caso_uso' => 'Asignar docentes a horarios',
            'descripcion' => 'Relaciona docente, materia y grupo mediante cada bloque de horario.',
            'docentes' => Docente::orderBy('nombre')
                ->get()
                ->map(fn (Docente $docente): array => $this->formatDocente($docente))
                ->values(),
            'horarios' => $horarios
                ->map(fn (HorarioGrupo $horario): array => $this->formatHorario($horario))
                ->values(),
        ]);
    }

    public function update(Request $request, HorarioGrupo $horarioGrupo): JsonResponse
    {
        $validated = $request->validate([
            'username_docente' => ['required', 'string', 'max:500', Rule::exists('pgsql.academico.docente', 'username_docente')],
        ]);

        $this->validarDocenteHabilitado($validated['username_docente']);
        $this->validarDocentePuedeDictarMateria($validated['username_docente'], $horarioGrupo->id_materia);
        $this->validarCruceDocente($validated['username_docente'], $horarioGrupo);
        $this->validarCargaHorariaDocente($validated['username_docente'], $horarioGrupo);

        $horarioGrupo->update([
            'username_docente' => $validated['username_docente'],
        ]);

        return response()->json([
            'message' => 'Docente asignado al horario correctamente.',
            'horario' => $this->formatHorario($horarioGrupo->fresh()),
        ]);
    }

    private function validarDocentePuedeDictarMateria(string $usernameDocente, string $idMateria): void
    {
        $exists = DB::table('academico.docente_materia')
            ->where('username_docente', $usernameDocente)
            ->where('id_materia', $idMateria)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'username_docente' => ['El docente seleccionado no tiene asignada esa materia.'],
            ]);
        }
    }

    private function validarDocenteHabilitado(string $usernameDocente): void
    {
        $docente = Docente::where('username_docente', $usernameDocente)->firstOrFail();

        if (! $docente->estaHabilitadoProfesionalmente()) {
            throw ValidationException::withMessages([
                'username_docente' => ['El docente no esta habilitado profesionalmente para ser asignado a horarios.'],
            ]);
        }
    }

    private function validarCruceDocente(string $usernameDocente, HorarioGrupo $horario): void
    {
        $exists = HorarioGrupo::where('id', '<>', $horario->id)
            ->where('username_docente', $usernameDocente)
            ->where('id_dia', $horario->id_dia)
            ->where('hora_inicio', $horario->hora_inicio)
            ->where('hora_fin', $horario->hora_fin)
            ->whereIn('estado', ['propuesto', 'confirmado'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'username_docente' => ['El docente ya tiene otro horario asignado en ese mismo bloque.'],
            ]);
        }
    }

    private function validarCargaHorariaDocente(string $usernameDocente, HorarioGrupo $horario): void
    {
        $docente = Docente::where('username_docente', $usernameDocente)->firstOrFail();
        $maxGrupos = (int) ($docente->max_grupos_periodo ?? 3);
        $maxHoras = (float) ($docente->max_horas_semana ?? 30);

        $query = HorarioGrupo::where('id', '<>', $horario->id)
            ->where('username_docente', $usernameDocente)
            ->whereIn('estado', ['propuesto', 'confirmado']);

        $query = $horario->id_periodo_academico
            ? $query->where('id_periodo_academico', $horario->id_periodo_academico)
            : $query->whereNull('id_periodo_academico');

        $horarios = $query->get(['id_grupo', 'hora_inicio', 'hora_fin']);
        $grupos = $horarios->pluck('id_grupo')->push($horario->id_grupo)->unique()->values();

        if ($grupos->count() > $maxGrupos) {
            throw ValidationException::withMessages([
                'username_docente' => ["El docente supera la cantidad maxima de {$maxGrupos} grupo(s) permitida para este periodo."],
            ]);
        }

        $minutosActuales = $horarios->sum(fn (HorarioGrupo $item): int => $this->minutosEntre($item->hora_inicio, $item->hora_fin));
        $minutosTotales = $minutosActuales + $this->minutosEntre($horario->hora_inicio, $horario->hora_fin);
        $horasTotales = round($minutosTotales / 60, 2);

        if ($horasTotales > $maxHoras) {
            throw ValidationException::withMessages([
                'username_docente' => ["El docente supera la carga maxima de {$maxHoras} hora(s) semanales. Carga calculada: {$horasTotales} hora(s)."],
            ]);
        }
    }

    private function minutosEntre(string $inicio, string $fin): int
    {
        return max((int) ((strtotime($fin) - strtotime($inicio)) / 60), 0);
    }

    private function formatDocente(Docente $docente): array
    {
        $materias = DB::table('academico.docente_materia')
            ->join('academico.materia as materia', 'materia.id', '=', 'docente_materia.id_materia')
            ->where('docente_materia.username_docente', $docente->username_docente)
            ->orderBy('materia.nombre')
            ->get(['materia.id', 'materia.nombre'])
            ->map(fn ($materia): array => [
                'id' => $materia->id,
                'nombre' => $materia->nombre,
            ])
            ->values();

        return [
            'username' => $docente->username_docente,
            'nombre' => $docente->nombre,
            'correo' => $docente->correo,
            'titulo_profesional' => $docente->titulo_profesional,
            'estado_profesional' => $docente->estado_profesional ?? 'pendiente_revision',
            'max_grupos_periodo' => $docente->max_grupos_periodo ?? 3,
            'max_horas_semana' => $docente->max_horas_semana ?? 30,
            'materias_ids' => $materias->pluck('id')->values()->all(),
            'materias' => $materias,
        ];
    }

    private function formatHorario(HorarioGrupo $horario): array
    {
        $grupo = DB::table('academico.grupo')->where('codigo', $horario->id_grupo)->first();
        $materia = DB::table('academico.materia')->where('id', $horario->id_materia)->first();
        $aula = DB::table('academico.aula')->where('nro_aula', $horario->id_aula)->first();
        $docente = DB::table('academico.docente')->where('username_docente', $horario->username_docente)->first();
        $dia = DB::table('academico.dia')->where('id', $horario->id_dia)->first();

        return [
            'id' => $horario->id,
            'grupo' => [
                'codigo' => $horario->id_grupo,
                'descripcion' => $grupo?->descripcion,
                'turno' => $grupo?->turno ?? $horario->turno,
            ],
            'materia' => [
                'id' => $horario->id_materia,
                'nombre' => $materia?->nombre ?? $horario->id_materia,
            ],
            'aula' => [
                'nro_aula' => $horario->id_aula,
                'tipo' => $aula?->tipo,
                'piso' => $aula?->piso,
            ],
            'docente' => [
                'username' => $horario->username_docente,
                'nombre' => $docente?->nombre ?? $horario->username_docente,
            ],
            'dia' => [
                'id' => $horario->id_dia,
                'nombre' => $dia?->nombre ?? 'Dia '.$horario->id_dia,
            ],
            'hora_inicio' => substr((string) $horario->hora_inicio, 0, 5),
            'hora_fin' => substr((string) $horario->hora_fin, 0, 5),
            'turno' => $horario->turno,
            'estado' => $horario->estado,
        ];
    }
}
