<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE IF NOT EXISTS "academico"."horario_grupo" (
                id serial NOT NULL PRIMARY KEY,
                id_grupo varchar(100) NOT NULL,
                id_materia varchar(100) NOT NULL,
                id_aula int NOT NULL,
                username_docente varchar(500) NOT NULL,
                id_dia int NOT NULL,
                hora_inicio time NOT NULL,
                hora_fin time NOT NULL,
                turno varchar(100) NOT NULL,
                id_periodo_academico int NULL,
                estado varchar(50) NOT NULL DEFAULT \'propuesto\',
                created_at timestamp DEFAULT now(),
                CONSTRAINT horario_grupo_estado_check CHECK (estado IN (\'propuesto\', \'confirmado\')),
                CONSTRAINT horario_grupo_unico_bloque UNIQUE (id_grupo, id_dia, hora_inicio, hora_fin),
                FOREIGN KEY (id_grupo) REFERENCES "academico"."grupo"(codigo),
                FOREIGN KEY (id_materia) REFERENCES "academico"."materia"(id),
                FOREIGN KEY (id_aula) REFERENCES "academico"."aula"(nro_aula),
                FOREIGN KEY (username_docente) REFERENCES "academico"."docente"(username_docente),
                FOREIGN KEY (id_dia) REFERENCES "academico"."dia"(id),
                FOREIGN KEY (id_periodo_academico) REFERENCES "academico"."periodo_academico"(id)
            )
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS "academico"."horario_grupo"');
    }
};
