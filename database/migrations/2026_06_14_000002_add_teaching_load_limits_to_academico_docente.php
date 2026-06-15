<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE "academico"."docente" ADD COLUMN IF NOT EXISTS max_grupos_periodo int NOT NULL DEFAULT 3');
        DB::statement('ALTER TABLE "academico"."docente" ADD COLUMN IF NOT EXISTS max_horas_semana numeric(5,2) NOT NULL DEFAULT 30');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE "academico"."docente" DROP COLUMN IF EXISTS max_horas_semana');
        DB::statement('ALTER TABLE "academico"."docente" DROP COLUMN IF EXISTS max_grupos_periodo');
    }
};
