<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE "academico".grupo ADD COLUMN IF NOT EXISTS cupo_maximo int not null default 70');
        DB::statement('ALTER TABLE "academico".grupo ADD COLUMN IF NOT EXISTS turno varchar(50) null');
        DB::statement('ALTER TABLE "academico".grupo ADD COLUMN IF NOT EXISTS id_periodo_academico int null');
        DB::statement('ALTER TABLE "academico".grupo ADD COLUMN IF NOT EXISTS estado varchar(50) not null default \'activo\'');
        DB::statement('ALTER TABLE "academico".grupo ADD COLUMN IF NOT EXISTS created_at timestamp default now()');

        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'grupo_id_periodo_academico_foreign'
    ) THEN
        ALTER TABLE "academico".grupo
        ADD CONSTRAINT grupo_id_periodo_academico_foreign
        FOREIGN KEY (id_periodo_academico)
        REFERENCES "academico".periodo_academico(id);
    END IF;
END $$;
SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE "academico".grupo DROP CONSTRAINT IF EXISTS grupo_id_periodo_academico_foreign');
        DB::statement('ALTER TABLE "academico".grupo DROP COLUMN IF EXISTS created_at');
        DB::statement('ALTER TABLE "academico".grupo DROP COLUMN IF EXISTS estado');
        DB::statement('ALTER TABLE "academico".grupo DROP COLUMN IF EXISTS id_periodo_academico');
        DB::statement('ALTER TABLE "academico".grupo DROP COLUMN IF EXISTS turno');
        DB::statement('ALTER TABLE "academico".grupo DROP COLUMN IF EXISTS cupo_maximo');
    }
};
