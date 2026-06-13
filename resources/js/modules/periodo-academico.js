import { apiRequest, cleanPayload, escapeHtml, formData, qs, qsa, setMessage, validateForm } from './api';

let periods = [];
let config = {};

export function initPeriodoAcademico() {
    if (!qs('#periodTable')) {
        return;
    }

    qs('[data-load-periods]')?.addEventListener('click', loadPeriods);
    qs('#periodForm')?.addEventListener('submit', savePeriod);
    qs('[data-clear-period]')?.addEventListener('click', resetPeriodForm);

    loadPeriods();
}

async function loadPeriods() {
    try {
        const data = await apiRequest('/api/periodos-academicos');
        periods = data.periodos || [];
        config = data.configuracion || {};
        applySchemaConfig();
        renderPeriods();
    } catch (error) {
        qs('#periodTable').innerHTML = `<tr><td colspan="6">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
    }
}

function applySchemaConfig() {
    const available = new Set(config.columnas_disponibles || []);
    const notice = qs('#periodSchemaNotice');

    qsa('[name^="fecha_"]').forEach((input) => {
        input.disabled = !available.has(input.name);
    });

    ['nombre', 'estado'].forEach((name) => {
        const input = qs(`[name="${name}"]`);
        if (input) {
            input.disabled = !available.has(name);
        }
    });

    if (notice) {
        notice.textContent = config.soporta_fechas
            ? 'El backend aplica la ventana de preinscripcion tambien para requisitos y pago.'
            : 'Tu tabla actual guarda solo semestre y anio. Agrega columnas de fechas para activar las ventanas.';
    }
}

function renderPeriods() {
    const table = qs('#periodTable');
    const count = qs('#periodCount');

    table.innerHTML = periods.map((period) => `
        <tr>
            <td>
                <strong>${escapeHtml(period.nombre || `Periodo CUP ${period.anio}-${period.semestre}`)}</strong>
                <small>ID ${escapeHtml(period.id)}</small>
            </td>
            <td>${escapeHtml(period.semestre)}</td>
            <td>${escapeHtml(period.anio)}</td>
            <td><span class="status-pill ${period.estado === 'activo' ? 'is-admitted' : ''}">${escapeHtml(period.estado || 'sin estado')}</span></td>
            <td>${escapeHtml(formatWindow(period.fecha_inicio_preinscripcion, period.fecha_fin_preinscripcion))}</td>
            <td class="table-actions">
                <button type="button" data-edit-period="${period.id}">Editar</button>
                <button type="button" data-delete-period="${period.id}">Eliminar</button>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="6">No hay periodos registrados.</td></tr>';

    if (count) {
        count.textContent = `${periods.length} periodo(s) registrado(s)`;
    }

    qsa('[data-edit-period]').forEach((button) => {
        button.addEventListener('click', () => fillPeriod(Number(button.dataset.editPeriod)));
    });

    qsa('[data-delete-period]').forEach((button) => {
        button.addEventListener('click', () => deletePeriod(Number(button.dataset.deletePeriod)));
    });
}

function formatWindow(start, end) {
    if (!start && !end) {
        return 'No configurado';
    }

    return `${start || '...'} - ${end || '...'}`;
}

function fillPeriod(id) {
    const form = qs('#periodForm');
    const period = periods.find((item) => Number(item.id) === Number(id));

    if (!form || !period) {
        return;
    }

    form.elements.id.value = period.id;
    form.elements.semestre.value = period.semestre;
    form.elements.anio.value = period.anio;

    [
        'nombre',
        'estado',
        'fecha_inicio_preinscripcion',
        'fecha_fin_preinscripcion',
    ].forEach((field) => {
        if (form.elements[field]) {
            form.elements[field].value = period[field] || '';
        }
    });

    qs('#periodForm button[type="submit"] span').textContent = 'Actualizar periodo';
    setMessage('#periodOutput', `Editando periodo ${period.nombre || period.id}.`);
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

async function savePeriod(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const payload = cleanPayload(formData(form));
    const id = payload.id;
    delete payload.id;
    mirrorProcessWindow(payload);

    try {
        const data = await apiRequest(id ? `/api/periodos-academicos/${id}` : '/api/periodos-academicos', {
            method: id ? 'PUT' : 'POST',
            body: JSON.stringify(payload),
        });

        setMessage('#periodOutput', data);
        resetPeriodForm();
        loadPeriods();
    } catch (error) {
        setMessage('#periodOutput', error.data || error.message);
    }
}

function mirrorProcessWindow(payload) {
    if (payload.fecha_inicio_preinscripcion) {
        payload.fecha_inicio_requisitos = payload.fecha_inicio_preinscripcion;
        payload.fecha_inicio_pago = payload.fecha_inicio_preinscripcion;
    }

    if (payload.fecha_fin_preinscripcion) {
        payload.fecha_fin_requisitos = payload.fecha_fin_preinscripcion;
        payload.fecha_fin_pago = payload.fecha_fin_preinscripcion;
    }
}

async function deletePeriod(id) {
    if (!id || !window.confirm('Eliminar este periodo academico?')) {
        return;
    }

    try {
        const data = await apiRequest(`/api/periodos-academicos/${id}`, {
            method: 'DELETE',
        });

        setMessage('#periodOutput', data);
        resetPeriodForm();
        loadPeriods();
    } catch (error) {
        setMessage('#periodOutput', error.data || error.message);
    }
}

function resetPeriodForm() {
    const form = qs('#periodForm');

    if (!form) {
        return;
    }

    form.reset();
    form.elements.id.value = '';
    qs('#periodForm button[type="submit"] span').textContent = 'Guardar periodo';
}
