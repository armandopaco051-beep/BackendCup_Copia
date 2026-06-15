import {
    apiRequest,
    escapeHtml,
    numberFormat,
    qs,
    setButtonLoading,
    statusClass,
} from './api';

let teacherSchedule = [];

export function initDocentePanel() {
    if (!qs('[data-page="docente"]')) {
        return;
    }

    qs('[data-refresh-teacher-panel]')?.addEventListener('click', loadTeacherPanel);
    qs('#teacherScheduleGroup')?.addEventListener('change', handleGroupChange);
    qs('#teacherScheduleDay')?.addEventListener('change', renderTeacherSchedule);
    loadTeacherPanel();
}

async function loadTeacherPanel(event) {
    const button = event?.currentTarget;
    setButtonLoading(button, true, 'Actualizando...');

    try {
        const data = await apiRequest('/api/docente/mi-panel');
        teacherSchedule = data.horario || [];
        renderTeacherSummary(data.resumen || {});
        renderGroupOptions();
        renderDayOptions();
        renderTeacherSchedule();
    } catch (error) {
        const message = error.status === 404
            ? 'Tu cuenta no esta vinculada con un perfil docente.'
            : (error.data?.message || error.message);
        qs('#teacherScheduleCount').textContent = message;
        qs('#teacherScheduleBody').innerHTML = `<tr><td colspan="8">${escapeHtml(message)}</td></tr>`;
    } finally {
        setButtonLoading(button, false);
    }
}

function renderTeacherSummary(summary) {
    qs('#teacherScheduleBlocks').textContent = numberFormat(summary.bloques);
    qs('#teacherScheduleGroups').textContent = numberFormat(summary.grupos);
    qs('#teacherScheduleSubjects').textContent = numberFormat(summary.materias);
    qs('#teacherScheduleHours').textContent = formatHours(summary.horas_semanales);
}

function renderGroupOptions() {
    const select = qs('#teacherScheduleGroup');
    const previous = select?.value || '';

    if (!select) {
        return;
    }

    const groups = [...new Set(teacherSchedule.map((item) => item.grupo).filter(Boolean))];

    if (!groups.length) {
        select.innerHTML = '<option value="">Sin grupos asignados</option>';
        select.disabled = true;
        return;
    }

    select.disabled = false;
    select.innerHTML = groups
        .map((group) => `<option value="${escapeHtml(group)}">${escapeHtml(group)}</option>`)
        .join('');
    select.value = groups.includes(previous) ? previous : groups[0];
}

function renderDayOptions() {
    const select = qs('#teacherScheduleDay');
    const previous = select?.value || '';
    const selectedGroup = qs('#teacherScheduleGroup')?.value || '';

    if (!select) {
        return;
    }

    const days = [...new Set(
        teacherSchedule
            .filter((item) => !selectedGroup || item.grupo === selectedGroup)
            .map((item) => item.dia)
            .filter(Boolean),
    )];
    select.innerHTML = `
        <option value="">Todos los dias</option>
        ${days.map((day) => `<option value="${escapeHtml(day)}">${escapeHtml(day)}</option>`).join('')}
    `;

    if (days.includes(previous)) {
        select.value = previous;
    }
}

function handleGroupChange() {
    renderDayOptions();
    renderTeacherSchedule();
}

function renderTeacherSchedule() {
    const target = qs('#teacherScheduleBody');
    const selectedGroup = qs('#teacherScheduleGroup')?.value || '';
    const selectedDay = qs('#teacherScheduleDay')?.value || '';
    const rows = teacherSchedule.filter((item) => (
        (!selectedGroup || item.grupo === selectedGroup)
        && (!selectedDay || item.dia === selectedDay)
    ));

    const groupLabel = selectedGroup ? ` de ${selectedGroup}` : '';
    qs('#teacherScheduleCount').textContent = `${rows.length} bloque(s) mostrado(s)${groupLabel}`;

    target.innerHTML = rows.map((item) => `
        <tr>
            <td><strong>${escapeHtml(item.dia)}</strong></td>
            <td>
                <strong>${escapeHtml(item.hora_inicio)} - ${escapeHtml(item.hora_fin)}</strong>
                <small>1 h 15 min</small>
            </td>
            <td>
                <strong>${escapeHtml(item.materia)}</strong>
                <small>${escapeHtml(item.materia_id)}</small>
            </td>
            <td>
                <strong>${escapeHtml(item.grupo)}</strong>
                <small>${escapeHtml(item.grupo_descripcion || '')}</small>
            </td>
            <td>${escapeHtml(capitalize(item.turno))}</td>
            <td>${classroomText(item)}</td>
            <td>${escapeHtml(item.periodo)}</td>
            <td><span class="status-pill ${statusClass(item.estado)}">${escapeHtml(capitalize(item.estado))}</span></td>
        </tr>
    `).join('') || '<tr><td colspan="8">No tienes bloques asignados para el filtro seleccionado.</td></tr>';
}

function classroomText(item) {
    if (!item.aula) {
        return 'Sin aula asignada';
    }

    return `
        <strong>Aula ${escapeHtml(item.aula)}</strong>
        <small>${escapeHtml(item.tipo_aula || 'Aula')} | Piso ${escapeHtml(item.piso || 'sin definir')}</small>
    `;
}

function formatHours(value) {
    const number = Number(value || 0);

    return Number.isInteger(number) ? String(number) : number.toFixed(2);
}

function capitalize(value) {
    const text = String(value || '');

    return text ? text.charAt(0).toUpperCase() + text.slice(1) : '';
}
