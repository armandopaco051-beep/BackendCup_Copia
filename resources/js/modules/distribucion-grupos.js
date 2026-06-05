import { apiRequest, cleanPayload, escapeHtml, formData, numberFormat, qs, validateForm } from './api';

export function initDistribucionGrupos() {
    if (!qs('#distributionTable')) {
        return;
    }

    qs('[data-calculate-groups]')?.addEventListener('click', calculateGroups);
    qs('[data-generate-groups]')?.addEventListener('click', generateGroups);
    qs('#distributionForm')?.addEventListener('submit', (event) => {
        event.preventDefault();
        calculateGroups();
    });

    calculateGroups();
}

function distributionPayload() {
    const form = qs('#distributionForm');
    const payload = cleanPayload(form ? formData(form) : {});

    if (payload.periodo_id) {
        payload.periodo_id = Number(payload.periodo_id);
    }

    if (payload.cupo_maximo) {
        payload.cupo_maximo = Number(payload.cupo_maximo);
    }

    return payload;
}

async function calculateGroups() {
    const form = qs('#distributionForm');
    if (form && !validateForm(form)) {
        return;
    }

    const params = new URLSearchParams();
    Object.entries(distributionPayload()).forEach(([key, value]) => params.set(key, value));

    try {
        const data = await apiRequest(`/api/distribucion-grupos/calcular${params.toString() ? `?${params}` : ''}`);
        renderDistribution(data);
    } catch (error) {
        qs('#distributionTable').innerHTML = `<tr><td colspan="4">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
    }
}

async function generateGroups() {
    const form = qs('#distributionForm');
    if (form && !validateForm(form)) {
        return;
    }

    try {
        const data = await apiRequest('/api/distribucion-grupos/generar', {
            method: 'POST',
            body: JSON.stringify({ ...distributionPayload(), forzar: true }),
        });

        renderDistribution({ ...data, grupos_calculados: data.grupos || [] });
        qs('#distributionNotice').textContent = data.message || 'Grupos generados correctamente.';
    } catch (error) {
        qs('#distributionNotice').textContent = error.data?.message || error.message;
    }
}

function renderDistribution(data) {
    const groups = data.grupos_calculados || data.grupos || [];

    qs('#distributionTotal').textContent = numberFormat(data.total_postulantes);
    qs('#distributionCapacity').textContent = numberFormat(data.cupo_maximo);
    qs('#distributionGroupsCount').textContent = numberFormat(data.cantidad_grupos);
    qs('#distributionCount').textContent = `${groups.length} grupo(s) calculado(s)`;
    qs('#distributionPeriod').textContent = data.periodo
        ? `Periodo ${data.periodo.anio}-${data.periodo.semestre}`
        : 'Periodo sin definir';
    qs('#distributionNotice').textContent = data.periodo_cerrado
        ? 'Periodo cerrado: puedes generar grupos.'
        : 'Vista previa calculada. La generacion usa los postulantes habilitados actuales.';

    qs('#distributionTable').innerHTML = groups.map((group) => `
        <tr>
            <td>${escapeHtml(group.codigo)}</td>
            <td>${escapeHtml(group.turno)}</td>
            <td>${escapeHtml(group.cupo_maximo)}</td>
            <td>${escapeHtml(group.cantidad_postulantes)}</td>
        </tr>
    `).join('') || '<tr><td colspan="4">No hay postulantes habilitados para crear grupos.</td></tr>';
}
