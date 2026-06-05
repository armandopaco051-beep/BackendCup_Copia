<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE "academico".carrera ADD COLUMN IF NOT EXISTS estado varchar(50) not null default \'habilitada\'');
        DB::statement('ALTER TABLE "academico".materia ADD COLUMN IF NOT EXISTS estado varchar(50) not null default \'habilitada\'');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE "academico".materia DROP COLUMN IF EXISTS estado');
        DB::statement('ALTER TABLE "academico".carrera DROP COLUMN IF EXISTS estado');
    }
};
