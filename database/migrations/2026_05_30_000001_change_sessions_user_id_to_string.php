<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE sessions ALTER COLUMN user_id TYPE varchar(500) USING user_id::varchar'
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE sessions ALTER COLUMN user_id TYPE bigint USING CASE WHEN user_id ~ '^[0-9]+$' THEN user_id::bigint ELSE NULL END"
        );
    }
};
