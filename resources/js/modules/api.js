export const qs = (selector, root = document) => root.querySelector(selector);
export const qsa = (selector, root = document) => Array.from(root.querySelectorAll(selector));

export function csrfToken() {
    return qs('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

export async function apiRequest(url, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const token = csrfToken();
    const csrfHeader = token ? { 'X-CSRF-TOKEN': token } : {};

    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...csrfHeader,
            ...options.headers,
        },
        ...options,
    });

    const text = await response.text();
    const data = text ? JSON.parse(text) : {};

    if (!response.ok) {
        const error = new Error(data.message || 'La solicitud no pudo completarse.');
        error.data = data;
        error.status = response.status;
        throw error;
    }

    registerMovement(url, method, data);

    return data;
}

export function formData(form) {
    return Object.fromEntries(new FormData(form).entries());
}

export function cleanPayload(payload) {
    return Object.fromEntries(
        Object.entries(payload).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    );
}

export function setMessage(selector, value) {
    const target = qs(selector);

    if (!target) {
        return;
    }

    target.textContent = typeof value === 'string'
        ? value
        : value?.message || 'Operacion realizada correctamente.';
}

export function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

export function numberFormat(value) {
    return new Intl.NumberFormat('es-BO').format(Number(value || 0));
}

export function statusClass(type) {
    return {
        pagado: 'is-paid',
        validado: 'is-validated',
        admitido: 'is-admitted',
        habilitado: 'is-admitted',
        habilitada: 'is-admitted',
        disponible: 'is-admitted',
        rechazado: 'is-rejected',
        inactiva: 'is-rejected',
        inactivo: 'is-rejected',
        aprobado: 'is-admitted',
        reprobado: 'is-rejected',
        observado: 'is-validated',
        pendiente_revision: 'is-validated',
        presente: 'is-admitted',
        retraso: 'is-validated',
        falta: 'is-rejected',
        propuesto: 'is-validated',
        confirmado: 'is-admitted',
        asignado: 'is-admitted',
        activo: 'is-admitted',
        activa: 'is-admitted',
        inactiva: 'is-rejected',
        lista_espera: 'is-validated',
        sin_opcion: 'is-rejected',
        inscrito: 'is-admitted',
        retirado: 'is-rejected',
    }[type] || '';
}

export function setButtonLoading(button, loading, text = 'Procesando...') {
    if (!button) {
        return;
    }

    if (loading) {
        button.dataset.originalText = button.textContent;
        button.disabled = true;
        button.textContent = text;
        return;
    }

    button.disabled = false;
    button.textContent = button.dataset.originalText || button.textContent;
}

export function validateForm(form) {
    if (!form) {
        return false;
    }

    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }

    return true;
}

function registerMovement(url, method, responseData) {
    if (method === 'GET' || String(url).includes('/api/bitacora')) {
        return;
    }

    const page = qs('[data-page]')?.dataset.page || 'portal';

    window.fetch('/api/bitacora/movimiento', {
        method: 'POST',
        credentials: 'same-origin',
        keepalive: true,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({
            accion: `${method.toLowerCase()}_api`,
            modulo: page,
            descripcion: responseData?.message || `Movimiento ${method} en ${url}`,
            datos: {
                endpoint: String(url),
                metodo: method,
            },
        }),
    }).catch(() => {});
}
