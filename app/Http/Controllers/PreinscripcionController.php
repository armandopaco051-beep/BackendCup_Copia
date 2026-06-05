<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Pago;
use App\Models\Postulante;
use App\Models\PostulanteCarrera;
use App\Models\RequisitoPostulante;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * CU-06: Registrar preinscripcion.
 *
 * Captura los datos personales del bachiller, genera un usuario interno como
 * folio de seguimiento y registra las carreras elegidas.
 */
class PreinscripcionController extends Controller
{
    public function carreras(): JsonResponse
    {
        return response()->json([
            'carreras' => Carrera::orderBy('nombre')
                ->get(['codigo', 'nombre'])
                ->map(fn (Carrera $carrera): array => [
                    'codigo' => $carrera->codigo,
                    'nombre' => $carrera->nombre,
                ])
                ->values(),
        ]);
    }

    public function index(): JsonResponse
    {
        $preinscripciones = Postulante::orderByDesc('username_postulante')
            ->get()
            ->map(fn (Postulante $postulante): array => $this->formatPreinscripcion($postulante))
            ->values();

        return response()->json([
            'preinscripciones' => $preinscripciones,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'correo' => ['required', 'email', 'max:100'],
            'ci' => ['required', 'string', 'max:100'],
            'nombre' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'max:10'],
            'ciudad' => ['required', 'string', 'max:100'],
            'colegio_procedencia' => ['required', 'string'],
            'direccion' => ['required', 'string'],
            'fecha_nacimiento' => ['required', 'date'],
            'genero' => ['required', 'string', 'max:100'],
            'cod_titulo_bachiller' => ['required', 'string'],
            'carreras' => ['nullable', 'array', 'max:2'],
            'carreras.*.id_carrera' => ['required_with:carreras', 'string', 'max:50', Rule::exists('pgsql.academico.carrera', 'codigo')],
            'carreras.*.descripcion' => ['nullable', 'string'],
            'id_carrera' => ['nullable', 'string', 'max:50', Rule::exists('pgsql.academico.carrera', 'codigo')],
            'descripcion' => ['required_with:id_carrera', 'nullable', 'string'],
        ]);

        $carreras = $this->carrerasSeleccionadas($validated);

        $postulante = DB::transaction(function () use ($validated, $carreras): Postulante {
            $username = $this->generarUsernamePostulante();

            $usuario = Usuario::create([
                'username' => $username,
                'password' => $this->generarPasswordTemporal(),
                'codigo_rol' => $this->rolPostulanteId(),
                'tipo' => 'postulante',
            ]);

            $postulante = Postulante::create([
                'username_postulante' => $usuario->username,
                'correo' => $validated['correo'],
                'ci' => $validated['ci'],
                'nombre' => $validated['nombre'],
                'telefono' => $validated['telefono'],
                'ciudad' => $validated['ciudad'],
                'colegio_procedencia' => $validated['colegio_procedencia'],
                'direccion' => $validated['direccion'],
                'fecha_nacimiento' => $validated['fecha_nacimiento'],
                'genero' => $validated['genero'],
                'cod_titulo_bachiller' => $validated['cod_titulo_bachiller'],
                'estado' => 'pendiente',
            ]);

            foreach ($carreras as $carrera) {
                PostulanteCarrera::create([
                    'id_carrera' => $carrera['id_carrera'],
                    'username_postulante' => $usuario->username,
                    'descripcion' => $carrera['descripcion'],
                ]);
            }

            return $postulante;
        });

        return response()->json([
            'caso_uso' => 'CU-06 Registrar preinscripcion',
            'message' => 'Preinscripcion registrada correctamente. Las credenciales de acceso se enviaran al correo cuando el postulante sea habilitado.',
            'preinscripcion' => [
                'username' => $postulante->username_postulante,
                'folio' => strtoupper($postulante->username_postulante),
                'tipo' => 'postulante',
                'correo' => $postulante->correo,
                'ci' => $postulante->ci,
                'nombre' => $postulante->nombre,
                'telefono' => $postulante->telefono,
                'ciudad' => $postulante->ciudad,
                'colegio_procedencia' => $postulante->colegio_procedencia,
                'direccion' => $postulante->direccion,
                'fecha_nacimiento' => $postulante->fecha_nacimiento,
                'genero' => $postulante->genero,
                'cod_titulo_bachiller' => $postulante->cod_titulo_bachiller,
                'carreras' => $this->formatCarreras($postulante->username_postulante),
            ],
        ], 201);
    }

    private function generarUsernamePostulante(): string
    {
        $siguiente = Usuario::where('username', 'like', 'PRE-%')->count() + 1;

        for ($intento = $siguiente; $intento < $siguiente + 1000; $intento++) {
            $username = 'PRE-'.str_pad((string) $intento, 6, '0', STR_PAD_LEFT);

            if (! Usuario::where('username', $username)->exists()) {
                return $username;
            }
        }

        throw ValidationException::withMessages([
            'username' => ['No se pudo generar un folio disponible para el postulante.'],
        ]);
    }

    private function generarPasswordTemporal(): string
    {
        return 'Cup-'.Str::upper(Str::random(10));
    }

    private function rolPostulanteId(): int
    {
        $rol = Rol::where('nombre', 'postulante')->first();

        if (! $rol) {
            throw ValidationException::withMessages([
                'tipo' => ['No existe el rol postulante en seguridad.rol.'],
            ]);
        }

        return $rol->id;
    }

    private function formatPreinscripcion(Postulante $postulante): array
    {
        $carreras = $this->formatCarreras($postulante->username_postulante);
        $pago = Pago::where('username_postulante', $postulante->username_postulante)
            ->latest('id')
            ->first();
        $requisitos = RequisitoPostulante::where('username_postulante', $postulante->username_postulante)->first();

        return [
            'folio' => strtoupper($postulante->username_postulante),
            'username' => $postulante->username_postulante,
            'ci' => $postulante->ci,
            'nombre' => $postulante->nombre,
            'correo' => $postulante->correo,
            'carrera' => $carreras
                ? collect($carreras)->pluck('nombre')->join(' / ')
                : 'Sin carrera',
            'carreras' => $carreras,
            'fecha' => $postulante->fecha_nacimiento,
            'estado' => $this->estadoResumen($postulante, $pago, $requisitos),
        ];
    }

    private function carrerasSeleccionadas(array $validated): array
    {
        $carreras = collect($validated['carreras'] ?? [])
            ->filter(fn (array $carrera): bool => ! empty($carrera['id_carrera']))
            ->map(fn (array $carrera, int $index): array => [
                'id_carrera' => $carrera['id_carrera'],
                'descripcion' => $carrera['descripcion'] ?? ($index === 0 ? 'Primera opcion' : 'Segunda opcion'),
            ]);

        if ($carreras->isEmpty() && ! empty($validated['id_carrera'])) {
            $carreras = collect([[
                'id_carrera' => $validated['id_carrera'],
                'descripcion' => $validated['descripcion'] ?? 'Primera opcion',
            ]]);
        }

        $duplicadas = $carreras->pluck('id_carrera')->duplicates();

        if ($duplicadas->isNotEmpty()) {
            throw ValidationException::withMessages([
                'carreras' => ['No puedes seleccionar la misma carrera dos veces.'],
            ]);
        }

        return $carreras->values()->all();
    }

    private function formatCarreras(string $username): array
    {
        $postulanteCarreras = PostulanteCarrera::where('username_postulante', $username)->get();

        if ($postulanteCarreras->isEmpty()) {
            return [];
        }

        $carreras = Carrera::whereIn('codigo', $postulanteCarreras->pluck('id_carrera'))
            ->get()
            ->keyBy('codigo');

        return $postulanteCarreras
            ->map(fn (PostulanteCarrera $postulanteCarrera): array => [
                'codigo' => $postulanteCarrera->id_carrera,
                'nombre' => $carreras[$postulanteCarrera->id_carrera]->nombre
                    ?? $postulanteCarrera->id_carrera,
                'descripcion' => $postulanteCarrera->descripcion,
            ])
            ->values()
            ->all();
    }

    private function estadoResumen(Postulante $postulante, ?Pago $pago, ?RequisitoPostulante $requisitos): array
    {
        if (in_array($postulante->estado, ['habilitado', 'admitido'], true)) {
            return ['label' => 'Admitido', 'tipo' => 'admitido'];
        }

        if ($pago && in_array($pago->estado, ['pagado', 'registrado'], true)) {
            return ['label' => 'Pagado', 'tipo' => 'pagado'];
        }

        if ($pago && $pago->estado === 'rechazado') {
            return ['label' => 'Rechazado', 'tipo' => 'rechazado'];
        }

        if ($requisitos
            && $requisitos->ci_entregado
            && $requisitos->titulo_entregado
            && $requisitos->libretas_entregadas) {
            return ['label' => 'Validado', 'tipo' => 'validado'];
        }

        return ['label' => 'Pendiente', 'tipo' => 'pendiente'];
    }
}
