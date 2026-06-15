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

let groups = [];
let applicants = [];
let enrollments = [];

export function initPostulantesGrupos() {
    if (!qs('#studentGroupForm') && !qs('#studentGroupTable')) {
        return;
    }

    qs('[data-load-student-groups]')?.addEventListener('click', loadStudentGroups);
    qs('#studentGroupForm')?.addEventListener('submit', saveStudentGroup);
    qs('#studentGroupSearch')?.addEventListener('input', (event) => renderEnrollments(event.currentTarget.value));

    loadStudentGroups();
}

async function loadStudentGroups() {
    try {
        const data = await apiRequest('/api/postulantes-grupos');
        groups = data.grupos || [];
        applicants = data.postulantes_disponibles || [];
        enrollments = data.inscripciones || [];

        renderApplicants();
        renderGroups();
        renderGroupQuotas();
        renderEnrollments(qs('#studentGroupSearch')?.value || '');
    } catch (error) {
        setMessage('#studentGroupOutput', error.data || error.message);
        if (qs('#studentGroupTable')) {
            qs('#studentGroupTable').innerHTML = `<tr><td colspan="6">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
        }
    }
}

function renderApplicants() {
    const select = qs('#studentGroupApplicantSelect');

    if (!select) {
        return;
    }

    select.innerHTML = applicants.length
        ? `<option value="">Selecciona postulante</option>${applicants.map((item) => `<option value="${escapeHtml(item.username)}">${escapeHtml(item.nombre)} - ${escapeHtml(item.ci || item.username)}</option>`).join('')}`
        : '<option value="">No hay postulantes habilitados disponibles</option>';
}

function renderGroups() {
    const select = qs('#studentGroupSelect');

    if (!select) {
        return;
    }

    const availableGroups = groups.filter((group) => group.estado === 'activo' && Number(group.cupos_disponibles) > 0);

    select.innerHTML = availableGroups.length
        ? `<option value="">Selecciona grupo</option>${availableGroups.map((group) => `<option value="${escapeHtml(group.codigo)}">${escapeHtml(group.codigo)} - ${escapeHtml(group.turno || '')} (${escapeHtml(group.cupos_disponibles)} cupos)</option>`).join('')}`
        : '<option value="">No hay grupos con cupos</option>';
}

function renderGroupQuotas() {
    const target = qs('#studentGroupQuotaCards');
    const count = qs('#studentGroupQuotaCount');

    if (!target) {
        return;
    }

    target.innerHTML = groups.map((group) => `
        <article class="career-quota-card">
            <div>
                <strong>${escapeHtml(group.codigo)}</strong>
                <small>${escapeHtml(group.turno || group.descripcion || '')}</small>
            </div>
            <span>${escapeHtml(group.ocupacion)} / ${escapeHtml(group.cupo_maximo)}</span>
            <div class="career-quota-bar"><i style="width: ${Number(group.porcentaje_uso || 0)}%"></i></div>
            <small>${escapeHtml(group.cupos_disponibles)} cupo(s) disponible(s)</small>
        </article>
    `).join('') || '<p class="module-note">No hay grupos registrados.</p>';

    if (count) {
        count.textContent = `${groups.length} grupo(s) encontrado(s)`;
    }
}

function renderEnrollments(filter = '') {
    const table = qs('#studentGroupTable');
    const count = qs('#studentGroupCount');

    if (!table) {
        return;
    }

    const query = filter.trim().toLowerCase();
    const filtered = enrollments.filter((item) => [
        item.postulante?.nombre,
        item.postulante?.ci,
        item.username_postulante,
        item.id_grupo,
        item.grupo?.turno,
        item.estado,
    ].filter(Boolean).join(' ').toLowerCase().includes(query));

    table.innerHTML = filtered.map((item) => `
        <tr>
            <td>
                <strong>${escapeHtml(item.postulante?.nombre || item.username_postulante)}</strong>
                <small>${escapeHtml(item.postulante?.ci || item.username_postulante)}</small>
            </td>
            <td>
                <strong>${escapeHtml(item.id_grupo)}</strong>
                <small>${escapeHtml(item.grupo?.descripcion || '')}</small>
            </td>
            <td>${escapeHtml(item.grupo?.turno || '-')}</td>
            <td>${escapeHtml(item.id_periodo_academico || 'Actual')}</td>
            <td><span class="status-pill ${statusClass(item.estado)}">${escapeHtml(item.estado)}</span></td>
            <td class="table-actions">
                <button type="button" data-remove-student-group="${escapeHtml(item.username_postulante)}" data-group="${escapeHtml(item.id_grupo)}">Retirar</button>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="6">No hay estudiantes inscritos en grupos.</td></tr>';

    if (count) {
        count.textContent = `${filtered.length} inscripcion(es) encontrada(s)`;
    }

    qsa('[data-remove-student-group]').forEach((button) => {
        button.addEventListener('click', () => removeStudentGroup(button.dataset.removeStudentGroup, button.dataset.group));
    });
}

async function saveStudentGroup(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const button = form.querySelector('button[type="submit"]');
    const payload = formData(form);

    try {
        setButtonLoading(button, true, 'Inscribiendo...');
        const data = await apiRequest('/api/postulantes-grupos', {
            method: 'POST',
            body: JSON.stringify(payload),
        });

        form.reset();
        setMessage('#studentGroupOutput', data);
        await loadStudentGroups();
    } catch (error) {
        setMessage('#studentGroupOutput', error.data || error.message);
    } finally {
        setButtonLoading(button, false);
    }
}

async function removeStudentGroup(username, group) {
    if (!window.confirm(`Retirar ${username} del grupo ${group}?`)) {
        return;
    }

    try {
        const data = await apiRequest(`/api/postulantes-grupos/${encodeURIComponent(username)}/${encodeURIComponent(group)}`, {
            method: 'DELETE',
        });

        setMessage('#studentGroupOutput', data);
        await loadStudentGroups();
    } catch (error) {
        setMessage('#studentGroupOutput', error.data || error.message);
    }
}
