<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE "academico".aula ADD COLUMN IF NOT EXISTS capacidad int not null default 70');
        DB::statement('ALTER TABLE "academico".aula ADD COLUMN IF NOT EXISTS estado varchar(50) not null default \'disponible\'');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE "academico".aula DROP COLUMN IF EXISTS estado');
        DB::statement('ALTER TABLE "academico".aula DROP COLUMN IF EXISTS capacidad');
    }
};
