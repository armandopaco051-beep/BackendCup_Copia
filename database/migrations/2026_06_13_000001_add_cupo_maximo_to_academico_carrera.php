<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE "academico"."carrera" ADD COLUMN IF NOT EXISTS cupo_maximo int NOT NULL DEFAULT 0');

        DB::statement("
            UPDATE \"academico\".\"carrera\"
            SET cupo_maximo = CASE
                WHEN lower(nombre) LIKE '%sistemas%' THEN 250
                WHEN lower(nombre) LIKE '%informatica%' OR lower(nombre) LIKE '%informática%' THEN 250
                WHEN lower(nombre) LIKE '%telecomunic%' THEN 200
                WHEN lower(nombre) LIKE '%robot%' THEN 150
                ELSE cupo_maximo
            END
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE "academico"."carrera" DROP COLUMN IF EXISTS cupo_maximo');
    }
};
