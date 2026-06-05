import {
    apiRequest,
    cleanPayload,
    escapeHtml,
    formData,
    qs,
    qsa,
    setButtonLoading,
    setMessage,
    statusClass,
    validateForm,
} from './api';

let preinscriptions = [];
let careers = [];
let publicPayment = {
    username: null,
    stripe: null,
    elements: null,
    cardNumber: null,
    mounted: false,
    amount: '700.00',
    currency: 'BOB',
};

export function initPreinscripciones() {
    if (!qs('#preinscriptionsTable') && !qs('#preinscriptionForm')) {
        return;
    }

    qs('[data-load-preinscriptions]')?.addEventListener('click', loadPreinscriptions);
    qs('#preinscriptionSearch')?.addEventListener('input', (event) => renderPreinscriptions(event.currentTarget.value));
    qs('#preinscriptionForm')?.addEventListener('submit', savePreinscription);

    loadCareers();
    loadPreinscriptions();
}

async function loadPreinscriptions() {
    if (!qs('#preinscriptionsTable')) {
        return;
    }

    try {
        const data = await apiRequest('/api/preinscripciones');
        preinscriptions = data.preinscripciones || [];
        renderPreinscriptions(qs('#preinscriptionSearch')?.value || '');
    } catch (error) {
        qs('#preinscriptionsTable').innerHTML = `<tr><td colspan="7">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
    }
}

async function loadCareers() {
    const first = qs('#careerFirstSelect');
    const second = qs('#careerSecondSelect');

    if (!first && !second) {
        return;
    }

    try {
        const data = await apiRequest('/api/carreras-habilitadas');
        careers = data.carreras || [];
        renderCareerOptions();
    } catch (error) {
        [first, second].filter(Boolean).forEach((select) => {
            select.innerHTML = `<option value="">${escapeHtml(error.data?.message || 'No se pudieron cargar carreras')}</option>`;
        });
    }
}

function renderCareerOptions() {
    const first = qs('#careerFirstSelect');
    const second = qs('#careerSecondSelect');
    const options = careers.map((career) => `<option value="${escapeHtml(career.codigo)}">${escapeHtml(career.nombre)}</option>`).join('');

    if (first) {
        first.innerHTML = careers.length
            ? `<option value="">Selecciona primera carrera</option>${options}`
            : '<option value="">No hay carreras registradas</option>';
    }

    if (second) {
        second.innerHTML = careers.length
            ? `<option value="">Opcional</option>${options}`
            : '<option value="">No hay carreras registradas</option>';
    }
}

function renderPreinscriptions(filter = '') {
    const table = qs('#preinscriptionsTable');
    const count = qs('#preinscriptionsCount');

    if (!table) {
        return;
    }

    const query = filter.trim().toLowerCase();
    const filtered = preinscriptions.filter((item) => [
        item.folio,
        item.username,
        item.ci,
        item.nombre,
        item.correo,
        item.carrera,
        item.estado?.label,
    ].filter(Boolean).join(' ').toLowerCase().includes(query));

    table.innerHTML = filtered.map((item) => `
        <tr>
            <td>
                <strong>${escapeHtml(item.folio)}</strong>
                <small>${escapeHtml(item.username)}</small>
            </td>
            <td>${escapeHtml(item.ci)}</td>
            <td><strong>${escapeHtml(item.nombre)}</strong></td>
            <td>${escapeHtml(item.carrera)}</td>
            <td>${escapeHtml(item.fecha)}</td>
            <td><span class="status-pill ${statusClass(item.estado?.tipo)}">${escapeHtml(item.estado?.label || 'Pendiente')}</span></td>
            <td class="table-actions">
                <a href="/dashboard/requisitos" aria-label="Ver postulante ${escapeHtml(item.username)}">Ver</a>
                <a href="#preinscriptionFormPanel" aria-label="Editar postulante ${escapeHtml(item.username)}">Editar</a>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="7">No hay preinscripciones registradas.</td></tr>';

    if (count) {
        count.textContent = `${filtered.length} resultado(s)`;
    }
}

async function savePreinscription(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const values = cleanPayload(formData(form));
    const firstCareer = values.carrera_principal;
    const secondCareer = values.carrera_secundaria;

    if (!firstCareer) {
        setMessage('#preinscriptionOutput', 'Selecciona la primera carrera.');
        return;
    }

    if (firstCareer && secondCareer && firstCareer === secondCareer) {
        setMessage('#preinscriptionOutput', 'No puedes seleccionar la misma carrera dos veces.');
        return;
    }

    delete values.carrera_principal;
    delete values.carrera_secundaria;

    values.carreras = [
        firstCareer ? { id_carrera: firstCareer, descripcion: 'Primera opcion' } : null,
        secondCareer ? { id_carrera: secondCareer, descripcion: 'Segunda opcion' } : null,
    ].filter(Boolean);

    try {
        const data = await apiRequest('/api/preinscripciones', {
            method: 'POST',
            body: JSON.stringify(values),
        });

        form.reset();
        renderCareerOptions();
        setMessage('#preinscriptionOutput', data);
        showPublicPaymentGateway(data.preinscripcion);
        loadPreinscriptions();
    } catch (error) {
        setMessage('#preinscriptionOutput', error.data || error.message);
    }
}

async function showPublicPaymentGateway(preinscription) {
    const gateway = qs('#publicPaymentGateway');
    const form = qs('#preinscriptionForm');

    if (!gateway || !preinscription?.username) {
        return;
    }

    publicPayment.username = preinscription.username;
    qs('#publicPaymentFolio').textContent = preinscription.folio || preinscription.username;

    form.hidden = true;
    gateway.hidden = false;

    showPublicPaymentMessage('Preinscripcion registrada. Ahora completa el pago de matricula.', true);

    try {
        const config = await apiRequest('/api/pago-matricula/configuracion');
        publicPayment.amount = config.monto || publicPayment.amount;
        publicPayment.currency = config.moneda || publicPayment.currency;

        qs('#publicPaymentAmount').textContent = `${publicPayment.amount} ${publicPayment.currency}`;
        qs('#publicPaymentButton span').textContent = `Pagar ${publicPayment.amount} ${publicPayment.currency}`;

        if (!config.stripe_key) {
            showPublicPaymentMessage('Falta configurar STRIPE_KEY en .env para mostrar la pasarela de pago.', false);
            return;
        }

        await mountStripeElements(config.stripe_key);
        qs('#publicPaymentForm')?.addEventListener('submit', payPublicMatricula);
    } catch (error) {
        showPublicPaymentMessage(error.data?.message || error.message, false);
    }
}

async function mountStripeElements(stripeKey) {
    if (publicPayment.mounted) {
        return;
    }

    await loadStripeSdk();

    if (!window.Stripe) {
        throw new Error('No se pudo cargar Stripe.js. Revisa tu conexion a internet.');
    }

    publicPayment.stripe = window.Stripe(stripeKey);
    publicPayment.elements = publicPayment.stripe.elements({
        locale: 'es',
    });

    const style = {
        base: {
            color: '#07111f',
            fontFamily: 'Inter, system-ui, sans-serif',
            fontSize: '16px',
            '::placeholder': {
                color: '#b6c4d8',
            },
        },
        invalid: {
            color: '#b42318',
        },
    };

    publicPayment.cardNumber = publicPayment.elements.create('cardNumber', {
        style,
        placeholder: '0000 0000 0000 0000',
    });
    publicPayment.cardNumber.mount('#cardNumberElement');

    publicPayment.elements.create('cardExpiry', {
        style,
        placeholder: 'MM / YY',
    }).mount('#cardExpiryElement');

    publicPayment.elements.create('cardCvc', {
        style,
        placeholder: '123',
    }).mount('#cardCvcElement');

    publicPayment.mounted = true;
}

async function payPublicMatricula(event) {
    event.preventDefault();

    const button = qs('#publicPaymentButton');
    const holder = qs('#cardholderName')?.value.trim();

    if (!holder) {
        showPublicPaymentMessage('Ingresa el titular de la tarjeta.', false);
        return;
    }

    setButtonLoading(button, true, 'Procesando pago...');
    showPublicPaymentMessage('Creando intento de pago seguro...', true);

    try {
        const intent = await apiRequest(`/api/postulantes/${encodeURIComponent(publicPayment.username)}/pago-matricula/intento`, {
            method: 'POST',
            body: JSON.stringify({
                observacion: 'Pago de matricula con tarjeta mediante Stripe.',
            }),
        });

        if (!intent.client_secret) {
            showPublicPaymentMessage(intent.message || 'El pago ya fue registrado para este postulante.', true);
            return;
        }

        showPublicPaymentMessage('Validando tarjeta con Stripe...', true);

        const result = await publicPayment.stripe.confirmCardPayment(intent.client_secret, {
            payment_method: {
                card: publicPayment.cardNumber,
                billing_details: {
                    name: holder,
                },
            },
        });

        if (result.error) {
            showPublicPaymentMessage(result.error.message || 'Stripe rechazo la transaccion.', false);
            return;
        }

        const confirmation = await apiRequest(`/api/postulantes/${encodeURIComponent(publicPayment.username)}/pago-matricula/confirmar`, {
            method: 'POST',
            body: JSON.stringify({
                payment_intent_id: result.paymentIntent.id,
            }),
        });

        showPublicPaymentMessage(confirmation.message || 'Pago confirmado correctamente.', confirmation.pago?.estado === 'pagado');
    } catch (error) {
        showPublicPaymentMessage(error.data?.message || error.message, false);
    } finally {
        setButtonLoading(button, false);
    }
}

function loadStripeSdk() {
    if (window.Stripe) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        const existing = document.querySelector('script[src="https://js.stripe.com/v3/"]');

        if (existing) {
            existing.addEventListener('load', resolve, { once: true });
            existing.addEventListener('error', reject, { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/';
        script.async = true;
        script.onload = resolve;
        script.onerror = () => reject(new Error('No se pudo cargar Stripe.js.'));
        document.head.appendChild(script);
    });
}

function showPublicPaymentMessage(message, success = false) {
    const output = qs('#publicPaymentOutput');

    if (!output) {
        return;
    }

    output.hidden = !message;
    output.textContent = message || '';
    output.classList.toggle('is-success', success);
}
