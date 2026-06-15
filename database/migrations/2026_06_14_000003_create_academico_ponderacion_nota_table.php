<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE IF NOT EXISTS "academico"."ponderacion_nota" (
            id serial primary key,
            nombre varchar(100) not null default \'Ponderacion CUP\',
            nota1_porcentaje numeric(5,2) not null default 30,
            nota2_porcentaje numeric(5,2) not null default 30,
            nota3_porcentaje numeric(5,2) not null default 40,
            estado varchar(50) not null default \'activa\',
            created_at timestamp default now()
        )');

        DB::statement('INSERT INTO "academico"."ponderacion_nota" (nombre, nota1_porcentaje, nota2_porcentaje, nota3_porcentaje, estado)
            SELECT \'Ponderacion CUP\', 30, 30, 40, \'activa\'
            WHERE NOT EXISTS (
                SELECT 1 FROM "academico"."ponderacion_nota" WHERE estado = \'activa\'
            )');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS "academico"."ponderacion_nota"');
    }
};
