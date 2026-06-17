<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $periodoCup1 = DB::table('academico.periodo_academico')
            ->whereRaw('lower(nombre) like ?', ['%cup1-2026%'])
            ->orderByDesc('id')
            ->value('id');

        if ($periodoCup1) {
            DB::table('academico.postulante')
                ->whereNull('id_periodo_academico')
                ->update(['id_periodo_academico' => $periodoCup1]);
        }
    }

    public function down(): void
    {
        // La gestion historica asignada no debe perderse al revertir.
    }
};
