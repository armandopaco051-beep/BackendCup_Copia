import { apiRequest, cleanPayload, escapeHtml, formData, qs, statusClass, validateForm } from './api';

export function initPagos() {
    if (!qs('#paymentIntentForm') && !qs('#paymentStatusForm')) {
        return;
    }

    qs('#paymentIntentForm')?.addEventListener('submit', createPaymentIntent);
    qs('#paymentStatusForm')?.addEventListener('submit', showPayment);
}

async function createPaymentIntent(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const values = formData(form);
    renderPaymentStatus({
        message: 'Creando intento de pago...',
        estado: 'procesando',
    });

    try {
        const data = await apiRequest(`/api/postulantes/${encodeURIComponent(values.username)}/pago-matricula/intento`, {
            method: 'POST',
            body: JSON.stringify(cleanPayload({
                registrado_por: values.registrado_por,
                observacion: values.observacion,
            })),
        });

        renderPaymentStatus(data);
    } catch (error) {
        renderPaymentStatus(error.data || { message: error.message, estado: 'error' }, true);
    }
}

async function showPayment(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const values = formData(form);
    renderPaymentStatus({
        message: 'Consultando pago...',
        estado: 'procesando',
    });

    try {
        const data = await apiRequest(`/api/postulantes/${encodeURIComponent(values.username)}/pago-matricula`);
        renderPaymentStatus(data);
    } catch (error) {
        renderPaymentStatus(error.data || { message: error.message, estado: 'error' }, true);
    }
}

function renderPaymentStatus(data = {}, isError = false) {
    const target = qs('#paymentOutput');

    if (!target) {
        return;
    }

    const pago = data.pago || null;
    const estado = data.estado || pago?.estado || (isError ? 'error' : 'pendiente');
    const message = data.message || paymentMessage(estado);
    const amount = data.monto || pago?.monto;
    const currency = data.moneda || 'BOB';
    const comprobante = pago?.nro_comprobante || null;
    const date = pago?.fecha_pago || null;
    const observation = pago?.observacion || null;
    const stripeHint = data.client_secret
        ? 'Intento creado. Falta completar/confirmar el pago con tarjeta en Stripe para pasar a pagado.'
        : '';

    target.classList.toggle('is-error', isError);
    target.classList.toggle('is-success', ['pagado', 'registrado'].includes(estado));

    target.innerHTML = `
        <div class="payment-status-head">
            <span class="status-pill ${statusClass(estado)}">${escapeHtml(statusLabel(estado))}</span>
            <strong>${escapeHtml(message)}</strong>
        </div>
        <dl class="payment-status-grid">
            <div>
                <dt>Monto</dt>
                <dd>${amount ? `${escapeHtml(amount)} ${escapeHtml(currency)}` : 'Sin monto'}</dd>
            </div>
            <div>
                <dt>Comprobante</dt>
                <dd>${escapeHtml(comprobante || 'Sin comprobante')}</dd>
            </div>
            <div>
                <dt>Fecha</dt>
                <dd>${escapeHtml(date || 'Sin fecha')}</dd>
            </div>
            <div>
                <dt>Observacion</dt>
                <dd>${escapeHtml(observation || stripeHint || 'Sin observacion')}</dd>
            </div>
        </dl>
        ${stripeHint ? `<p class="payment-hint">${escapeHtml(stripeHint)}</p>` : ''}
    `;
}

function paymentMessage(estado) {
    return {
        sin_pago: 'No existe un pago registrado para este postulante.',
        pendiente: 'El pago fue creado, pero todavia no esta confirmado.',
        pagado: 'Pago de matricula confirmado correctamente.',
        registrado: 'Pago de matricula registrado correctamente.',
        rechazado: 'El pago fue rechazado o cancelado.',
        procesando: 'Procesando solicitud de pago.',
        error: 'No se pudo completar la operacion.',
    }[estado] || 'Estado de pago consultado.';
}

function statusLabel(estado) {
    return {
        sin_pago: 'Sin pago',
        pendiente: 'Pendiente',
        pagado: 'Pagado',
        registrado: 'Registrado',
        rechazado: 'Rechazado',
        procesando: 'Procesando',
        error: 'Error',
    }[estado] || estado;
}
