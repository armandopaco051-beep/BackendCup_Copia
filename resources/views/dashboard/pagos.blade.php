@extends('layouts.app')

@section('title', 'Pago de matricula | FICCT')

@section('content')
<main class="portal-shell" data-page="pagos">
    @include('dashboard.partials.sidebar', ['active' => 'pagos'])

    <section class="portal-main">
        @include('dashboard.partials.topbar', [
            'title' => 'Pago de matricula',
            'description' => 'CU-08: registra y consulta el pago unico de 700 Bs. mediante Stripe.',
        ])

        <section class="two-column">
            <article class="module-card">
                <div class="module-head">
                    <span>CU-08</span>
                    <div>
                        <h2>Crear intento de pago</h2>
                        <p>Genera la operacion pendiente para el postulante.</p>
                    </div>
                </div>
                <form id="paymentIntentForm" class="portal-form">
                    <label>Usuario postulante<input name="username" required maxlength="500" placeholder="PRE-000001"></label>
                    <label>Registrado por<input name="registrado_por" placeholder="admin"></label>
                    <label>Observacion<textarea name="observacion" rows="3">Pago de matricula con tarjeta mediante Stripe</textarea></label>
                    <button class="primary-action" type="submit"><span>Crear intento</span></button>
                </form>
            </article>

            <article class="module-card">
                <div class="module-head">
                    <span>Estado</span>
                    <div>
                        <h2>Consultar pago</h2>
                        <p>Revisa el registro asociado a un postulante.</p>
                    </div>
                </div>
                <form id="paymentStatusForm" class="portal-form">
                    <label>Usuario<input name="username" required maxlength="500" placeholder="PRE-000001"></label>
                    <button class="secondary-action" type="submit">Consultar pago</button>
                </form>
            </article>
        </section>

        <section class="module-card payment-result-card">
            <div class="module-head">
                <span>Resultado</span>
                <div>
                    <h2>Estado del pago</h2>
                    <p>El sistema mostrara si el pago esta pendiente, confirmado o si hubo algun error.</p>
                </div>
            </div>

            <div id="paymentOutput" class="payment-status-panel">
                <span class="status-pill">Sin consulta</span>
                <p>Crea un intento o consulta un postulante para ver el estado del pago.</p>
            </div>
        </section>
    </section>
</main>
@endsection
