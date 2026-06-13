<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
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
use Symfony\Component\HttpFoundation\Response;

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
            ->where('estado', '!=', 'pendiente_pago')
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
        $this->prevenirPreinscripcionDuplicada($validated['ci'], $validated['correo']);

        $postulante = DB::transaction(function () use ($validated, $carreras): Postulante {
            $this->eliminarPreinscripcionesPendientes($validated['ci'], $validated['correo']);

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
                'estado' => 'pendiente_pago',
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
            'message' => 'Datos registrados temporalmente. Completa el pago de matricula para confirmar la preinscripcion.',
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
                'estado' => $postulante->estado,
                'carreras' => $this->formatCarreras($postulante->username_postulante),
            ],
        ], 201);
    }

    public function consultarPorCi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ci' => ['required', 'string', 'max:100'],
        ]);

        $postulante = Postulante::where('ci', $validated['ci'])
            ->where('estado', '!=', 'pendiente_pago')
            ->latest('username_postulante')
            ->first();

        if (! $postulante) {
            return response()->json([
                'message' => 'No existe una preinscripcion confirmada para ese carnet.',
            ], 404);
        }

        return response()->json([
            'preinscripcion' => $this->formatPreinscripcion($postulante),
            'puede_editar' => $this->puedeEditar($postulante),
        ]);
    }

    public function updatePublic(Request $request, string $username): JsonResponse
    {
        $postulante = Postulante::where('username_postulante', $username)
            ->where('estado', '!=', 'pendiente_pago')
            ->firstOrFail();

        if (! $this->puedeEditar($postulante)) {
            throw ValidationException::withMessages([
                'preinscripcion' => ['La preinscripcion ya no puede editarse porque fue validada o habilitada.'],
            ]);
        }

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
        ]);

        $duplicado = Postulante::where('username_postulante', '!=', $postulante->username_postulante)
            ->where(function ($query) use ($validated): void {
                $query->where('ci', $validated['ci'])
                    ->orWhere('correo', $validated['correo']);
            })
            ->where('estado', '!=', 'pendiente_pago')
            ->exists();

        if ($duplicado) {
            throw ValidationException::withMessages([
                'ci' => ['Ya tiene una preinscripcion realizada.'],
            ]);
        }

        $carreras = $this->carrerasSeleccionadas($validated);

        DB::transaction(function () use ($postulante, $validated, $carreras): void {
            $postulante->update([
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
            ]);

            PostulanteCarrera::where('username_postulante', $postulante->username_postulante)->delete();

            foreach ($carreras as $carrera) {
                PostulanteCarrera::create([
                    'id_carrera' => $carrera['id_carrera'],
                    'username_postulante' => $postulante->username_postulante,
                    'descripcion' => $carrera['descripcion'],
                ]);
            }
        });

        return response()->json([
            'message' => 'Preinscripcion actualizada correctamente.',
            'preinscripcion' => $this->formatPreinscripcion($postulante->refresh()),
        ]);
    }

    public function formularioPdf(string $username)
    {
        $postulante = Postulante::where('username_postulante', $username)->firstOrFail();
        $pago = Pago::where('username_postulante', $username)
            ->whereIn('estado', ['pagado', 'registrado'])
            ->latest('id')
            ->first();

        abort_if(! $pago, Response::HTTP_FORBIDDEN, 'El formulario solo esta disponible despues de confirmar el pago de matricula.');

        $carreras = $this->formatCarreras($postulante->username_postulante);
        $logoPath = public_path('assets/brand/ficct-escudo.png');

        $pdf = Pdf::loadView('pdf.preinscripcion-formulario', [
            'postulante' => $postulante,
            'pago' => $pago,
            'carreras' => $carreras,
            'logoPath' => file_exists($logoPath) ? $logoPath : null,
            'fechaEmision' => now(),
        ])->setPaper('letter');

        return $pdf->download('formulario-preinscripcion-'.$postulante->username_postulante.'.pdf');
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

    private function prevenirPreinscripcionDuplicada(string $ci, string $correo): void
    {
        $postulantes = Postulante::where(function ($query) use ($ci, $correo): void {
            $query->where('ci', $ci)
                ->orWhere('correo', $correo);
        })->get();

        foreach ($postulantes as $postulante) {
            $pago = Pago::where('username_postulante', $postulante->username_postulante)
                ->latest('id')
                ->first();

            if ($postulante->estado !== 'pendiente_pago'
                || ($pago && in_array($pago->estado, ['pagado', 'registrado'], true))) {
                throw ValidationException::withMessages([
                    'ci' => ['Ya tiene una preinscripcion realizada.'],
                ]);
            }
        }
    }

    private function eliminarPreinscripcionesPendientes(string $ci, string $correo): void
    {
        $pendientes = Postulante::where('estado', 'pendiente_pago')
            ->where(function ($query) use ($ci, $correo): void {
                $query->where('ci', $ci)
                    ->orWhere('correo', $correo);
            })
            ->get();

        foreach ($pendientes as $postulante) {
            $username = $postulante->username_postulante;

            Pago::where('username_postulante', $username)->delete();
            PostulanteCarrera::where('username_postulante', $username)->delete();
            RequisitoPostulante::where('username_postulante', $username)->delete();
            $postulante->delete();
            Usuario::where('username', $username)->delete();
        }
    }

    private function puedeEditar(Postulante $postulante): bool
    {
        if (in_array($postulante->estado, ['habilitado', 'admitido'], true)) {
            return false;
        }

        $requisitos = RequisitoPostulante::where('username_postulante', $postulante->username_postulante)->first();

        return ! ($requisitos
            && $requisitos->ci_entregado
            && $requisitos->titulo_entregado
            && $requisitos->libretas_entregadas);
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
            'telefono' => $postulante->telefono,
            'ciudad' => $postulante->ciudad,
            'colegio_procedencia' => $postulante->colegio_procedencia,
            'direccion' => $postulante->direccion,
            'fecha_nacimiento' => $postulante->fecha_nacimiento,
            'genero' => $postulante->genero,
            'cod_titulo_bachiller' => $postulante->cod_titulo_bachiller,
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
        if ($postulante->estado === 'pendiente_pago') {
            return ['label' => 'Pendiente de pago', 'tipo' => 'pendiente'];
        }

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
