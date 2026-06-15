<?php

namespace App\Http\Controllers;

use App\Models\ActaNota;
use App\Models\AsignacionCarrera;
use App\Models\Asistencia;
use App\Models\Pago;
use App\Models\Postulante;
use App\Models\PostulanteGrupo;
use App\Models\RequisitoPostulante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostulantePanelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();

        abort_if(! $usuario || $usuario->tipo !== 'postulante', 403);

        $postulante = Postulante::where('username_postulante', $usuario->username)->firstOrFail();
        $requisitos = RequisitoPostulante::where('username_postulante', $usuario->username)->first();
        $pago = Pago::where('username_postulante', $usuario->username)->latest('id')->first();
        $inscripcion = PostulanteGrupo::where('username_postulante', $usuario->username)
            ->where('estado', 'inscrito')
            ->first();
        $asignacionCarrera = AsignacionCarrera::where('username_postulante', $usuario->username)->first();

        return response()->json([
            'postulante' => $this->postulante($postulante),
            'carreras' => $this->carreras($usuario->username),
            'requisitos' => $this->requisitos($requisitos),
            'pago' => $this->pago($pago),
            'grupo' => $this->grupo($inscripcion),
            'horario' => $this->horario($inscripcion),
            'calificaciones' => $this->calificaciones($usuario->username),
            'asistencias' => $this->asistencias($usuario->username),
            'carrera_asignada' => $this->carreraAsignada($asignacionCarrera),
        ]);
    }

    private function postulante(Postulante $postulante): array
    {
        return [
            'username' => $postulante->username_postulante,
            'correo' => $postulante->correo,
            'ci' => $postulante->ci,
            'nombre' => $postulante->nombre,
            'telefono' => $postulante->telefono,
            'ciudad' => $postulante->ciudad,
            'colegio_procedencia' => $postulante->colegio_procedencia,
            'direccion' => $postulante->direccion,
            'fecha_nacimiento' => $postulante->fecha_nacimiento,
            'genero' => $postulante->genero,
            'cod_titulo_bachiller' => $postulante->cod_titulo_bachiller,
            'estado' => $postulante->estado,
            'formulario_url' => url('/api/preinscripciones/'.$postulante->username_postulante.'/formulario'),
        ];
    }

    private function carreras(string $username): array
    {
        return DB::table('academico.postulante_carrera')
            ->leftJoin('academico.carrera', 'carrera.codigo', '=', 'postulante_carrera.id_carrera')
            ->where('postulante_carrera.username_postulante', $username)
            ->orderBy('postulante_carrera.descripcion')
            ->get([
                'postulante_carrera.id_carrera as codigo',
                'carrera.nombre',
                'postulante_carrera.descripcion',
            ])
            ->map(fn ($carrera): array => [
                'codigo' => $carrera->codigo,
                'nombre' => $carrera->nombre ?? $carrera->codigo,
                'descripcion' => $carrera->descripcion,
            ])
            ->values()
            ->all();
    }

    private function requisitos(?RequisitoPostulante $requisitos): array
    {
        if (! $requisitos) {
            return [
                'estado' => 'pendiente',
                'completados' => 0,
                'total' => 3,
                'documentos' => [
                    ['nombre' => 'Fotocopia de cedula', 'entregado' => false],
                    ['nombre' => 'Titulo de bachiller', 'entregado' => false],
                    ['nombre' => 'Libretas escolares', 'entregado' => false],
                ],
                'observacion' => null,
                'fecha_validacion' => null,
            ];
        }

        $documentos = [
            ['nombre' => 'Fotocopia de cedula', 'entregado' => (bool) $requisitos->ci_entregado],
            ['nombre' => 'Titulo de bachiller', 'entregado' => (bool) $requisitos->titulo_entregado],
            ['nombre' => 'Libretas escolares', 'entregado' => (bool) $requisitos->libretas_entregadas],
        ];
        $completados = collect($documentos)->where('entregado', true)->count();

        return [
            'estado' => $completados === count($documentos) ? 'validado' : 'observado',
            'completados' => $completados,
            'total' => count($documentos),
            'documentos' => $documentos,
            'observacion' => $requisitos->observacion,
            'fecha_validacion' => $requisitos->fecha_validacion?->format('Y-m-d H:i'),
        ];
    }

    private function pago(?Pago $pago): array
    {
        if (! $pago) {
            return [
                'estado' => 'sin_pago',
                'monto' => null,
                'comprobante' => null,
                'fecha' => null,
                'observacion' => null,
            ];
        }

        return [
            'estado' => $pago->estado,
            'monto' => $pago->monto,
            'comprobante' => $pago->nro_comprobante,
            'fecha' => $pago->fecha_pago?->format('Y-m-d'),
            'observacion' => $pago->observacion,
        ];
    }

    private function grupo(?PostulanteGrupo $inscripcion): ?array
    {
        if (! $inscripcion) {
            return null;
        }

        $grupo = DB::table('academico.grupo')
            ->where('codigo', $inscripcion->id_grupo)
            ->first();

        return [
            'codigo' => $inscripcion->id_grupo,
            'descripcion' => $grupo?->descripcion,
            'turno' => $grupo?->turno,
            'estado' => $inscripcion->estado,
            'periodo_id' => $inscripcion->id_periodo_academico,
        ];
    }

    private function horario(?PostulanteGrupo $inscripcion): array
    {
        if (! $inscripcion || ! $this->tableExists('horario_grupo')) {
            return [];
        }

        return DB::table('academico.horario_grupo')
            ->leftJoin('academico.materia', 'materia.id', '=', 'horario_grupo.id_materia')
            ->leftJoin('academico.aula', 'aula.nro_aula', '=', 'horario_grupo.id_aula')
            ->leftJoin('academico.dia', 'dia.id', '=', 'horario_grupo.id_dia')
            ->leftJoin('academico.docente', 'docente.username_docente', '=', 'horario_grupo.username_docente')
            ->where('horario_grupo.id_grupo', $inscripcion->id_grupo)
            ->whereIn('horario_grupo.estado', ['propuesto', 'confirmado'])
            ->orderBy('horario_grupo.id_dia')
            ->orderBy('horario_grupo.hora_inicio')
            ->get([
                'horario_grupo.id',
                'horario_grupo.id_grupo',
                'horario_grupo.id_materia',
                'materia.nombre as materia',
                'horario_grupo.id_aula',
                'aula.piso',
                'aula.tipo as tipo_aula',
                'aula.capacidad as capacidad_aula',
                'aula.estado as estado_aula',
                'horario_grupo.username_docente',
                'docente.nombre as docente',
                'horario_grupo.id_dia',
                'dia.nombre as dia',
                'horario_grupo.hora_inicio',
                'horario_grupo.hora_fin',
                'horario_grupo.turno',
                'horario_grupo.estado',
            ])
            ->map(fn ($horario): array => [
                'id' => $horario->id,
                'grupo' => $horario->id_grupo,
                'materia' => $horario->materia ?? $horario->id_materia,
                'aula' => $horario->id_aula,
                'piso' => $horario->piso,
                'tipo_aula' => $horario->tipo_aula,
                'capacidad_aula' => $horario->capacidad_aula,
                'estado_aula' => $horario->estado_aula,
                'docente' => $horario->docente ?? $horario->username_docente,
                'dia' => $horario->dia ?? 'Dia '.$horario->id_dia,
                'hora_inicio' => substr((string) $horario->hora_inicio, 0, 5),
                'hora_fin' => substr((string) $horario->hora_fin, 0, 5),
                'turno' => $horario->turno,
                'estado' => $horario->estado,
            ])
            ->values()
            ->all();
    }

    private function calificaciones(string $username): array
    {
        return ActaNota::where('username_postulante', $username)
            ->orderBy('id_grupo')
            ->orderBy('id_materia')
            ->get()
            ->map(function (ActaNota $nota): array {
                $materia = DB::table('academico.materia')->where('id', $nota->id_materia)->first();

                return [
                    'id' => $nota->id,
                    'grupo' => $nota->id_grupo,
                    'materia' => $materia?->nombre ?? $nota->id_materia,
                    'nota1' => $nota->nota1,
                    'nota2' => $nota->nota2,
                    'nota3' => $nota->nota3,
                    'promedio' => $nota->promedio,
                    'estado' => $nota->promedio === null
                        ? 'pendiente'
                        : ($nota->promedio >= 60 ? 'aprobado' : 'reprobado'),
                    'descripcion' => $nota->descripcion,
                ];
            })
            ->values()
            ->all();
    }

    private function asistencias(string $username): array
    {
        if (! $this->tableExists('asistencia')) {
            return [
                'presente' => 0,
                'retraso' => 0,
                'falta' => 0,
                'total' => 0,
            ];
        }

        $asistencias = Asistencia::where('username_postulante', $username)->get();

        return [
            'presente' => $asistencias->where('estado', 'presente')->count(),
            'retraso' => $asistencias->where('estado', 'retraso')->count(),
            'falta' => $asistencias->where('estado', 'falta')->count(),
            'total' => $asistencias->count(),
        ];
    }

    private function carreraAsignada(?AsignacionCarrera $asignacion): ?array
    {
        if (! $asignacion) {
            return null;
        }

        $carrera = $asignacion->id_carrera
            ? DB::table('academico.carrera')->where('codigo', $asignacion->id_carrera)->first()
            : null;

        return [
            'codigo' => $asignacion->id_carrera,
            'nombre' => $carrera?->nombre,
            'promedio_final' => $asignacion->promedio_final,
            'opcion_asignada' => $asignacion->opcion_asignada,
            'estado' => $asignacion->estado,
            'motivo' => $asignacion->motivo,
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
