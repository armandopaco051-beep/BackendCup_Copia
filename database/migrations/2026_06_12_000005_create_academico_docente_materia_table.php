<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE IF NOT EXISTS "academico"."docente_materia" (
                username_docente varchar(500) NOT NULL,
                id_materia varchar(100) NOT NULL,
                created_at timestamp DEFAULT now(),
                PRIMARY KEY (username_docente, id_materia),
                FOREIGN KEY (username_docente) REFERENCES "academico"."docente"(username_docente),
                FOREIGN KEY (id_materia) REFERENCES "academico"."materia"(id)
            )
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS "academico"."docente_materia"');
    }
};
