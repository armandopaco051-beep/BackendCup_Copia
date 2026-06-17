<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            ALTER TABLE "academico"."postulante"
            ADD COLUMN IF NOT EXISTS id_periodo_academico integer NULL
        ');

        DB::statement('
            CREATE INDEX IF NOT EXISTS postulante_id_periodo_academico_index
            ON "academico"."postulante" (id_periodo_academico)
        ');

        DB::statement(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM pg_constraint
                    WHERE conname = 'postulante_id_periodo_academico_foreign'
                ) THEN
                    ALTER TABLE "academico"."postulante"
                    ADD CONSTRAINT postulante_id_periodo_academico_foreign
                    FOREIGN KEY (id_periodo_academico)
                    REFERENCES "academico"."periodo_academico"(id);
                END IF;
            END
            $$
        SQL);
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE "academico"."postulante"
            DROP CONSTRAINT IF EXISTS postulante_id_periodo_academico_foreign
        ');
        DB::statement('DROP INDEX IF EXISTS "academico".postulante_id_periodo_academico_index');
        DB::statement('
            ALTER TABLE "academico"."postulante"
            DROP COLUMN IF EXISTS id_periodo_academico
        ');
    }
};
