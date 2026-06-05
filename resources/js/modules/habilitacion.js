import { apiRequest, escapeHtml, formData, numberFormat, qs, qsa, setMessage, statusClass } from './api';

let candidates = [];
let selectedCandidate = null;

export function initHabilitacion() {
    if (!qs('#habilitationTable')) {
        return;
    }

    qs('[data-load-habilitations]')?.addEventListener('click', loadHabilitations);
    qs('#habilitationSearch')?.addEventListener('input', (event) => renderCandidates(event.currentTarget.value));
    qs('#enableApplicantForm')?.addEventListener('submit', enableApplicant);

    loadHabilitations();
}

async function loadHabilitations() {
    try {
        const data = await apiRequest('/api/habilitaciones');
        candidates = data.candidatos || [];
        renderSummary(data.resumen || {});
        renderCandidates(qs('#habilitationSearch')?.value || '');

        if (candidates[0]) {
            selectCandidate(candidates[0].username);
        }
    } catch (error) {
        qs('#habilitationTable').innerHTML = `<tr><td colspan="6">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
    }
}

function renderSummary(summary) {
    qs('#habilitationTotal').textContent = numberFormat(summary.total);
    qs('#habilitationReady').textContent = numberFormat(summary.listos);
    qs('#habilitationEnabled').textContent = numberFormat(summary.habilitados);
    qs('#habilitationReview').textContent = numberFormat(summary.en_revision);
    qs('#habilitationReadyBadge').textContent = `${numberFormat(summary.listos)} listo(s) para habilitar`;
}

function renderCandidates(filter = '') {
    const table = qs('#habilitationTable');
    const count = qs('#habilitationCount');
    const query = filter.trim().toLowerCase();

    const filtered = candidates.filter((candidate) => [
        candidate.folio,
        candidate.username,
        candidate.ci,
        candidate.nombre,
        candidate.carrera,
        candidate.estado_postulante,
    ].filter(Boolean).join(' ').toLowerCase().includes(query));

    table.innerHTML = filtered.map((candidate) => {
        const enabled = candidate.estado_postulante === 'habilitado';
        const ready = candidate.puede_habilitarse && !enabled;
        const label = enabled ? 'Habilitado' : ready ? 'Listo' : 'En revision';
        const klass = enabled ? 'is-admitted' : ready ? 'is-paid' : '';

        return `
            <tr class="${selectedCandidate?.username === candidate.username ? 'is-selected-row' : ''}">
                <td>${escapeHtml(candidate.folio)}</td>
                <td><strong>${escapeHtml(candidate.nombre)}</strong></td>
                <td>${escapeHtml(candidate.validaciones?.requisitos_fisicos || 'pendiente')}</td>
                <td>${escapeHtml(candidate.validaciones?.pago_matricula || 'pendiente')}</td>
                <td><span class="status-pill ${klass}">${escapeHtml(label)}</span></td>
                <td>
                    <button class="habilitation-row-action ${enabled ? 'is-muted' : ''}" type="button" data-habilitation-user="${escapeHtml(candidate.username)}">
                        ${enabled ? 'Habilitado' : 'Revisar'}
                    </button>
                </td>
            </tr>
        `;
    }).join('') || '<tr><td colspan="6">No hay candidatos registrados.</td></tr>';

    if (count) {
        count.textContent = `${filtered.length} candidato(s) encontrado(s)`;
    }

    qsa('[data-habilitation-user]').forEach((button) => {
        button.addEventListener('click', () => selectCandidate(button.dataset.habilitationUser));
    });
}

function selectCandidate(username) {
    selectedCandidate = candidates.find((candidate) => candidate.username === username) || null;

    if (!selectedCandidate) {
        return;
    }

    qs('#habilitationUsername').value = selectedCandidate.username;
    qs('#habilitationDetailName').textContent = selectedCandidate.nombre;
    qs('#habilitationDetailMeta').textContent = `${selectedCandidate.folio} · ${selectedCandidate.carrera} · CI ${selectedCandidate.ci}`;
    qs('#habilitationDetailRequirements').textContent = selectedCandidate.validaciones?.requisitos_fisicos || 'pendiente';
    qs('#habilitationDetailPayment').textContent = selectedCandidate.validaciones?.pago_matricula || 'pendiente';
    qs('#habilitationDetailStatus').textContent = selectedCandidate.estado_postulante || 'en revision';

    const submit = qs('#enableApplicantForm button[type="submit"]');

    if (submit) {
        const enabled = selectedCandidate.estado_postulante === 'habilitado';
        submit.disabled = enabled || !selectedCandidate.puede_habilitarse;
        submit.querySelector('span').textContent = enabled
            ? 'Postulante habilitado'
            : selectedCandidate.puede_habilitarse ? 'Habilitar postulante' : 'Faltan validaciones';
    }

    showEnableMessage('', false);
    renderCandidates(qs('#habilitationSearch')?.value || '');
}

async function enableApplicant(event) {
    event.preventDefault();

    const values = formData(event.currentTarget);
    const username = values.username || selectedCandidate?.username;

    if (!username) {
        showEnableMessage('Selecciona un postulante antes de habilitar.', false);
        return;
    }

    try {
        const data = await apiRequest(`/api/postulantes/${encodeURIComponent(username)}/habilitacion`, {
            method: 'POST',
            body: JSON.stringify({ observacion: values.observacion }),
        });

        showEnableMessage(formatEnableMessage(data), true);
        loadHabilitations();
    } catch (error) {
        showEnableMessage(error.data?.message || error.message, false);
    }
}

function showEnableMessage(message, success = false) {
    setMessage('#enableOutput', message);

    const output = qs('#enableOutput');

    if (!output) {
        return;
    }

    output.hidden = !message;
    output.classList.toggle('is-success', success);
}

function formatEnableMessage(data) {
    const credentials = data.credenciales || {};
    const details = [
        data.message || 'Postulante habilitado correctamente.',
        credentials.username ? `Usuario: ${credentials.username}` : '',
        credentials.correo ? `Correo: ${credentials.correo}` : '',
        credentials.aviso || '',
        credentials.password_temporal ? `Password temporal local: ${credentials.password_temporal}` : '',
    ].filter(Boolean);

    return details.join(' ');
}
