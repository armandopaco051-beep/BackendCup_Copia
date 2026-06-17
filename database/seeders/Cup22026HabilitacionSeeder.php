<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Cup22026HabilitacionSeeder extends Seeder
{
    private const MARCA = 'CUP2-2026';

    private const VALIDADO_POR = 'admin123';

    public function run(): void
    {
        $periodo = DB::table('academico.periodo_academico')
            ->where('estado', 'activo')
            ->whereRaw('lower(nombre) like ?', ['%cup2-2026%'])
            ->first();

        if (! $periodo) {
            throw new RuntimeException('No existe un periodo activo llamado Cup2-2026.');
        }

        $responsable = DB::table('seguridad.usuario')
            ->where('username', self::VALIDADO_POR)
            ->first();

        if (! $responsable) {
            throw new RuntimeException('No existe el usuario administrador '.self::VALIDADO_POR.'.');
        }

        $postulantes = DB::table('academico.postulante')
            ->where('id_periodo_academico', $periodo->id)
            ->where('cod_titulo_bachiller', 'like', self::MARCA.'-%')
            ->whereIn('estado', ['pagado', 'validado', 'habilitado'])
            ->orderBy('username_postulante')
            ->get(['username_postulante', 'nombre', 'ci']);

        if ($postulantes->count() !== 700) {
            throw new RuntimeException(
                "Se esperaban 700 postulantes pagados de prueba y se encontraron {$postulantes->count()}."
            );
        }

        $fecha = now();

        DB::transaction(function () use ($postulantes, $periodo, $fecha): void {
            foreach ($postulantes as $postulante) {
                DB::table('academico.requisito_postulante')->updateOrInsert(
                    ['username_postulante' => $postulante->username_postulante],
                    [
                        'ci_entregado' => true,
                        'titulo_entregado' => true,
                        'libretas_entregadas' => true,
                        'observacion' => 'Tres requisitos físicos validados para datos de prueba de '.$periodo->nombre.'.',
                        'validado_por' => self::VALIDADO_POR,
                        'fecha_validacion' => $fecha,
                    ],
                );
            }

            DB::table('academico.postulante')
                ->whereIn('username_postulante', $postulantes->pluck('username_postulante'))
                ->update(['estado' => 'habilitado']);

            $this->registrarAuditoria($postulantes, $periodo, $fecha);
        });

        $this->command?->info(
            '700 postulantes con CI, titulo y libretas validados; estado actualizado a habilitado.'
        );
    }

    private function registrarAuditoria($postulantes, object $periodo, $fecha): void
    {
        $descripcionesExistentes = DB::table('seguridad.bitacora')
            ->whereIn('accion', ['aprobar_requisitos', 'habilitar_postulante'])
            ->whereIn(
                'descripcion',
                $postulantes->flatMap(fn (object $postulante): array => [
                    "Requisitos fisicos aprobados para {$postulante->username_postulante}.",
                    "Postulante {$postulante->username_postulante} habilitado.",
                ]),
            )
            ->pluck('descripcion')
            ->flip();

        $registros = [];

        foreach ($postulantes as $postulante) {
            $descripcionRequisitos = "Requisitos fisicos aprobados para {$postulante->username_postulante}.";
            $descripcionHabilitacion = "Postulante {$postulante->username_postulante} habilitado.";
            $datosBase = [
                'username_postulante' => $postulante->username_postulante,
                'nombre_postulante' => $postulante->nombre,
                'ci_postulante' => $postulante->ci,
                'periodo_id' => $periodo->id,
                'periodo' => $periodo->nombre,
                'validado_por' => self::VALIDADO_POR,
            ];

            if (! $descripcionesExistentes->has($descripcionRequisitos)) {
                $registros[] = [
                    'username' => self::VALIDADO_POR,
                    'rol' => 'administrador',
                    'tipo_usuario' => 'administrativo',
                    'accion' => 'aprobar_requisitos',
                    'modulo' => 'admisiones',
                    'metodo' => 'SEED',
                    'ruta' => 'database/seeders/Cup22026HabilitacionSeeder',
                    'descripcion' => $descripcionRequisitos,
                    'ip' => '127.0.0.1',
                    'user_agent' => 'Laravel Seeder',
                    'datos' => json_encode([
                        ...$datosBase,
                        'ci_entregado' => true,
                        'titulo_entregado' => true,
                        'libretas_entregadas' => true,
                        'estado' => 'validado',
                    ], JSON_THROW_ON_ERROR),
                    'created_at' => $fecha,
                ];
            }

            if (! $descripcionesExistentes->has($descripcionHabilitacion)) {
                $registros[] = [
                    'username' => self::VALIDADO_POR,
                    'rol' => 'administrador',
                    'tipo_usuario' => 'administrativo',
                    'accion' => 'habilitar_postulante',
                    'modulo' => 'admisiones',
                    'metodo' => 'SEED',
                    'ruta' => 'database/seeders/Cup22026HabilitacionSeeder',
                    'descripcion' => $descripcionHabilitacion,
                    'ip' => '127.0.0.1',
                    'user_agent' => 'Laravel Seeder',
                    'datos' => json_encode([
                        ...$datosBase,
                        'estado' => 'habilitado',
                        'correo_enviado' => false,
                        'motivo_correo' => 'Correo de prueba example.test.',
                    ], JSON_THROW_ON_ERROR),
                    'created_at' => $fecha,
                ];
            }
        }

        foreach (array_chunk($registros, 200) as $lote) {
            DB::table('seguridad.bitacora')->insert($lote);
        }
    }
}
