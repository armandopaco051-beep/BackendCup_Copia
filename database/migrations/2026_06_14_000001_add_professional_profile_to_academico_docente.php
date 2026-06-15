<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE "academico"."docente" ADD COLUMN IF NOT EXISTS titulo_profesional varchar(500) NULL');
        DB::statement('ALTER TABLE "academico"."docente" ADD COLUMN IF NOT EXISTS nro_registro_profesional varchar(100) NULL');
        DB::statement('ALTER TABLE "academico"."docente" ADD COLUMN IF NOT EXISTS estado_profesional varchar(50) NOT NULL DEFAULT \'pendiente_revision\'');
        DB::statement('ALTER TABLE "academico"."docente" ADD COLUMN IF NOT EXISTS observacion_profesional text NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE "academico"."docente" DROP COLUMN IF EXISTS observacion_profesional');
        DB::statement('ALTER TABLE "academico"."docente" DROP COLUMN IF EXISTS estado_profesional');
        DB::statement('ALTER TABLE "academico"."docente" DROP COLUMN IF EXISTS nro_registro_profesional');
        DB::statement('ALTER TABLE "academico"."docente" DROP COLUMN IF EXISTS titulo_profesional');
    }
};
