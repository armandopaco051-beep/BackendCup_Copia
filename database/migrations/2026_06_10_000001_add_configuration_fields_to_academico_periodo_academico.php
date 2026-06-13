<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE "academico"."periodo_academico" ADD COLUMN IF NOT EXISTS nombre varchar(100) NULL');
        DB::statement('ALTER TABLE "academico"."periodo_academico" ADD COLUMN IF NOT EXISTS fecha_inicio_preinscripcion date NULL');
        DB::statement('ALTER TABLE "academico"."periodo_academico" ADD COLUMN IF NOT EXISTS fecha_fin_preinscripcion date NULL');
        DB::statement('ALTER TABLE "academico"."periodo_academico" ADD COLUMN IF NOT EXISTS fecha_inicio_requisitos date NULL');
        DB::statement('ALTER TABLE "academico"."periodo_academico" ADD COLUMN IF NOT EXISTS fecha_fin_requisitos date NULL');
        DB::statement('ALTER TABLE "academico"."periodo_academico" ADD COLUMN IF NOT EXISTS fecha_inicio_pago date NULL');
        DB::statement('ALTER TABLE "academico"."periodo_academico" ADD COLUMN IF NOT EXISTS fecha_fin_pago date NULL');
        DB::statement('ALTER TABLE "academico"."periodo_academico" ADD COLUMN IF NOT EXISTS estado varchar(50) NOT NULL DEFAULT \'pendiente\'');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE "academico"."periodo_academico" DROP COLUMN IF EXISTS estado');
        DB::statement('ALTER TABLE "academico"."periodo_academico" DROP COLUMN IF EXISTS fecha_fin_pago');
        DB::statement('ALTER TABLE "academico"."periodo_academico" DROP COLUMN IF EXISTS fecha_inicio_pago');
        DB::statement('ALTER TABLE "academico"."periodo_academico" DROP COLUMN IF EXISTS fecha_fin_requisitos');
        DB::statement('ALTER TABLE "academico"."periodo_academico" DROP COLUMN IF EXISTS fecha_inicio_requisitos');
        DB::statement('ALTER TABLE "academico"."periodo_academico" DROP COLUMN IF EXISTS fecha_fin_preinscripcion');
        DB::statement('ALTER TABLE "academico"."periodo_academico" DROP COLUMN IF EXISTS fecha_inicio_preinscripcion');
        DB::statement('ALTER TABLE "academico"."periodo_academico" DROP COLUMN IF EXISTS nombre');
    }
};
