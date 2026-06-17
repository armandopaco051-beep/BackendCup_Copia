import {
    apiRequest,
    cleanPayload,
    escapeHtml,
    formData,
    numberFormat,
    qs,
    qsa,
    setButtonLoading,
    statusClass,
    validateForm,
} from './api';

let savedGroups = [];

export async function initDistribucionGrupos() {
    if (!qs('#distributionTable')) {
        return;
    }

    qsa('[data-calculate-groups]').forEach((button) => {
        button.addEventListener('click', calculateGroups);
    });
    qsa('[data-generate-groups]').forEach((button) => {
        button.addEventListener('click', generateGroups);
    });
    qs('#distributionPeriodSelect')?.addEventListener('change', loadExistingDistribution);
    qs('#distributionEditForm')?.addEventListener('submit', updateGroup);
    qs('[data-cancel-group-edit]')?.addEventListener('click', closeGroupEditor);
    qs('#distributionForm')?.addEventListener('submit', (event) => {
        event.preventDefault();
        calculateGroups();
    });
    qs('#distributionTable')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-edit-group]');

        if (button) {
            openGroupEditor(button.dataset.editGroup);
        }
    });

    await loadDistributionPeriods();
    await loadExistingDistribution();
}

async function loadDistributionPeriods() {
    const select = qs('#distributionPeriodSelect');

    if (!select) {
        return;
    }

    try {
        const data = await apiRequest('/api/periodos-academicos');
        const periods = data.periodos || [];

        select.innerHTML = periods.length
            ? periods.map((period) => `
                <option value="${escapeHtml(period.id)}">
                    ${escapeHtml(period.nombre || `Periodo CUP ${period.anio}-${period.semestre}`)}
                </option>
            `).join('')
            : '<option value="">No hay periodos registrados</option>';
        const activePeriod = periods.find((period) => period.estado === 'activo');
        if (activePeriod) {
            select.value = String(activePeriod.id);
        }
        select.disabled = !periods.length;
    } catch (error) {
        select.innerHTML = '<option value="">No se pudieron cargar los periodos</option>';
        select.disabled = true;
        showNotice(error.data?.message || error.message, true);
    }
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

async function loadExistingDistribution() {
    const { periodo_id: periodId } = distributionPayload();

    if (!periodId) {
        renderSavedGroups([]);
        return;
    }

    hidePreview();
    closeGroupEditor();
    qs('#distributionTable').innerHTML = '<tr><td colspan="7">Cargando distribucion guardada...</td></tr>';

    try {
        const data = await apiRequest(`/api/distribucion-grupos?periodo_id=${encodeURIComponent(periodId)}`);
        savedGroups = data.grupos || [];
        renderSavedGroups(savedGroups);
        renderSummary(data);
        qs('#distributionPeriod').textContent = data.periodo?.nombre || 'Periodo sin definir';
        showNotice(savedGroups.length
            ? 'Esta es la distribucion guardada. Puedes editar sus grupos o calcular si hace falta ampliar cupos.'
            : 'Este periodo aun no tiene grupos. Calcula la distribucion para generar los necesarios.');
    } catch (error) {
        savedGroups = [];
        renderSavedGroups([]);
        showNotice(error.data?.message || error.message, true);
    }
}

async function calculateGroups(event) {
    const form = qs('#distributionForm');

    if (form && !validateForm(form)) {
        return;
    }

    const button = event?.currentTarget || qs('[data-calculate-groups]');
    const params = new URLSearchParams();
    Object.entries(distributionPayload()).forEach(([key, value]) => params.set(key, value));
    setButtonLoading(button, true, 'Calculando...');

    try {
        const data = await apiRequest(`/api/distribucion-grupos/calcular?${params}`);
        savedGroups = data.grupos_existentes || [];
        renderSavedGroups(savedGroups);
        renderCalculation(data);
        qs('#distributionPeriod').textContent = data.periodo?.nombre || 'Periodo sin definir';
    } catch (error) {
        showNotice(error.data?.message || error.message, true);
    } finally {
        setButtonLoading(button, false);
    }
}

async function generateGroups(event) {
    const form = qs('#distributionForm');

    if (form && !validateForm(form)) {
        return;
    }

    const button = event?.currentTarget || qs('[data-generate-groups]');
    setButtonLoading(button, true, 'Generando...');

    try {
        const data = await apiRequest('/api/distribucion-grupos/generar', {
            method: 'POST',
            body: JSON.stringify({ ...distributionPayload(), forzar: true }),
        });

        savedGroups = data.grupos || [];
        renderSavedGroups(savedGroups);
        renderSummary(data);
        hidePreview();
        showNotice(data.message || 'Distribucion guardada correctamente.');
    } catch (error) {
        showNotice(error.data?.message || error.message, true);
    } finally {
        setButtonLoading(button, false);
    }
}

function renderSavedGroups(groups) {
    qs('#distributionCount').textContent = `${groups.length} grupo(s) guardado(s)`;
    qs('#distributionTable').innerHTML = groups.map((group) => `
        <tr>
            <td><strong>${escapeHtml(group.codigo)}</strong></td>
            <td>${escapeHtml(capitalize(group.turno))}</td>
            <td>${numberFormat(group.cupo_maximo)}</td>
            <td>${numberFormat(group.ocupacion)}</td>
            <td>${numberFormat(group.cupos_disponibles)}</td>
            <td><span class="status-pill ${statusClass(group.estado)}">${escapeHtml(capitalize(group.estado))}</span></td>
            <td class="table-actions">
                <button type="button" data-edit-group="${escapeHtml(group.codigo)}">Editar</button>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="7">No hay grupos guardados para este periodo.</td></tr>';
}

function renderSummary(data) {
    const summary = data.resumen || {};

    qs('#distributionTotal').textContent = numberFormat(data.total_postulantes);
    qs('#distributionCapacity').textContent = numberFormat(summary.capacidad_total);
    qs('#distributionGroupsCount').textContent = numberFormat(summary.grupos_activos);
}

function renderCalculation(data) {
    const groups = data.grupos_calculados || [];
    const panel = qs('#distributionPreviewPanel');

    renderSummary({
        ...data,
        resumen: {
            capacidad_total: data.capacidad_existente,
            grupos_activos: (data.grupos_existentes || []).filter((group) => group.estado === 'activo').length,
        },
    });

    qs('#distributionPreviewCount').textContent = groups.length
        ? `${groups.length} grupo(s) nuevo(s) propuesto(s)`
        : 'No se necesitan grupos nuevos';
    qs('#distributionPreviewTable').innerHTML = groups.map((group) => `
        <tr>
            <td><strong>${escapeHtml(group.codigo)}</strong></td>
            <td>${escapeHtml(capitalize(group.turno))}</td>
            <td>${numberFormat(group.cupo_maximo)}</td>
            <td>${escapeHtml(group.descripcion)}</td>
        </tr>
    `).join('') || `
        <tr>
            <td colspan="4">La capacidad guardada cubre a los ${numberFormat(data.total_postulantes)} postulantes habilitados.</td>
        </tr>
    `;

    if (panel) {
        panel.hidden = false;
    }

    showNotice(groups.length
        ? `Faltan ${numberFormat(data.postulantes_sin_cupo)} cupos. Revisa la propuesta y pulsa Generar grupos.`
        : 'La distribucion guardada ya tiene capacidad suficiente. Puedes editar un grupo si deseas ampliar su cupo.');
}

function openGroupEditor(code) {
    const group = savedGroups.find((item) => item.codigo === code);
    const panel = qs('#distributionEditPanel');
    const form = qs('#distributionEditForm');

    if (!group || !panel || !form) {
        return;
    }

    form.elements.codigo.value = group.codigo;
    form.elements.cupo_maximo.value = group.cupo_maximo;
    form.elements.turno.value = group.turno;
    form.elements.descripcion.value = group.descripcion || '';
    form.elements.estado.value = group.estado || 'activo';
    qs('#distributionEditOccupancy').textContent = numberFormat(group.ocupacion);
    panel.hidden = false;
    panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function closeGroupEditor() {
    const panel = qs('#distributionEditPanel');

    if (panel) {
        panel.hidden = true;
    }
}

async function updateGroup(event) {
    event.preventDefault();
    const form = event.currentTarget;

    if (!validateForm(form)) {
        return;
    }

    const payload = formData(form);
    const code = payload.codigo;
    payload.cupo_maximo = Number(payload.cupo_maximo);
    delete payload.codigo;
    const button = form.querySelector('button[type="submit"]');
    setButtonLoading(button, true, 'Guardando...');

    try {
        const data = await apiRequest(`/api/distribucion-grupos/${encodeURIComponent(code)}`, {
            method: 'PUT',
            body: JSON.stringify(payload),
        });

        showNotice(data.message || 'Grupo actualizado correctamente.');
        closeGroupEditor();
        await loadExistingDistribution();
    } catch (error) {
        const validation = error.data?.errors
            ? Object.values(error.data.errors).flat().join(' ')
            : null;
        showNotice(validation || error.data?.message || error.message, true);
    } finally {
        setButtonLoading(button, false);
    }
}

function hidePreview() {
    const panel = qs('#distributionPreviewPanel');

    if (panel) {
        panel.hidden = true;
    }
}

function showNotice(message, isError = false) {
    const notice = qs('#distributionNotice');

    if (!notice) {
        return;
    }

    notice.textContent = message;
    notice.classList.toggle('is-error', isError);
}

function capitalize(value) {
    const text = String(value || '');

    return text ? text.charAt(0).toUpperCase() + text.slice(1) : '';
}
