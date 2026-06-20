<?php

namespace App\Http\Controllers;

use App\Mail\CredencialesPostulanteMail;
use App\Models\Carrera;
use App\Models\Pago;
use App\Models\PeriodoAcademico;
use App\Models\Postulante;
use App\Models\PostulanteCarrera;
use App\Models\RequisitoPostulante;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * CU-09: Habilitar postulante.
 *
 * Confirma la habilitacion definitiva del estudiante tras validar requisitos
 * fisicos y pago de matricula. Al habilitar, genera credenciales temporales
 * y las envia al correo registrado del postulante.
 */
class HabilitacionPostulanteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $periodoId = $request->integer('periodo_id')
            ?: PeriodoAcademico::where('estado', 'activo')->orderByDesc('id')->value('id');

        $candidatos = Postulante::orderBy('nombre')
            ->where('estado', '!=', 'pendiente_pago')
            ->when($periodoId, fn ($query) => $query->where('id_periodo_academico', $periodoId))
            ->get()
            ->map(fn (Postulante $postulante): array => $this->formatearCandidato($postulante))
            ->values();

        return response()->json([
            'candidatos' => $candidatos,
            'resumen' => [
                'total' => $candidatos->count(),
                'listos' => $candidatos->where('puede_habilitarse', true)->count(),
                'habilitados' => $candidatos->where('estado_postulante', 'habilitado')->count(),
                'en_revision' => $candidatos->where('estado_postulante', '!=', 'habilitado')->count(),
            ],
        ]);
    }

    public function show(string $username): JsonResponse
    {
        $postulante = Postulante::where('username_postulante', $username)->firstOrFail();

        $requisitos = $this->requisitos($username);
        $pago = $this->pago($username);

        return response()->json([
            'caso_uso' => 'CU-09 Habilitar postulante',
            'estado_postulante' => $postulante->estado,
            'puede_habilitarse' => $this->requisitosCompletos($requisitos) && $this->pagoValido($pago),
            'validaciones' => [
                'requisitos_fisicos' => $this->requisitosCompletos($requisitos) ? 'validado' : 'pendiente',
                'pago_matricula' => $this->pagoValido($pago) ? 'validado' : 'pendiente',
            ],
            'requisitos' => $requisitos,
            'pago' => $pago,
        ]);
    }
    // habilita el postulante
    public function store(Request $request, string $username): JsonResponse
    {
        $postulante = Postulante::where('username_postulante', $username)->firstOrFail();

        $validated = $request->validate([
            'observacion' => ['nullable', 'string'],
        ]);

        $requisitos = $this->requisitos($username);
        $pago = $this->pago($username);

        if (! $this->requisitosCompletos($requisitos)) {
            return response()->json([
                'caso_uso' => 'CU-09 Habilitar postulante',
                'message' => 'No se puede habilitar: faltan requisitos fisicos por validar.',
                'estado_postulante' => $postulante->estado,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $this->pagoValido($pago)) {
            return response()->json([
                'caso_uso' => 'CU-09 Habilitar postulante',
                'message' => 'No se puede habilitar: falta validar el pago de matricula.',
                'estado_postulante' => $postulante->estado,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $usuario = Usuario::where('username', $postulante->username_postulante)->firstOrFail();

        if ($postulante->estado === 'habilitado') {
            return response()->json([
                'caso_uso' => 'CU-09 Habilitar postulante',
                'message' => 'El postulante ya se encuentra habilitado.',
                'estado_postulante' => $postulante->estado,
                'credenciales' => [
                    'username' => $usuario->username,
                    'correo' => $postulante->correo,
                    'correo_enviado' => null,
                    'aviso' => 'Las credenciales ya fueron generadas anteriormente.',
                ],
                'postulante' => $postulante,
            ]);
        }

        $passwordTemporal = $this->generarPasswordTemporal();

        $postulante->update(['estado' => 'habilitado']);
        $usuario->update(['password' => $passwordTemporal]);

        $correo = $this->enviarCredenciales($postulante, $usuario, $passwordTemporal);
        $message = $correo['enviado']
            ? 'Postulante habilitado correctamente. Las credenciales fueron enviadas al correo del postulante.'
            : 'Postulante habilitado correctamente, pero no se pudo enviar el correo de credenciales.';

        return response()->json([
            'caso_uso' => 'CU-09 Habilitar postulante',
            'message' => $message,
            'estado_postulante' => $postulante->estado,
            'observacion' => $validated['observacion'] ?? null,
            'credenciales' => [
                'username' => $usuario->username,
                'correo' => $postulante->correo,
                'correo_enviado' => $correo['enviado'],
                'aviso' => $correo['mensaje'],
                'password_temporal' => app()->environment('local') ? $passwordTemporal : null,
            ],
            'postulante' => $postulante,
        ]);
    }
    //esto hace la consulta de los requisitos
    private function requisitos(string $username): ?RequisitoPostulante
    {
        return RequisitoPostulante::where('username_postulante', $username)->first();
    }   
    //esto hace la consulta del pago
    private function pago(string $username): ?Pago
    {
        return Pago::where('username_postulante', $username)
            ->latest('id')
            ->first();
    }

    //verifica si los requisitos estan completos
    private function requisitosCompletos(?RequisitoPostulante $requisitos): bool
    {
        return (bool) $requisitos
            && $requisitos->ci_entregado
            && $requisitos->titulo_entregado
            && $requisitos->libretas_entregadas;
    }

    //verifica si el pago es valido
    private function pagoValido(?Pago $pago): bool
    {
        return (bool) $pago && in_array($pago->estado, ['pagado', 'registrado'], true);
    }

    //genera una password temporal
    private function generarPasswordTemporal(): string
    {
        return 'Cup-'.Str::upper(Str::random(10));
    }

    //envia las credenciales al postulante
    private function enviarCredenciales(Postulante $postulante, Usuario $usuario, string $passwordTemporal): array
    {
        try {
            Mail::to($postulante->correo)->send(
                new CredencialesPostulanteMail($postulante->nombre, $usuario->username, $passwordTemporal)
            );

            return [
                'enviado' => true,
                'mensaje' => 'Credenciales enviadas al correo del postulante.',
            ];
        } catch (Throwable) {
            return [
                'enviado' => false,
                'mensaje' => 'No se pudo enviar el correo. Revisa la configuracion MAIL_* del archivo .env.',
            ];
        }
    }

    //formatea el candidato
    private function formatearCandidato(Postulante $postulante): array
    {
        $username = $postulante->username_postulante;
        $requisitos = $this->requisitos($username);
        $pago = $this->pago($username);
        $postulanteCarrera = PostulanteCarrera::where('username_postulante', $username)->first();
        $carrera = $postulanteCarrera
            ? Carrera::where('codigo', $postulanteCarrera->id_carrera)->first()
            : null;

        $requisitosCompletos = $this->requisitosCompletos($requisitos);
        $pagoValido = $this->pagoValido($pago);

        return [
            'folio' => strtoupper($username),
            'username' => $username,
            'ci' => $postulante->ci,
            'nombre' => $postulante->nombre,
            'correo' => $postulante->correo,
            'telefono' => $postulante->telefono,
            'carrera' => $carrera?->nombre ?? $postulanteCarrera?->descripcion ?? 'Sin carrera',
            'estado_postulante' => $postulante->estado,
            'puede_habilitarse' => $requisitosCompletos && $pagoValido,
            'validaciones' => [
                'requisitos_fisicos' => $requisitosCompletos ? 'validado' : 'pendiente',
                'pago_matricula' => $pagoValido ? 'confirmado' : 'pendiente',
            ],
            'requisitos' => $requisitos,
            'pago' => $pago,
        ];
    }
}
