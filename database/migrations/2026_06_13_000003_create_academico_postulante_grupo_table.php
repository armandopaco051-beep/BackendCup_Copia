<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE TABLE IF NOT EXISTS "academico"."postulante_grupo" (
                username_postulante varchar(500) NOT NULL,
                id_grupo varchar(100) NOT NULL,
                id_periodo_academico int NULL,
                estado varchar(50) NOT NULL DEFAULT \'inscrito\',
                created_at timestamp DEFAULT now(),
                PRIMARY KEY (username_postulante, id_grupo),
                CONSTRAINT postulante_grupo_estado_check CHECK (estado IN (\'inscrito\', \'retirado\')),
                CONSTRAINT postulante_grupo_periodo_unique UNIQUE (username_postulante, id_periodo_academico),
                FOREIGN KEY (username_postulante) REFERENCES "academico"."postulante"(username_postulante),
                FOREIGN KEY (id_grupo) REFERENCES "academico"."grupo"(codigo),
                FOREIGN KEY (id_periodo_academico) REFERENCES "academico"."periodo_academico"(id)
            )
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS "academico"."postulante_grupo"');
    }
};
