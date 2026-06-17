<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class Cup22026PostulantesSeeder extends Seeder
{
    private const TOTAL = 700;

    private const MARCA = 'CUP2-2026';

    public function run(): void
    {
        $periodo = DB::table('academico.periodo_academico')
            ->where('estado', 'activo')
            ->whereRaw('lower(nombre) like ?', ['%cup2-2026%'])
            ->first();

        if (! $periodo) {
            throw new RuntimeException('No existe un periodo activo llamado Cup2-2026.');
        }

        $rolPostulante = DB::table('seguridad.rol')
            ->whereRaw('lower(nombre) = ?', ['postulante'])
            ->first();

        if (! $rolPostulante) {
            throw new RuntimeException('No existe el rol postulante en seguridad.rol.');
        }

        $carreras = DB::table('academico.carrera')
            ->where('estado', 'habilitada')
            ->orderBy('codigo')
            ->pluck('codigo')
            ->values();

        if ($carreras->count() < 2) {
            throw new RuntimeException('Se necesitan al menos dos carreras habilitadas.');
        }

        $indicesExistentes = DB::table('academico.postulante')
            ->where('id_periodo_academico', $periodo->id)
            ->where('cod_titulo_bachiller', 'like', self::MARCA.'-%')
            ->pluck('cod_titulo_bachiller')
            ->map(fn (string $codigo): int => (int) str_replace(self::MARCA.'-', '', $codigo))
            ->all();

        $indicesPendientes = collect(range(1, self::TOTAL))
            ->diff($indicesExistentes)
            ->values();

        if ($indicesPendientes->isEmpty()) {
            $this->command?->info('Los 700 postulantes de prueba ya existen para '.$periodo->nombre.'.');

            return;
        }

        $ultimoFolio = (int) DB::table('seguridad.usuario')
            ->where('username', 'like', 'PRE-%')
            ->selectRaw("COALESCE(MAX(CAST(SUBSTRING(username FROM 5) AS INTEGER)), 0) AS numero")
            ->value('numero');

        $faker = Faker::create('es_ES');
        $password = Hash::make('Cup2-2026!');
        $fechaPago = $periodo->fecha_inicio_preinscripcion ?? now()->toDateString();
        $ciudades = ['Santa Cruz', 'Montero', 'Warnes', 'Cotoca', 'La Guardia'];
        $colegios = ['Nacional Florida', 'Jose Malky', 'Don Bosco', 'La Salle', 'Maria Auxiliadora'];

        $usuarios = [];
        $postulantes = [];
        $postulanteCarreras = [];
        $pagos = [];

        foreach ($indicesPendientes as $posicion => $indice) {
            $numeroFolio = $ultimoFolio + $posicion + 1;
            $username = 'PRE-'.str_pad((string) $numeroFolio, 6, '0', STR_PAD_LEFT);
            $numeroPrueba = str_pad((string) $indice, 4, '0', STR_PAD_LEFT);
            $carreraPrincipal = $carreras[($indice - 1) % $carreras->count()];
            $carreraSecundaria = $carreras[$indice % $carreras->count()];

            $usuarios[] = [
                'username' => $username,
                'password' => $password,
                'codigo_rol' => $rolPostulante->id,
                'tipo' => 'postulante',
            ];

            $postulantes[] = [
                'username_postulante' => $username,
                'correo' => 'postulante'.$numeroPrueba.'.cup2@example.test',
                'ci' => (string) (9200000 + $indice),
                'nombre' => $faker->name(),
                'telefono' => (string) (76000000 + $indice),
                'ciudad' => $ciudades[($indice - 1) % count($ciudades)],
                'colegio_procedencia' => $colegios[($indice - 1) % count($colegios)],
                'direccion' => 'Zona universitaria, domicilio de prueba '.$numeroPrueba,
                'fecha_nacimiento' => $faker->dateTimeBetween('2002-01-01', '2008-12-31')->format('Y-m-d'),
                'genero' => $indice % 2 === 0 ? 'Femenino' : 'Masculino',
                'cod_titulo_bachiller' => self::MARCA.'-'.$numeroPrueba,
                'estado' => 'pagado',
                'id_periodo_academico' => $periodo->id,
            ];

            $postulanteCarreras[] = [
                'id_carrera' => $carreraPrincipal,
                'username_postulante' => $username,
                'descripcion' => 'Primera opcion',
            ];
            $postulanteCarreras[] = [
                'id_carrera' => $carreraSecundaria,
                'username_postulante' => $username,
                'descripcion' => 'Segunda opcion',
            ];

            $pagos[] = [
                'username_postulante' => $username,
                'monto' => 700.00,
                'nro_comprobante' => 'SIM-'.self::MARCA.'-'.$numeroPrueba,
                'fecha_pago' => $fechaPago,
                'registrado_por' => null,
                'estado' => 'pagado',
                'observacion' => 'Pago simulado confirmado para datos de prueba de '.$periodo->nombre.'.',
                'created_at' => now(),
            ];
        }

        DB::transaction(function () use ($usuarios, $postulantes, $postulanteCarreras, $pagos): void {
            foreach (array_chunk($usuarios, 100) as $lote) {
                DB::table('seguridad.usuario')->insert($lote);
            }
            foreach (array_chunk($postulantes, 100) as $lote) {
                DB::table('academico.postulante')->insert($lote);
            }
            foreach (array_chunk($postulanteCarreras, 200) as $lote) {
                DB::table('academico.postulante_carrera')->insert($lote);
            }
            foreach (array_chunk($pagos, 100) as $lote) {
                DB::table('pago.pago')->insert($lote);
            }
        });

        $this->command?->info(
            $indicesPendientes->count().' postulantes cargados para '.$periodo->nombre
            .'. Total esperado: '.self::TOTAL.'.'
        );
    }
}
