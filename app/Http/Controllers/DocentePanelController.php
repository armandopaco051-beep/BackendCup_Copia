<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocentePanelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();

        abort_if(! $usuario || $usuario->tipo !== 'docente', 403);

        $docente = Docente::where('username_docente', $usuario->username)->firstOrFail();
        $horarios = $this->horarios($usuario->username);

        return response()->json([
            'caso_uso' => 'Consultar horario personal del docente',
            'docente' => [
                'username' => $docente->username_docente,
                'nombre' => $docente->nombre,
                'correo' => $docente->correo,
                'estado_profesional' => $docente->estado_profesional,
                'max_grupos_periodo' => $docente->max_grupos_periodo,
                'max_horas_semana' => $docente->max_horas_semana,
            ],
            'resumen' => [
                'bloques' => $horarios->count(),
                'grupos' => $horarios->pluck('grupo')->unique()->count(),
                'materias' => $horarios->pluck('materia_id')->unique()->count(),
                'horas_semanales' => round(
                    $horarios->sum(fn (array $horario): int => $horario['duracion_minutos']) / 60,
                    2,
                ),
            ],
            'horario' => $horarios
                ->map(fn (array $horario): array => collect($horario)->except('duracion_minutos')->all())
                ->values(),
        ]);
    }

    private function horarios(string $username)
    {
        return DB::table('academico.horario_grupo')
            ->leftJoin('academico.materia', 'materia.id', '=', 'horario_grupo.id_materia')
            ->leftJoin('academico.grupo', 'grupo.codigo', '=', 'horario_grupo.id_grupo')
            ->leftJoin('academico.aula', 'aula.nro_aula', '=', 'horario_grupo.id_aula')
            ->leftJoin('academico.dia', 'dia.id', '=', 'horario_grupo.id_dia')
            ->leftJoin('academico.periodo_academico', 'periodo_academico.id', '=', 'horario_grupo.id_periodo_academico')
            ->where('horario_grupo.username_docente', $username)
            ->whereIn('horario_grupo.estado', ['propuesto', 'confirmado'])
            ->orderBy('horario_grupo.id_dia')
            ->orderBy('horario_grupo.hora_inicio')
            ->get([
                'horario_grupo.id',
                'horario_grupo.id_grupo',
                'grupo.descripcion as grupo_descripcion',
                'horario_grupo.id_materia',
                'materia.nombre as materia',
                'horario_grupo.id_aula',
                'aula.tipo as tipo_aula',
                'aula.piso',
                'aula.capacidad as capacidad_aula',
                'aula.estado as estado_aula',
                'horario_grupo.id_dia',
                'dia.nombre as dia',
                'horario_grupo.hora_inicio',
                'horario_grupo.hora_fin',
                'horario_grupo.turno',
                'horario_grupo.estado',
                'horario_grupo.id_periodo_academico',
                'periodo_academico.nombre as periodo',
            ])
            ->map(function (object $horario): array {
                $inicio = substr((string) $horario->hora_inicio, 0, 5);
                $fin = substr((string) $horario->hora_fin, 0, 5);

                return [
                    'id' => $horario->id,
                    'grupo' => $horario->id_grupo,
                    'grupo_descripcion' => $horario->grupo_descripcion,
                    'materia_id' => $horario->id_materia,
                    'materia' => $horario->materia ?? $horario->id_materia,
                    'dia' => $horario->dia ?? 'Dia '.$horario->id_dia,
                    'dia_id' => $horario->id_dia,
                    'hora_inicio' => $inicio,
                    'hora_fin' => $fin,
                    'duracion_minutos' => $this->minutosEntre($inicio, $fin),
                    'turno' => $horario->turno,
                    'aula' => $horario->id_aula,
                    'tipo_aula' => $horario->tipo_aula,
                    'piso' => $horario->piso,
                    'capacidad_aula' => $horario->capacidad_aula,
                    'estado_aula' => $horario->estado_aula,
                    'periodo_id' => $horario->id_periodo_academico,
                    'periodo' => $horario->periodo ?? 'Periodo sin nombre',
                    'estado' => $horario->estado,
                ];
            });
    }

    private function minutosEntre(string $inicio, string $fin): int
    {
        [$horaInicio, $minutoInicio] = array_map('intval', explode(':', $inicio));
        [$horaFin, $minutoFin] = array_map('intval', explode(':', $fin));

        return (($horaFin * 60) + $minutoFin) - (($horaInicio * 60) + $minutoInicio);
    }
}
