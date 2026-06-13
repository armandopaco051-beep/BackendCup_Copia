<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE IF NOT EXISTS "academico"."docente_grupo" (
                username_docente varchar(500) NOT NULL,
                codigo_grupo varchar(100) NOT NULL,
                created_at timestamp DEFAULT now(),
                PRIMARY KEY (username_docente, codigo_grupo),
                FOREIGN KEY (username_docente) REFERENCES "academico"."docente"(username_docente),
                FOREIGN KEY (codigo_grupo) REFERENCES "academico"."grupo"(codigo)
            )
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS "academico"."docente_grupo"');
    }
};
