<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Postulante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

/**
 * CU-08: Registrar pago de matricula.
 *
 * Valida y registra el pago unico de 700 Bs. mediante Stripe en modo tarjeta.
 */
class PagoMatriculaController extends Controller
{
    private const MONTO_MATRICULA = '700.00';

    public function crearIntento(Request $request, string $username): JsonResponse
    {
        $postulante = Postulante::where('username_postulante', $username)->firstOrFail();

        $validated = $request->validate([
            'registrado_por' => ['nullable', 'string', 'exists:pgsql.seguridad.usuario,username'],
            'observacion' => ['nullable', 'string'],
        ]);

        $pagoExistente = Pago::where('username_postulante', $postulante->username_postulante)
            ->whereIn('estado', ['pendiente', 'pagado', 'registrado'])
            ->latest('id')
            ->first();

        if ($pagoExistente && in_array($pagoExistente->estado, ['pagado', 'registrado'], true)) {
            return response()->json([
                'caso_uso' => 'CU-08 Registrar pago de matricula',
                'message' => 'El postulante ya tiene un pago de matricula registrado.',
                'pago' => $pagoExistente,
            ], Response::HTTP_CONFLICT);
        }

        $stripe = $this->stripe();

        if ($pagoExistente && $pagoExistente->estado === 'pendiente') {
            $paymentIntent = $stripe->paymentIntents->retrieve($pagoExistente->nro_comprobante);

            return response()->json([
                'caso_uso' => 'CU-08 Registrar pago de matricula',
                'message' => 'Ya existe un intento de pago pendiente.',
                'client_secret' => $paymentIntent->client_secret,
                'pago' => $pagoExistente,
            ]);
        }

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => config('services.stripe.matricula_amount'),
            'currency' => config('services.stripe.currency'),
            'payment_method_types' => ['card'],
            'description' => 'Pago de matricula de admision',
            'metadata' => [
                'caso_uso' => 'CU-08 Registrar pago de matricula',
                'username_postulante' => $postulante->username_postulante,
            ],
        ]);

        $pago = Pago::create([
            'username_postulante' => $postulante->username_postulante,
            'monto' => self::MONTO_MATRICULA,
            'nro_comprobante' => $paymentIntent->id,
            'fecha_pago' => now()->toDateString(),
            'registrado_por' => $validated['registrado_por'] ?? null,
            'estado' => 'pendiente',
            'observacion' => $validated['observacion'] ?? 'Intento de pago creado con Stripe.',
            'created_at' => now(),
        ]);

        return response()->json([
            'caso_uso' => 'CU-08 Registrar pago de matricula',
            'message' => 'Intento de pago de matricula creado correctamente.',
            'monto' => self::MONTO_MATRICULA,
            'moneda' => strtoupper((string) config('services.stripe.currency')),
            'client_secret' => $paymentIntent->client_secret,
            'pago' => $pago,
        ], Response::HTTP_CREATED);
    }

    public function show(string $username): JsonResponse
    {
        Postulante::where('username_postulante', $username)->firstOrFail();

        $pago = Pago::where('username_postulante', $username)
            ->latest('id')
            ->first();

        return response()->json([
            'caso_uso' => 'CU-08 Registrar pago de matricula',
            'estado' => $pago?->estado ?? 'sin_pago',
            'pago' => $pago,
        ]);
    }

    public function confirmar(Request $request, string $username): JsonResponse
    {
        Postulante::where('username_postulante', $username)->firstOrFail();

        $validated = $request->validate([
            'payment_intent_id' => ['required', 'string'],
        ]);

        $paymentIntent = $this->stripe()->paymentIntents->retrieve($validated['payment_intent_id']);
        $estado = $paymentIntent->status === 'succeeded' ? 'pagado' : 'pendiente';

        $pago = Pago::where('username_postulante', $username)
            ->where('nro_comprobante', $paymentIntent->id)
            ->firstOrFail();

        $pago->update([
            'estado' => $estado,
            'fecha_pago' => now()->toDateString(),
            'observacion' => $estado === 'pagado'
                ? 'Pago confirmado por Stripe.'
                : 'Pago aun no confirmado por Stripe: '.$paymentIntent->status,
        ]);

        return response()->json([
            'caso_uso' => 'CU-08 Registrar pago de matricula',
            'message' => $estado === 'pagado'
                ? 'Pago de matricula confirmado correctamente.'
                : 'El pago todavia no fue completado.',
            'stripe_status' => $paymentIntent->status,
            'pago' => $pago,
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = $webhookSecret
                ? Webhook::constructEvent($payload, $request->header('Stripe-Signature'), $webhookSecret)
                : json_decode($payload, false, 512, JSON_THROW_ON_ERROR);
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return response()->json(['message' => 'Webhook invalido.'], Response::HTTP_BAD_REQUEST);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $this->actualizarPagoStripe($event->data->object->id, 'pagado', 'Pago confirmado por webhook de Stripe.');
        }

        if (in_array($event->type, ['payment_intent.payment_failed', 'payment_intent.canceled'], true)) {
            $this->actualizarPagoStripe($event->data->object->id, 'rechazado', 'Pago rechazado o cancelado por Stripe.');
        }

        return response()->json(['received' => true]);
    }

    private function actualizarPagoStripe(string $paymentIntentId, string $estado, string $observacion): void
    {
        Pago::where('nro_comprobante', $paymentIntentId)->update([
            'estado' => $estado,
            'fecha_pago' => now()->toDateString(),
            'observacion' => $observacion,
        ]);
    }

    private function stripe(): StripeClient
    {
        $secret = config('services.stripe.secret');

        abort_if(! $secret, Response::HTTP_INTERNAL_SERVER_ERROR, 'Falta configurar STRIPE_SECRET en .env.');

        try {
            return new StripeClient($secret);
        } catch (ApiErrorException $exception) {
            abort(Response::HTTP_BAD_GATEWAY, $exception->getMessage());
        }
    }
}
