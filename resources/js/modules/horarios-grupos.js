import {
    apiRequest,
    escapeHtml,
    formData,
    qs,
    qsa,
    setButtonLoading,
    setMessage,
    statusClass,
    validateForm,
} from './api';

let schedules = [];
let options = {};
let currentPeriodId = '';

export function initHorariosGrupos() {
    if (!qs('#scheduleTable') && !qs('#scheduleGenerateForm')) {
        return;
    }

    qs('[data-load-schedules]')?.addEventListener('click', loadScheduleData);
    qs('[data-confirm-schedules]')?.addEventListener('click', confirmSchedules);
    qs('#scheduleGenerateForm')?.addEventListener('submit', generateSchedules);
    qs('#scheduleSearch')?.addEventListener('input', (event) => renderSchedules(event.currentTarget.value));

    loadScheduleData();
}

async function loadScheduleData() {
    try {
        const [optionsData, scheduleData] = await Promise.all([
            apiRequest('/api/horarios-grupos/opciones'),
            apiRequest('/api/horarios-grupos'),
        ]);

        options = optionsData;
        schedules = scheduleData.horarios || [];
        currentPeriodId = optionsData.periodo?.id || scheduleData.periodo?.id || '';

        renderPeriodSelect();
        renderOptions();
        renderSummary(scheduleData.resumen || {});
        renderSchedules(qs('#scheduleSearch')?.value || '');
    } catch (error) {
        setMessage('#scheduleOutput', error.data || error.message);
        if (qs('#scheduleTable')) {
            qs('#scheduleTable').innerHTML = `<tr><td colspan="9">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
        }
    }
}

function renderPeriodSelect() {
    const select = qs('#schedulePeriodSelect');

    if (!select) {
        return;
    }

    const period = options.periodo;
    select.innerHTML = period
        ? `<option value="${escapeHtml(period.id)}">${escapeHtml(period.nombre || `Periodo ${period.id}`)}</option>`
        : '<option value="">Periodo actual</option>';
    select.value = period?.id || '';
}

function renderOptions() {
    const target = qs('#scheduleOptions');

    if (!target) {
        return;
    }

    const groups = options.grupos || [];
    const subjects = options.materias || [];
    const classrooms = options.aulas || [];

    target.innerHTML = `
        <span><strong>${groups.length}</strong> grupo(s) activo(s)</span>
        <span><strong>${subjects.length}</strong> materia(s) habilitada(s)</span>
        <span><strong>${classrooms.length}</strong> aula(s) disponible(s)</span>
        <span><strong>5</strong> dias academicos</span>
    `;
}

function renderSummary(summary = {}) {
    const target = qs('#scheduleSummary');

    if (!target) {
        return;
    }

    const total = summary.total_bloques ?? schedules.length;
    const proposed = summary.propuestos ?? schedules.filter((item) => item.estado === 'propuesto').length;
    const confirmed = summary.confirmados ?? schedules.filter((item) => item.estado === 'confirmado').length;

    target.innerHTML = `
        <article class="module-card schedule-summary-card">
            <span class="section-kicker">Bloques generados</span>
            <h2>${escapeHtml(total)}</h2>
            <p>Materia, docente, aula y grupo.</p>
        </article>
        <article class="module-card schedule-summary-card">
            <span class="section-kicker">Propuestos</span>
            <h2>${escapeHtml(proposed)}</h2>
            <p>Listos para revisar.</p>
        </article>
        <article class="module-card schedule-summary-card">
            <span class="section-kicker">Confirmados</span>
            <h2>${escapeHtml(confirmed)}</h2>
            <p>Disponibles para clases.</p>
        </article>
    `;
}

function renderSchedules(filter = '') {
    const table = qs('#scheduleTable');
    const count = qs('#scheduleCount');

    if (!table) {
        return;
    }

    const query = filter.trim().toLowerCase();
    const filtered = schedules.filter((item) => [
        item.dia?.nombre,
        item.grupo?.codigo,
        item.turno,
        item.materia?.nombre,
        item.aula?.nro_aula,
        item.aula?.piso,
        item.docente?.nombre,
        item.docente?.username,
        item.estado,
    ].filter(Boolean).join(' ').toLowerCase().includes(query));

    table.innerHTML = filtered.map((item) => `
        <tr>
            <td>${escapeHtml(item.dia?.nombre || 'Sin dia')}</td>
            <td>
                <strong>${escapeHtml(item.grupo?.codigo || 'Sin grupo')}</strong>
                <small>${escapeHtml(item.grupo?.descripcion || '')}</small>
            </td>
            <td>${escapeHtml(item.turno || 'Sin turno')}</td>
            <td>${escapeHtml(item.hora_inicio)} - ${escapeHtml(item.hora_fin)}</td>
            <td>
                <strong>${escapeHtml(item.materia?.nombre || 'Sin materia')}</strong>
                <small>${escapeHtml(item.materia?.id || '')}</small>
            </td>
            <td>
                <strong>Aula ${escapeHtml(item.aula?.nro_aula || '')}</strong>
                <small>${escapeHtml(item.aula?.tipo || '')} ${escapeHtml(item.aula?.piso || '')}</small>
            </td>
            <td>
                <strong>${escapeHtml(item.docente?.nombre || item.docente?.username || 'Sin docente')}</strong>
                <small>${escapeHtml(item.docente?.username || '')}</small>
            </td>
            <td><span class="status-pill ${statusClass(item.estado)}">${escapeHtml(item.estado || 'sin estado')}</span></td>
            <td class="table-actions">
                ${item.estado === 'propuesto' ? `<button type="button" data-delete-schedule="${escapeHtml(item.id)}">Eliminar</button>` : '<span class="status-pill is-admitted">Fijo</span>'}
            </td>
        </tr>
    `).join('') || '<tr><td colspan="9">No hay horarios generados.</td></tr>';

    if (count) {
        count.textContent = `${filtered.length} bloque(s) encontrado(s)`;
    }

    qsa('[data-delete-schedule]').forEach((button) => {
        button.addEventListener('click', () => deleteSchedule(button.dataset.deleteSchedule));
    });
}

async function generateSchedules(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const button = form.querySelector('button[type="submit"]');
    const payload = formData(form);

    try {
        setButtonLoading(button, true, 'Generando...');
        const data = await apiRequest('/api/horarios-grupos/generar', {
            method: 'POST',
            body: JSON.stringify({
                periodo_id: payload.periodo_id || currentPeriodId || null,
                sobrescribir: Boolean(payload.sobrescribir),
            }),
        });

        setMessage('#scheduleOutput', data);
        await loadScheduleData();
    } catch (error) {
        setMessage('#scheduleOutput', error.data || error.message);
    } finally {
        setButtonLoading(button, false);
    }
}

async function confirmSchedules(event) {
    const button = event.currentTarget;

    try {
        setButtonLoading(button, true, 'Confirmando...');
        const data = await apiRequest('/api/horarios-grupos/confirmar', {
            method: 'POST',
            body: JSON.stringify({
                periodo_id: qs('#schedulePeriodSelect')?.value || currentPeriodId || null,
            }),
        });

        setMessage('#scheduleOutput', data);
        await loadScheduleData();
    } catch (error) {
        setMessage('#scheduleOutput', error.data || error.message);
    } finally {
        setButtonLoading(button, false);
    }
}

async function deleteSchedule(id) {
    try {
        const data = await apiRequest(`/api/horarios-grupos/${encodeURIComponent(id)}`, {
            method: 'DELETE',
        });

        setMessage('#scheduleOutput', data);
        await loadScheduleData();
    } catch (error) {
        setMessage('#scheduleOutput', error.data || error.message);
    }
}
