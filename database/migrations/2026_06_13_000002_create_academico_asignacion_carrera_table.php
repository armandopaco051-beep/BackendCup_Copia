<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE IF NOT EXISTS "academico"."asignacion_carrera" (
                id serial NOT NULL PRIMARY KEY,
                username_postulante varchar(500) NOT NULL,
                id_carrera varchar(50) NULL,
                primera_opcion varchar(50) NULL,
                segunda_opcion varchar(50) NULL,
                promedio_final numeric(5,2) NOT NULL,
                nota3_promedio numeric(5,2) NULL,
                nota2_promedio numeric(5,2) NULL,
                nota1_promedio numeric(5,2) NULL,
                opcion_asignada int NULL,
                estado varchar(50) NOT NULL DEFAULT \'asignado\',
                motivo text NULL,
                created_at timestamp DEFAULT now(),
                CONSTRAINT asignacion_carrera_username_unique UNIQUE (username_postulante),
                CONSTRAINT asignacion_carrera_estado_check CHECK (estado IN (\'asignado\', \'lista_espera\', \'reprobado\', \'sin_opcion\')),
                FOREIGN KEY (username_postulante) REFERENCES "academico"."postulante"(username_postulante),
                FOREIGN KEY (id_carrera) REFERENCES "academico"."carrera"(codigo),
                FOREIGN KEY (primera_opcion) REFERENCES "academico"."carrera"(codigo),
                FOREIGN KEY (segunda_opcion) REFERENCES "academico"."carrera"(codigo)
            )
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS "academico"."asignacion_carrera"');
    }
};
