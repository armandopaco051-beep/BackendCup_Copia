import { apiRequest, cleanPayload, escapeHtml, formData, qs, qsa, validateForm } from './api';

let periods = [];
let config = {};

export function initPeriodoAcademico() {
    if (!qs('#periodTable')) {
        return;
    }

    qs('[data-load-periods]')?.addEventListener('click', loadPeriods);
    qs('#periodForm')?.addEventListener('submit', savePeriod);

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
        qs('#periodTable').innerHTML = `<tr><td colspan="7">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
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
            ? 'El backend acepta fechas y estado del periodo.'
            : 'Tu tabla actual guarda solo semestre y año. Agrega columnas de fechas para activar las ventanas.';
    }
}

function renderPeriods() {
    const table = qs('#periodTable');
    const count = qs('#periodCount');

    table.innerHTML = periods.map((period) => `
        <tr>
            <td>${escapeHtml(period.id)}</td>
            <td>${escapeHtml(period.nombre || `Periodo ${period.anio}-${period.semestre}`)}</td>
            <td>${escapeHtml(period.semestre)}</td>
            <td>${escapeHtml(period.anio)}</td>
            <td>${escapeHtml(period.estado || 'sin estado')}</td>
            <td>${escapeHtml(formatWindow(period.fecha_inicio_preinscripcion, period.fecha_fin_preinscripcion))}</td>
            <td class="table-actions"><button type="button" data-edit-period="${period.id}">Editar</button></td>
        </tr>
    `).join('') || '<tr><td colspan="7">No hay periodos registrados.</td></tr>';

    if (count) {
        count.textContent = `${periods.length} periodo(s) registrado(s)`;
    }

    qsa('[data-edit-period]').forEach((button) => {
        button.addEventListener('click', () => fillPeriod(Number(button.dataset.editPeriod)));
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
        'fecha_inicio_requisitos',
        'fecha_fin_requisitos',
        'fecha_inicio_pago',
        'fecha_fin_pago',
    ].forEach((field) => {
        if (form.elements[field]) {
            form.elements[field].value = period[field] || '';
        }
    });
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

    try {
        await apiRequest(id ? `/api/periodos-academicos/${id}` : '/api/periodos-academicos', {
            method: id ? 'PUT' : 'POST',
            body: JSON.stringify(payload),
        });

        form.reset();
        loadPeriods();
    } catch (error) {
        const notice = qs('#periodSchemaNotice');
        if (notice) {
            notice.textContent = error.data?.message || error.message;
        }
    }
}
