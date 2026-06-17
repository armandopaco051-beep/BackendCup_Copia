<?php

namespace Database\Seeders;

use App\Models\PonderacionNota;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Cup22026CalificacionesSeeder extends Seeder
{
    private const DESCRIPCION = 'Notas de prueba CUP2-2026';

    public function run(): void
    {
        $periodo = DB::table('academico.periodo_academico')
            ->where('estado', 'activo')
            ->whereRaw('lower(nombre) like ?', ['%cup2-2026%'])
            ->first();

        if (! $periodo) {
            throw new RuntimeException('No existe un periodo activo llamado Cup2-2026.');
        }

        $inscripciones = DB::table('academico.postulante_grupo')
            ->where('id_periodo_academico', $periodo->id)
            ->where('estado', 'inscrito')
            ->orderBy('id_grupo')
            ->orderBy('username_postulante')
            ->get(['username_postulante', 'id_grupo']);

        if ($inscripciones->isEmpty()) {
            throw new RuntimeException('No hay postulantes inscritos en grupos de Cup2-2026.');
        }

        $materiasPorGrupo = DB::table('academico.horario_grupo')
            ->where('id_periodo_academico', $periodo->id)
            ->whereIn('estado', ['propuesto', 'confirmado'])
            ->select('id_grupo', 'id_materia')
            ->distinct()
            ->orderBy('id_grupo')
            ->orderBy('id_materia')
            ->get()
            ->groupBy('id_grupo')
            ->map(fn ($items): array => $items->pluck('id_materia')->values()->all());

        if ($materiasPorGrupo->isEmpty()) {
            throw new RuntimeException('No hay horarios/materias generados para Cup2-2026.');
        }

        $ponderacion = PonderacionNota::activa();
        $usernames = $inscripciones->pluck('username_postulante')->unique()->values();

        DB::transaction(function () use ($inscripciones, $materiasPorGrupo, $ponderacion, $usernames): void {
            DB::table('academico.acta_nota')
                ->where('descripcion', self::DESCRIPCION)
                ->whereIn('username_postulante', $usernames)
                ->delete();

            $registros = [];
            $indicePostulante = 0;

            foreach ($inscripciones as $inscripcion) {
                $materias = $materiasPorGrupo[$inscripcion->id_grupo] ?? [];

                foreach ($materias as $indiceMateria => $materia) {
                    [$nota1, $nota2, $nota3] = $this->notas($indicePostulante, $indiceMateria);

                    $registros[] = [
                        'nota1' => $nota1,
                        'nota2' => $nota2,
                        'nota3' => $nota3,
                        'promedio' => round(
                            ($nota1 * ($ponderacion->nota1_porcentaje / 100))
                            + ($nota2 * ($ponderacion->nota2_porcentaje / 100))
                            + ($nota3 * ($ponderacion->nota3_porcentaje / 100)),
                            2,
                        ),
                        'descripcion' => self::DESCRIPCION,
                        'id_grupo' => $inscripcion->id_grupo,
                        'id_materia' => $materia,
                        'username_postulante' => $inscripcion->username_postulante,
                    ];
                }

                $indicePostulante++;
            }

            foreach (array_chunk($registros, 500) as $lote) {
                DB::table('academico.acta_nota')->insert($lote);
            }
        });

        $this->command?->info(
            'Calificaciones generadas para '.$inscripciones->count().' postulantes de CUP2-2026.'
        );
    }

    private function notas(int $indicePostulante, int $indiceMateria): array
    {
        $semilla = ($indicePostulante * 37) + ($indiceMateria * 11);
        $perfilAlto = ($indicePostulante % 10) < 8;

        if ($perfilAlto) {
            $base = 61 + ($semilla % 33);
        } else {
            $base = 38 + ($semilla % 20);
        }

        $nota1 = $this->limitar($base + (($semilla % 9) - 4));
        $nota2 = $this->limitar($base + ((($semilla + 3) % 11) - 5));
        $nota3 = $this->limitar($base + 3 + ((($semilla + 7) % 13) - 6));

        return [$nota1, $nota2, $nota3];
    }

    private function limitar(int $nota): int
    {
        return max(0, min(100, $nota));
    }
}
