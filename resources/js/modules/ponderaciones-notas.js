import {
    apiRequest,
    cleanPayload,
    escapeHtml,
    formData,
    qs,
    setButtonLoading,
    setMessage,
    statusClass,
    validateForm,
} from './api';

let weights = [];

export function initPonderacionesNotas() {
    if (!qs('#gradeWeightForm')) {
        return;
    }

    qs('[data-load-grade-weights]')?.addEventListener('click', loadWeights);
    qs('[data-recalculate-grades]')?.addEventListener('click', recalculateGrades);
    qs('#gradeWeightForm')?.addEventListener('submit', saveWeight);

    loadWeights();
}

async function loadWeights() {
    try {
        const data = await apiRequest('/api/ponderaciones-notas');
        weights = data.ponderaciones || [];
        renderActive(data.activa);
        renderWeights();
        setMessage('#gradeWeightOutput', 'Ponderaciones cargadas correctamente.');
    } catch (error) {
        setMessage('#gradeWeightOutput', error.data || error.message);
        if (qs('#gradeWeightTable')) {
            qs('#gradeWeightTable').innerHTML = `<tr><td colspan="7">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
        }
    }
}

async function saveWeight(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const payload = normalizePayload(cleanPayload(formData(form)));
    const total = Number(payload.nota1_porcentaje) + Number(payload.nota2_porcentaje) + Number(payload.nota3_porcentaje);

    if (Number(total.toFixed(2)) !== 100) {
        setMessage('#gradeWeightOutput', `La suma debe ser 100%. Actualmente suma ${total.toFixed(2)}%.`);
        return;
    }

    const button = form.querySelector('button[type="submit"]');
    setButtonLoading(button, true);

    try {
        const data = await apiRequest('/api/ponderaciones-notas', {
            method: 'POST',
            body: JSON.stringify(payload),
        });

        setMessage('#gradeWeightOutput', `${data.message} Recalculadas: ${data.calificaciones_recalculadas}.`);
        await loadWeights();
    } catch (error) {
        setMessage('#gradeWeightOutput', error.data || error.message);
    } finally {
        setButtonLoading(button, false);
    }
}

async function recalculateGrades() {
    try {
        const data = await apiRequest('/api/ponderaciones-notas/recalcular', {
            method: 'POST',
            body: JSON.stringify({}),
        });

        setMessage('#gradeWeightOutput', `${data.message} Recalculadas: ${data.calificaciones_recalculadas}.`);
        await loadWeights();
    } catch (error) {
        setMessage('#gradeWeightOutput', error.data || error.message);
    }
}

function normalizePayload(payload) {
    return {
        ...payload,
        nota1_porcentaje: Number(payload.nota1_porcentaje),
        nota2_porcentaje: Number(payload.nota2_porcentaje),
        nota3_porcentaje: Number(payload.nota3_porcentaje),
        recalcular: Boolean(payload.recalcular),
    };
}

function renderActive(active) {
    if (!active) {
        return;
    }

    qs('#gradeWeightActiveTitle').textContent = active.nombre || 'Ponderacion CUP';
    qs('#gradeWeightActiveMeta').textContent = `Nota 1: ${active.nota1_porcentaje}% | Nota 2: ${active.nota2_porcentaje}% | Nota 3: ${active.nota3_porcentaje}%`;
    qs('#gradeWeightActiveTags').innerHTML = `
        <span class="status-pill ${statusClass(active.estado)}">${escapeHtml(active.estado)}</span>
        <span class="status-pill is-admitted">Total ${escapeHtml(active.total)}%</span>
    `;

    const form = qs('#gradeWeightForm');
    if (form) {
        form.elements.nombre.value = active.nombre || 'Ponderacion CUP';
        form.elements.nota1_porcentaje.value = active.nota1_porcentaje ?? 30;
        form.elements.nota2_porcentaje.value = active.nota2_porcentaje ?? 30;
        form.elements.nota3_porcentaje.value = active.nota3_porcentaje ?? 40;
    }
}

function renderWeights() {
    const table = qs('#gradeWeightTable');
    const count = qs('#gradeWeightCount');

    if (!table) {
        return;
    }

    table.innerHTML = weights.map((item) => `
        <tr>
            <td><strong>${escapeHtml(item.nombre)}</strong></td>
            <td>${escapeHtml(item.nota1_porcentaje)}%</td>
            <td>${escapeHtml(item.nota2_porcentaje)}%</td>
            <td>${escapeHtml(item.nota3_porcentaje)}%</td>
            <td>${escapeHtml(item.total)}%</td>
            <td><span class="status-pill ${statusClass(item.estado)}">${escapeHtml(item.estado)}</span></td>
            <td>${escapeHtml(item.created_at || '-')}</td>
        </tr>
    `).join('') || '<tr><td colspan="7">No hay ponderaciones registradas.</td></tr>';

    if (count) {
        count.textContent = `${weights.length} ponderacion(es) registrada(s)`;
    }
}
