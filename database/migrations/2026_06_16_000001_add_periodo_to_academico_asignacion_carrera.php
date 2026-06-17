<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE "academico"."asignacion_carrera" ADD COLUMN IF NOT EXISTS id_periodo_academico int NULL');

        DB::statement('
            UPDATE "academico"."asignacion_carrera" asignacion
            SET id_periodo_academico = postulante.id_periodo_academico
            FROM "academico"."postulante" postulante
            WHERE postulante.username_postulante = asignacion.username_postulante
              AND asignacion.id_periodo_academico IS NULL
        ');

        DB::statement('
            ALTER TABLE "academico"."asignacion_carrera"
            DROP CONSTRAINT IF EXISTS asignacion_carrera_username_unique
        ');

        DB::statement('
            ALTER TABLE "academico"."asignacion_carrera"
            ADD CONSTRAINT asignacion_carrera_username_periodo_unique
            UNIQUE (username_postulante, id_periodo_academico)
        ');

        DB::statement('
            ALTER TABLE "academico"."asignacion_carrera"
            ADD CONSTRAINT asignacion_carrera_periodo_fk
            FOREIGN KEY (id_periodo_academico)
            REFERENCES "academico"."periodo_academico"(id)
        ');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE "academico"."asignacion_carrera" DROP CONSTRAINT IF EXISTS asignacion_carrera_periodo_fk');
        DB::statement('ALTER TABLE "academico"."asignacion_carrera" DROP CONSTRAINT IF EXISTS asignacion_carrera_username_periodo_unique');
        DB::statement('ALTER TABLE "academico"."asignacion_carrera" DROP COLUMN IF EXISTS id_periodo_academico');
        DB::statement('
            ALTER TABLE "academico"."asignacion_carrera"
            ADD CONSTRAINT asignacion_carrera_username_unique UNIQUE (username_postulante)
        ');
    }
};
