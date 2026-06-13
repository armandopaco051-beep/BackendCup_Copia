<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE "academico"."docente" ADD COLUMN IF NOT EXISTS correo varchar(100) NULL');
        DB::statement('ALTER TABLE "academico"."administrativo" ADD COLUMN IF NOT EXISTS correo varchar(100) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE "academico"."administrativo" DROP COLUMN IF EXISTS correo');
        DB::statement('ALTER TABLE "academico"."docente" DROP COLUMN IF EXISTS correo');
    }
};
