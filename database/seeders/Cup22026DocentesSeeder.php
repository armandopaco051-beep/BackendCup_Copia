<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class Cup22026DocentesSeeder extends Seeder
{
    private const PASSWORD_TEMPORAL = 'Docente2026!';

    public function run(): void
    {
        $rolDocente = DB::table('seguridad.rol')
            ->where('nombre', 'docente')
            ->value('id');

        if (! $rolDocente) {
            throw new RuntimeException('No existe el rol docente en seguridad.rol.');
        }

        $materias = DB::table('academico.materia')
            ->whereIn('id', ['COM-001', 'FIS-001', 'ING-001', 'MAT-001'])
            ->pluck('nombre', 'id');

        if ($materias->count() !== 4) {
            throw new RuntimeException('Deben existir las materias COM-001, FIS-001, ING-001 y MAT-001.');
        }

        $docentes = [
            $this->docente('cup2_com_01', 'Daniel Rojas Computacion', 'COM-001', 'Licenciatura en Computacion', 'REG-CUP2-COM-01'),
            $this->docente('cup2_com_02', 'Mariana Suarez Computacion', 'COM-001', 'Ingenieria Informatica', 'REG-CUP2-COM-02'),
            $this->docente('cup2_fis_01', 'Jorge Salvatierra Fisica', 'FIS-001', 'Licenciatura en Fisica', 'REG-CUP2-FIS-01'),
            $this->docente('cup2_fis_02', 'Paola Rivero Fisica', 'FIS-001', 'Licenciatura en Fisica', 'REG-CUP2-FIS-02'),
            $this->docente('cup2_ing_01', 'Andrea Flores Ingles', 'ING-001', 'Licenciatura en Idiomas', 'REG-CUP2-ING-01'),
            $this->docente('cup2_ing_02', 'Luis Mendoza Ingles', 'ING-001', 'Licenciatura en Idiomas', 'REG-CUP2-ING-02'),
            $this->docente('cup2_ing_03', 'Carla Vaca Ingles', 'ING-001', 'Licenciatura en Idiomas', 'REG-CUP2-ING-03'),
            $this->docente('cup2_mat_01', 'Sergio Duran Matematica', 'MAT-001', 'Licenciatura en Matematicas', 'REG-CUP2-MAT-01'),
            $this->docente('cup2_mat_02', 'Monica Castro Matematica', 'MAT-001', 'Licenciatura en Matematicas', 'REG-CUP2-MAT-02'),
        ];

        DB::transaction(function () use ($docentes, $rolDocente): void {
            foreach ($docentes as $docente) {
                DB::table('seguridad.usuario')->updateOrInsert(
                    ['username' => $docente['username']],
                    [
                        'password' => Hash::make(self::PASSWORD_TEMPORAL),
                        'codigo_rol' => $rolDocente,
                        'tipo' => 'docente',
                    ],
                );

                DB::table('academico.docente')->updateOrInsert(
                    ['username_docente' => $docente['username']],
                    [
                        'nombre' => $docente['nombre'],
                        'especializacion' => $docente['especializacion'],
                        'maestria' => 'Educacion Superior',
                        'correo' => $docente['username'].'@cup-uagrm.test',
                        'telefono' => '70000000',
                        'ciudad' => 'Santa Cruz',
                        'titulo_profesional' => $docente['titulo'],
                        'nro_registro_profesional' => $docente['registro'],
                        'estado_profesional' => 'habilitado',
                        'observacion_profesional' => 'Docente de prueba habilitado para cubrir la carga del periodo CUP2-2026.',
                        'max_grupos_periodo' => 3,
                        'max_horas_semana' => 30,
                    ],
                );

                DB::table('academico.docente_materia')
                    ->where('username_docente', $docente['username'])
                    ->delete();

                DB::table('academico.docente_materia')->insert([
                    'username_docente' => $docente['username'],
                    'id_materia' => $docente['materia'],
                    'created_at' => now(),
                ]);
            }
        });

        $this->command?->info('9 docentes habilitados y asignados a materias para CUP2-2026.');
        $this->command?->warn('Password temporal de las cuentas de prueba: '.self::PASSWORD_TEMPORAL);
    }

    private function docente(
        string $username,
        string $nombre,
        string $materia,
        string $titulo,
        string $registro,
    ): array {
        return [
            'username' => $username,
            'nombre' => $nombre,
            'materia' => $materia,
            'titulo' => $titulo,
            'registro' => $registro,
            'especializacion' => match ($materia) {
                'COM-001' => 'Computacion y tecnologia educativa',
                'FIS-001' => 'Fisica aplicada',
                'ING-001' => 'Ensenanza del idioma ingles',
                'MAT-001' => 'Matematica',
            },
        ];
    }
}
