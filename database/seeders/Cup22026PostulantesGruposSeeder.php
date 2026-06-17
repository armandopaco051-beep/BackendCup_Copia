<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Cup22026PostulantesGruposSeeder extends Seeder
{
    public function run(): void
    {
        $periodo = DB::table('academico.periodo_academico')
            ->where('estado', 'activo')
            ->whereRaw('lower(nombre) like ?', ['%cup2-2026%'])
            ->first();

        if (! $periodo) {
            throw new RuntimeException('No existe un periodo activo llamado Cup2-2026.');
        }

        $grupos = DB::table('academico.grupo')
            ->where('id_periodo_academico', $periodo->id)
            ->where('estado', 'activo')
            ->orderBy('codigo')
            ->get(['codigo', 'cupo_maximo']);

        if ($grupos->isEmpty()) {
            throw new RuntimeException('No existen grupos activos para Cup2-2026.');
        }

        $inscritos = DB::table('academico.postulante_grupo')
            ->where('id_periodo_academico', $periodo->id)
            ->where('estado', 'inscrito')
            ->get(['username_postulante', 'id_grupo']);

        $ocupacion = $inscritos
            ->groupBy('id_grupo')
            ->map(fn ($items): int => $items->count());

        $cuposDisponibles = [];

        foreach ($grupos as $grupo) {
            $cupoMaximo = (int) ($grupo->cupo_maximo ?? 70);
            $disponibles = $cupoMaximo - (int) ($ocupacion[$grupo->codigo] ?? 0);

            if ($disponibles < 0) {
                throw new RuntimeException("El grupo {$grupo->codigo} supera su cupo maximo.");
            }

            for ($indice = 0; $indice < $disponibles; $indice++) {
                $cuposDisponibles[] = $grupo->codigo;
            }
        }

        $postulantes = DB::table('academico.postulante')
            ->where('id_periodo_academico', $periodo->id)
            ->whereIn('estado', ['habilitado', 'admitido'])
            ->whereNotIn('username_postulante', $inscritos->pluck('username_postulante'))
            ->pluck('username_postulante')
            ->all();

        if (count($postulantes) > count($cuposDisponibles)) {
            throw new RuntimeException(
                'No existen cupos suficientes: '.count($postulantes)
                .' postulantes para '.count($cuposDisponibles).' cupos.'
            );
        }

        shuffle($postulantes);
        shuffle($cuposDisponibles);

        DB::transaction(function () use ($postulantes, $cuposDisponibles, $periodo): void {
            $fecha = now();
            $registros = [];

            foreach ($postulantes as $indice => $username) {
                $grupo = $cuposDisponibles[$indice];
                $retirado = DB::table('academico.postulante_grupo')
                    ->where('username_postulante', $username)
                    ->where('id_periodo_academico', $periodo->id)
                    ->where('estado', 'retirado')
                    ->exists();

                if ($retirado) {
                    DB::table('academico.postulante_grupo')
                        ->where('username_postulante', $username)
                        ->where('id_periodo_academico', $periodo->id)
                        ->update([
                            'id_grupo' => $grupo,
                            'estado' => 'inscrito',
                            'created_at' => $fecha,
                        ]);

                    continue;
                }

                $registros[] = [
                    'username_postulante' => $username,
                    'id_grupo' => $grupo,
                    'id_periodo_academico' => $periodo->id,
                    'estado' => 'inscrito',
                    'created_at' => $fecha,
                ];
            }

            foreach (array_chunk($registros, 200) as $lote) {
                DB::table('academico.postulante_grupo')->insert($lote);
            }
        });

        $this->command?->info(
            count($postulantes).' postulantes distribuidos aleatoriamente entre '.$grupos->count().' grupos.'
        );
    }
}
