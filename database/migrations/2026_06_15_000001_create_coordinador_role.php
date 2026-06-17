<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISOS = [
        'registrar_postulante',
        'modificar_postulante',
        'consultar_postulante',
        'habilitar_postulante',
        'asignar_carrera',
        'validar_pago',
        'consultar_pago',
        'controlar_estado_pago',
        'consultar_carrera',
        'controlar_cupos',
        'calcular_grupos',
        'asignar_grupos',
        'consultar_grupos',
        'asignar_horarios',
        'controlar_aulas',
        'evitar_cruces',
        'consultar_estado_final',
        'seguimiento_academico',
        'generar_reportes',
        'exportar_pdf',
        'exportar_excel',
        'visualizar_dashboard',
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            $rolId = DB::table('seguridad.rol')
                ->whereRaw('lower(nombre) = ?', ['coordinador'])
                ->value('id');

            if (! $rolId) {
                $rolId = DB::table('seguridad.rol')->insertGetId([
                    'nombre' => 'coordinador',
                ], 'id');
            }

            $permisos = DB::table('seguridad.permiso')
                ->whereIn('nombre', self::PERMISOS)
                ->pluck('codigo');

            foreach ($permisos as $codigoPermiso) {
                DB::table('seguridad.permiso_rol')->insertOrIgnore([
                    'codigo_permiso' => $codigoPermiso,
                    'id_rol' => $rolId,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $rolId = DB::table('seguridad.rol')
                ->whereRaw('lower(nombre) = ?', ['coordinador'])
                ->value('id');

            if (! $rolId) {
                return;
            }

            DB::table('seguridad.permiso_rol')->where('id_rol', $rolId)->delete();

            if (! DB::table('seguridad.usuario')->where('codigo_rol', $rolId)->exists()) {
                DB::table('seguridad.rol')->where('id', $rolId)->delete();
            }
        });
    }
};
