<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE IF NOT EXISTS "academico"."asistencia" (
                id serial NOT NULL PRIMARY KEY,
                username_postulante varchar(500) NOT NULL,
                username_docente varchar(500) NOT NULL,
                id_grupo varchar(100) NOT NULL,
                id_materia varchar(100) NOT NULL,
                fecha date NOT NULL,
                estado varchar(50) NOT NULL,
                observacion text NULL,
                created_at timestamp DEFAULT now(),
                CONSTRAINT asistencia_estado_check CHECK (estado IN (\'presente\', \'retraso\', \'falta\')),
                CONSTRAINT asistencia_unique UNIQUE (username_postulante, id_grupo, id_materia, fecha),
                FOREIGN KEY (username_postulante) REFERENCES "academico"."postulante"(username_postulante),
                FOREIGN KEY (username_docente) REFERENCES "academico"."docente"(username_docente),
                FOREIGN KEY (id_grupo) REFERENCES "academico"."grupo"(codigo),
                FOREIGN KEY (id_materia) REFERENCES "academico"."materia"(id)
            )
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS "academico"."asistencia"');
    }
};
