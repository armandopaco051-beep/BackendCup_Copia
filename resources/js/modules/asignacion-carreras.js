import {
    apiRequest,
    escapeHtml,
    formData,
    qs,
    setButtonLoading,
    setMessage,
    statusClass,
    validateForm,
} from './api';

let assignments = [];
let summary = {};

export function initAsignacionCarreras() {
    if (!qs('#careerAssignmentTable') && !qs('#careerAssignmentForm')) {
        return;
    }

    qs('[data-load-career-assignments]')?.addEventListener('click', loadAssignments);
    qs('[data-generate-career-assignments]')?.addEventListener('click', () => qs('#careerAssignmentForm')?.requestSubmit());
    qs('#careerAssignmentForm')?.addEventListener('submit', generateAssignments);
    qs('#careerAssignmentSearch')?.addEventListener('input', (event) => renderAssignments(event.currentTarget.value));

    loadAssignments();
}

async function loadAssignments() {
    try {
        const data = await apiRequest('/api/asignacion-carreras');
        assignments = data.asignaciones || [];
        summary = data.resumen || {};
        renderSummary();
        renderQuotas();
        renderAssignments(qs('#careerAssignmentSearch')?.value || '');
    } catch (error) {
        setMessage('#careerAssignmentOutput', error.data || error.message);
        if (qs('#careerAssignmentTable')) {
            qs('#careerAssignmentTable').innerHTML = `<tr><td colspan="8">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
        }
    }
}

function renderSummary() {
    const target = qs('#careerAssignmentSummary');

    if (!target) {
        return;
    }

    target.innerHTML = `
        <article class="module-card schedule-summary-card">
            <span class="section-kicker">Evaluados</span>
            <h2>${escapeHtml(summary.total_evaluados ?? assignments.length)}</h2>
            <p>Postulantes con nota final.</p>
        </article>
        <article class="module-card schedule-summary-card">
            <span class="section-kicker">Asignados</span>
            <h2>${escapeHtml(summary.asignados ?? 0)}</h2>
            <p>Con carrera confirmada.</p>
        </article>
        <article class="module-card schedule-summary-card">
            <span class="section-kicker">Lista espera</span>
            <h2>${escapeHtml(summary.lista_espera ?? 0)}</h2>
            <p>Aprobados sin cupo.</p>
        </article>
    `;
}

function renderQuotas() {
    const target = qs('#careerQuotaCards');
    const count = qs('#careerQuotaCount');
    const careers = summary.carreras || [];

    if (!target) {
        return;
    }

    target.innerHTML = careers.map((career) => {
        const cupo = Number(career.cupo_maximo || 0);
        const assigned = Number(career.asignados || 0);
        const percent = cupo > 0 ? Math.min(Math.round((assigned / cupo) * 100), 100) : 0;

        return `
            <article class="career-quota-card">
                <div>
                    <strong>${escapeHtml(career.nombre)}</strong>
                    <small>${escapeHtml(career.codigo)}</small>
                </div>
                <span>${escapeHtml(assigned)} / ${escapeHtml(cupo)}</span>
                <div class="career-quota-bar"><i style="width: ${percent}%"></i></div>
                <small>${escapeHtml(career.disponibles ?? 0)} cupo(s) disponible(s)</small>
            </article>
        `;
    }).join('') || '<p class="module-note">No hay carreras registradas.</p>';

    if (count) {
        count.textContent = `${careers.length} carrera(s) con cupo configurado`;
    }
}

function renderAssignments(filter = '') {
    const table = qs('#careerAssignmentTable');
    const count = qs('#careerAssignmentCount');

    if (!table) {
        return;
    }

    const query = filter.trim().toLowerCase();
    const filtered = assignments.filter((item) => [
        item.postulante?.nombre,
        item.postulante?.ci,
        item.postulante?.username,
        item.primera_opcion?.nombre,
        item.segunda_opcion?.nombre,
        item.carrera_asignada?.nombre,
        item.estado,
        item.motivo,
    ].filter(Boolean).join(' ').toLowerCase().includes(query));

    table.innerHTML = filtered.map((item) => `
        <tr>
            <td>
                <strong>${escapeHtml(item.postulante?.nombre || item.postulante?.username)}</strong>
                <small>${escapeHtml(item.postulante?.ci || item.postulante?.username || '')}</small>
            </td>
            <td><strong>${escapeHtml(item.promedio_final ?? '-')}</strong></td>
            <td>${escapeHtml([item.nota3_promedio, item.nota2_promedio, item.nota1_promedio].filter((value) => value !== null && value !== undefined).join(' / '))}</td>
            <td>${careerText(item.primera_opcion)}</td>
            <td>${careerText(item.segunda_opcion)}</td>
            <td>
                <strong>${escapeHtml(item.carrera_asignada?.nombre || 'Sin asignar')}</strong>
                <small>${item.opcion_asignada ? `Opcion ${escapeHtml(item.opcion_asignada)}` : ''}</small>
            </td>
            <td><span class="status-pill ${statusClass(item.estado)}">${escapeHtml(item.estado || 'pendiente')}</span></td>
            <td>${escapeHtml(item.motivo || '')}</td>
        </tr>
    `).join('') || '<tr><td colspan="8">No hay asignaciones generadas.</td></tr>';

    if (count) {
        count.textContent = `${filtered.length} resultado(s) encontrado(s)`;
    }
}

function careerText(career) {
    if (!career) {
        return 'Sin opcion';
    }

    return `<strong>${escapeHtml(career.nombre)}</strong><small>${escapeHtml(career.codigo)}</small>`;
}

async function generateAssignments(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const button = form.querySelector('button[type="submit"]');
    const payload = formData(form);

    try {
        setButtonLoading(button, true, 'Generando...');
        const data = await apiRequest('/api/asignacion-carreras/generar', {
            method: 'POST',
            body: JSON.stringify({
                sobrescribir: Boolean(payload.sobrescribir),
            }),
        });

        setMessage('#careerAssignmentOutput', data);
        await loadAssignments();
    } catch (error) {
        setMessage('#careerAssignmentOutput', error.data || error.message);
    } finally {
        setButtonLoading(button, false);
    }
}
