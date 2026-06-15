import {
    apiRequest,
    escapeHtml,
    formData,
    qs,
    qsa,
    setMessage,
    statusClass,
    validateForm,
} from './api';

let teachers = [];
let schedules = [];

export function initDocentesHorarios() {
    if (!qs('#teacherScheduleTable') && !qs('#teacherScheduleForm')) {
        return;
    }

    qs('[data-load-teacher-schedules]')?.addEventListener('click', loadTeacherSchedules);
    qs('#teacherScheduleSearch')?.addEventListener('input', (event) => renderSchedules(event.currentTarget.value));
    qs('#teacherScheduleSelect')?.addEventListener('change', syncTeacherOptions);
    qs('#teacherScheduleForm')?.addEventListener('submit', saveScheduleTeacher);

    loadTeacherSchedules();
}

async function loadTeacherSchedules() {
    try {
        const data = await apiRequest('/api/docentes-horarios');
        teachers = data.docentes || [];
        schedules = data.horarios || [];
        renderScheduleOptions();
        syncTeacherOptions();
        renderSchedules(qs('#teacherScheduleSearch')?.value || '');
    } catch (error) {
        setMessage('#teacherScheduleOutput', error.data || error.message);
        if (qs('#teacherScheduleTable')) {
            qs('#teacherScheduleTable').innerHTML = `<tr><td colspan="8">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
        }
    }
}

function renderScheduleOptions() {
    const select = qs('#teacherScheduleSelect');

    if (!select) {
        return;
    }

    select.innerHTML = schedules.length
        ? `<option value="">Selecciona horario</option>${schedules.map((schedule) => `<option value="${escapeHtml(schedule.id)}">${scheduleLabel(schedule)}</option>`).join('')}`
        : '<option value="">No hay horarios generados</option>';
}

function syncTeacherOptions() {
    const scheduleId = Number(qs('#teacherScheduleSelect')?.value || 0);
    const teacherSelect = qs('#teacherScheduleTeacherSelect');
    const hiddenId = qs('#teacherScheduleForm [name="id"]');
    const schedule = schedules.find((item) => Number(item.id) === scheduleId);

    if (!teacherSelect) {
        return;
    }

    if (hiddenId) {
        hiddenId.value = schedule?.id || '';
    }

    if (!schedule) {
        teacherSelect.innerHTML = '<option value="">Selecciona un horario primero</option>';
        return;
    }

    const availableTeachers = teachers.filter((teacher) => Array.isArray(teacher.materias_ids)
        && teacher.materias_ids.includes(schedule.materia?.id)
        && teacher.estado_profesional === 'habilitado');

    teacherSelect.innerHTML = availableTeachers.length
        ? `<option value="">Selecciona docente</option>${availableTeachers.map((teacher) => `<option value="${escapeHtml(teacher.username)}">${escapeHtml(teacher.nombre)} - ${escapeHtml(teacher.username)}</option>`).join('')}`
        : '<option value="">No hay docentes habilitados para esta materia</option>';
    teacherSelect.value = availableTeachers.some((teacher) => teacher.username === schedule.docente?.username)
        ? schedule.docente.username
        : '';
}

function renderSchedules(filter = '') {
    const table = qs('#teacherScheduleTable');
    const count = qs('#teacherScheduleCount');

    if (!table) {
        return;
    }

    const query = filter.trim().toLowerCase();
    const filtered = schedules.filter((schedule) => [
        schedule.dia?.nombre,
        schedule.grupo?.codigo,
        schedule.grupo?.turno,
        schedule.materia?.nombre,
        schedule.docente?.nombre,
        schedule.docente?.username,
        schedule.aula?.nro_aula,
        schedule.estado,
    ].filter(Boolean).join(' ').toLowerCase().includes(query));

    table.innerHTML = filtered.map((schedule) => `
        <tr>
            <td>${escapeHtml(schedule.dia?.nombre || 'Sin dia')}</td>
            <td>
                <strong>${escapeHtml(schedule.grupo?.codigo || 'Sin grupo')}</strong>
                <small>${escapeHtml(schedule.grupo?.turno || '')}</small>
            </td>
            <td>${escapeHtml(schedule.hora_inicio)} - ${escapeHtml(schedule.hora_fin)}</td>
            <td>
                <strong>${escapeHtml(schedule.materia?.nombre || 'Sin materia')}</strong>
                <small>${escapeHtml(schedule.materia?.id || '')}</small>
            </td>
            <td>Aula ${escapeHtml(schedule.aula?.nro_aula || '')}</td>
            <td>
                <strong>${escapeHtml(schedule.docente?.nombre || 'Sin docente')}</strong>
                <small>${escapeHtml(schedule.docente?.username || '')}</small>
            </td>
            <td><span class="status-pill ${statusClass(schedule.estado)}">${escapeHtml(schedule.estado || 'sin estado')}</span></td>
            <td class="table-actions">
                <button type="button" data-edit-teacher-schedule="${escapeHtml(schedule.id)}">Editar</button>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="8">No hay horarios generados.</td></tr>';

    if (count) {
        count.textContent = `${filtered.length} bloque(s) encontrado(s)`;
    }

    qsa('[data-edit-teacher-schedule]').forEach((button) => {
        button.addEventListener('click', () => {
            qs('#teacherScheduleSelect').value = button.dataset.editTeacherSchedule;
            syncTeacherOptions();
            qs('#teacherScheduleForm')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
}

function scheduleLabel(schedule) {
    return [
        schedule.grupo?.codigo,
        schedule.dia?.nombre,
        `${schedule.hora_inicio}-${schedule.hora_fin}`,
        schedule.materia?.nombre,
    ].filter(Boolean).map(escapeHtml).join(' | ');
}

async function saveScheduleTeacher(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const payload = formData(form);
    const id = payload.id || payload.horario_id;

    try {
        const data = await apiRequest(`/api/docentes-horarios/${encodeURIComponent(id)}`, {
            method: 'PUT',
            body: JSON.stringify({
                username_docente: payload.username_docente,
            }),
        });

        setMessage('#teacherScheduleOutput', data);
        await loadTeacherSchedules();
        qs('#teacherScheduleSelect').value = id;
        syncTeacherOptions();
    } catch (error) {
        setMessage('#teacherScheduleOutput', error.data || error.message);
    }
}
