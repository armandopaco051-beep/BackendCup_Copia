<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS "seguridad"');

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS "seguridad".bitacora (
    id bigserial primary key,
    username varchar(500) null,
    rol varchar(500) null,
    tipo_usuario varchar(100) null,
    accion varchar(120) not null,
    modulo varchar(120) null,
    metodo varchar(20) null,
    ruta text null,
    descripcion text null,
    ip varchar(100) null,
    user_agent text null,
    datos jsonb null,
    created_at timestamp not null default now()
)
SQL);

        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'bitacora_username_foreign'
    ) THEN
        ALTER TABLE "seguridad".bitacora
        ADD CONSTRAINT bitacora_username_foreign
        FOREIGN KEY (username)
        REFERENCES "seguridad".usuario(username)
        ON UPDATE CASCADE
        ON DELETE SET NULL;
    END IF;
END $$;
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS "seguridad".bitacora');
    }
};
