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
    paymentElement: null,
    paymentIntentId: null,
    clientSecret: null,
    mounted: false,
    formBound: false,
    amount: '700.00',
    currency: 'BOB',
};
let publicEditingUsername = null;

export function initPreinscripciones() {
    if (!qs('#preinscriptionsTable') && !qs('#preinscriptionForm')) {
        return;
    }

    qs('[data-load-preinscriptions]')?.addEventListener('click', loadPreinscriptions);
    qs('#preinscriptionSearch')?.addEventListener('input', (event) => renderPreinscriptions(event.currentTarget.value));
    qs('#preinscriptionForm')?.addEventListener('submit', savePreinscription);
    qs('[data-public-lookup]')?.addEventListener('click', lookupPublicPreinscription);
    qs('#publicLookupCi')?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            lookupPublicPreinscription();
        }
    });

    loadCareers();
    loadPreinscriptions();
}
// esta funcion hace la carga de las preinscripciones
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
// esta funcion hace la carga de las carreras
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
// esta funcion renderiza las opciones de las carreras
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

// esta funcion renderiza las preinscripciones
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
                <button type="button" data-edit-preinscription="${escapeHtml(item.username)}" aria-label="Editar postulante ${escapeHtml(item.username)}">Editar</button>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="7">No hay preinscripciones registradas.</td></tr>';

    if (count) {
        count.textContent = `${filtered.length} resultado(s)`;
    }

    qsa('[data-edit-preinscription]').forEach((button) => {
        button.addEventListener('click', () => {
            const preinscription = preinscriptions.find((item) => item.username === button.dataset.editPreinscription);

            if (!preinscription) {
                setMessage('#preinscriptionOutput', 'No se pudo cargar la preinscripcion seleccionada.');
                return;
            }

            fillPublicPreinscriptionForm(preinscription);
            setMessage('#preinscriptionOutput', `Editando ${preinscription.folio}. Guarda los cambios con el boton Actualizar preinscripcion.`);
        });
    });
}

// esta funcion guarda la preinscripcion
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
        if (publicEditingUsername) {
            const data = await apiRequest(`/api/preinscripciones/${encodeURIComponent(publicEditingUsername)}`, {
                method: 'PUT',
                body: JSON.stringify(values),
            });

            setMessage('#preinscriptionOutput', data);
            setMessage('#publicLookupOutput', 'Preinscripcion actualizada correctamente.');
            publicEditingUsername = null;
            qs('#preinscriptionForm button[type="submit"] span').textContent = 'Registrar preinscripcion';
            loadPreinscriptions();
            return;
        }

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

// esta funcion busca la preinscripcion publica
async function lookupPublicPreinscription() {
    const ci = qs('#publicLookupCi')?.value.trim();

    if (!ci) {
        setMessage('#publicLookupOutput', 'Ingresa tu carnet para buscar la preinscripcion.');
        return;
    }

    try {
        const data = await apiRequest(`/api/preinscripciones/consulta?ci=${encodeURIComponent(ci)}`);
        const preinscription = data.preinscripcion;

        setMessage(
            '#publicLookupOutput',
            `${preinscription.folio}: ${preinscription.nombre}. Estado: ${preinscription.estado?.label || 'Registrada'}. ${data.puede_editar ? 'Puedes editar tus datos abajo.' : 'Esta preinscripcion ya no puede editarse.'}`,
        );

        if (data.puede_editar) {
            fillPublicPreinscriptionForm(preinscription);
        }
    } catch (error) {
        setMessage('#publicLookupOutput', error.data || error.message);
    }
}

// esta funcion llena el formulario con los datos de la preinscripcion publica
function fillPublicPreinscriptionForm(preinscription) {
    const form = qs('#preinscriptionForm');

    if (!form) {
        return;
    }

    publicEditingUsername = preinscription.username;

    const values = {
        correo: preinscription.correo,
        ci: preinscription.ci,
        nombre: preinscription.nombre,
        telefono: preinscription.telefono,
        ciudad: preinscription.ciudad,
        colegio_procedencia: preinscription.colegio_procedencia,
        direccion: preinscription.direccion,
        fecha_nacimiento: preinscription.fecha_nacimiento,
        genero: preinscription.genero,
        cod_titulo_bachiller: preinscription.cod_titulo_bachiller,
    };

    Object.entries(values).forEach(([name, value]) => {
        if (form.elements[name]) {
            form.elements[name].value = value || '';
        }
    });

    const careers = preinscription.carreras || [];
    if (form.elements.carrera_principal) {
        form.elements.carrera_principal.value = careers[0]?.codigo || '';
    }
    if (form.elements.carrera_secundaria) {
        form.elements.carrera_secundaria.value = careers[1]?.codigo || '';
    }

    qs('#preinscriptionForm button[type="submit"] span').textContent = 'Actualizar preinscripcion';
    qs('#preinscriptionForm')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// esta funcion muestra la pasarela de pago publica
async function showPublicPaymentGateway(preinscription) {
    const gateway = qs('#publicPaymentGateway');
    const form = qs('#preinscriptionForm');

    if (!gateway || !preinscription?.username) {
        return;
    }

    publicPayment.username = preinscription.username;
    resetPublicPaymentElement();
    qs('#publicPaymentFolio').textContent = preinscription.folio || preinscription.username;

    qs('.register-shell')?.classList.add('is-payment-mode');
    form.hidden = true;
    gateway.hidden = false;
    showPublicPaymentDownload(null);
    showPublicPaymentLogin(false);

    showPublicPaymentMessage('Preinscripcion registrada. Ahora completa el pago de matricula.', true);

    try {
        const config = await apiRequest('/api/pago-matricula/configuracion');
        publicPayment.amount = config.monto || publicPayment.amount;
        publicPayment.currency = config.moneda || publicPayment.currency;

        qs('#publicPaymentAmount').textContent = `${publicPayment.amount} ${publicPayment.currency}`;
        setPublicPaymentButtonText(`Pagar ${publicPayment.amount} ${publicPayment.currency}`);

        if (!config.stripe_key) {
            showPublicPaymentMessage('Falta configurar STRIPE_KEY en .env para mostrar la pasarela de pago.', false);
            return;
        }

        const intent = await createPublicPaymentIntent();

        if (!intent.client_secret) {
            showPublicPaymentMessage(intent.message || 'El pago ya fue registrado para este postulante.', true);
            if (['pagado', 'registrado'].includes(intent.pago?.estado)) {
                showPublicPaymentDownload(`/api/preinscripciones/${encodeURIComponent(publicPayment.username)}/formulario`);
                showPublicPaymentLogin(true);
            }
            return;
        }

        publicPayment.paymentIntentId = intent.pago?.nro_comprobante || null;

        await mountStripeElements(config.stripe_key, intent.client_secret);

        if (!publicPayment.formBound) {
            qs('#publicPaymentForm')?.addEventListener('submit', payPublicMatricula);
            publicPayment.formBound = true;
        }
    } catch (error) {
        showPublicPaymentMessage(error.data?.message || error.message, false);
    }
}

// esta funcion crea el intento de pago publico
async function createPublicPaymentIntent() {
    showPublicPaymentMessage('Preparando pasarela segura de Stripe...', true);

    return apiRequest(`/api/postulantes/${encodeURIComponent(publicPayment.username)}/pago-matricula/intento`, {
        method: 'POST',
        body: JSON.stringify({
            observacion: 'Pago de matricula con tarjeta mediante Stripe.',
        }),
    });
}

async function mountStripeElements(stripeKey, clientSecret) {
    if (publicPayment.mounted) {
        return;
    }

    await loadStripeSdk();

    if (!window.Stripe) {
        throw new Error('No se pudo cargar Stripe.js. Revisa tu conexion a internet.');
    }

    publicPayment.stripe = window.Stripe(stripeKey);
    publicPayment.elements = publicPayment.stripe.elements({
        clientSecret,
        locale: 'es',
        appearance: {
            theme: 'stripe',
            variables: {
                colorPrimary: '#063c7a',
                colorText: '#07111f',
                colorDanger: '#b42318',
                fontFamily: 'Inter, system-ui, sans-serif',
                borderRadius: '8px',
            },
        },
    });

    publicPayment.paymentElement = publicPayment.elements.create('payment', {
        layout: {
            type: 'tabs',
            defaultCollapsed: false,
        },
        defaultValues: {
            billingDetails: {
                name: qs('#cardholderName')?.value.trim() || undefined,
            },
        },
    });
    publicPayment.paymentElement.mount('#payment-element');

    publicPayment.clientSecret = clientSecret;
    publicPayment.mounted = true;
    showPublicPaymentMessage('Ingresa los datos de tu tarjeta de prueba.', true);
}

// esta funcion procesa el pago publico
async function payPublicMatricula(event) {
    event.preventDefault();

    const button = qs('#publicPaymentButton');
    const holder = qs('#cardholderName')?.value.trim();

    if (!holder) {
        showPublicPaymentMessage('Ingresa el titular de la tarjeta.', false);
        return;
    }

    if (!publicPayment.elements) {
        showPublicPaymentMessage('La pasarela de Stripe aun no esta lista.', false);
        return;
    }

    setButtonLoading(button, true, 'Procesando pago...');
    showPublicPaymentMessage('Validando pago con Stripe...', true);

    try {
        const result = await publicPayment.stripe.confirmPayment({
            elements: publicPayment.elements,
            confirmParams: {
                return_url: `${window.location.origin}/registro-postulante`,
                payment_method_data: {
                    billing_details: {
                        name: holder,
                    },
                },
            },
            redirect: 'if_required',
        });

        if (result.error) {
            showPublicPaymentMessage(result.error.message || 'Stripe rechazo la transaccion.', false);
            return;
        }

        const paymentIntentId = result.paymentIntent?.id || publicPayment.paymentIntentId;

        if (!paymentIntentId) {
            showPublicPaymentMessage('Stripe no devolvio el identificador del pago.', false);
            return;
        }

        const confirmation = await apiRequest(`/api/postulantes/${encodeURIComponent(publicPayment.username)}/pago-matricula/confirmar`, {
            method: 'POST',
            body: JSON.stringify({
                payment_intent_id: paymentIntentId,
            }),
        });

        const paymentConfirmed = confirmation.pago?.estado === 'pagado';
        showPublicPaymentMessage(confirmation.message || 'Pago confirmado correctamente.', paymentConfirmed);
        showPublicPaymentDownload(confirmation.formulario_url);
        showPublicPaymentLogin(paymentConfirmed);
    } catch (error) {
        showPublicPaymentMessage(error.data?.message || error.message, false);
    } finally {
        setButtonLoading(button, false);
    }
}

// esta funcion carga el sdk de stripe
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

// esta funcion muestra el mensaje de pago publico
function showPublicPaymentMessage(message, success = false) {
    const output = qs('#publicPaymentOutput');

    if (!output) {
        return;
    }

    output.hidden = !message;
    output.textContent = message || '';
    output.classList.toggle('is-success', success);
}

function showPublicPaymentDownload(url) {
    const link = qs('#publicPaymentDownload');

    if (!link) {
        return;
    }

    link.hidden = !url;
    link.href = url || '#';
}

function showPublicPaymentLogin(visible) {
    const link = qs('#publicPaymentLogin');

    if (link) {
        link.hidden = !visible;
    }
}

// esta funcion reinicia el elemento de pago publico
function resetPublicPaymentElement() {
    if (publicPayment.paymentElement) {
        publicPayment.paymentElement.destroy();
    }

    publicPayment.stripe = null;
    publicPayment.elements = null;
    publicPayment.paymentElement = null;
    publicPayment.paymentIntentId = null;
    publicPayment.clientSecret = null;
    publicPayment.mounted = false;
    showPublicPaymentLogin(false);

    const container = qs('#payment-element');
    if (container) {
        container.innerHTML = '';
    }
}

function setPublicPaymentButtonText(text) {
    const button = qs('#publicPaymentButton');

    if (button) {
        button.textContent = text;
    }
}
