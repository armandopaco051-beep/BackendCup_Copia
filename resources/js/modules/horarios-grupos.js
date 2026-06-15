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
    qs('[data-clear-schedule-edit]')?.addEventListener('click', clearScheduleForm);
    qs('#scheduleGenerateForm')?.addEventListener('submit', generateSchedules);
    qs('#scheduleEditForm')?.addEventListener('submit', saveSchedule);
    qs('#scheduleSubjectSelect')?.addEventListener('change', renderTeacherOptions);
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
        renderEditorOptions();
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
    const days = options.dias || [];

    target.innerHTML = `
        <span><strong>${groups.length}</strong> grupo(s) activo(s)</span>
        <span><strong>${subjects.length}</strong> materia(s) habilitada(s)</span>
        <span><strong>${classrooms.length}</strong> aula(s) disponible(s)</span>
        <span><strong>${days.length || 5}</strong> dias academicos</span>
    `;
}

function renderEditorOptions() {
    const groupSelect = qs('#scheduleGroupSelect');
    const subjectSelect = qs('#scheduleSubjectSelect');
    const classroomSelect = qs('#scheduleClassroomSelect');
    const daySelect = qs('#scheduleDaySelect');

    if (groupSelect) {
        groupSelect.innerHTML = (options.grupos || []).length
            ? `<option value="">Selecciona grupo</option>${options.grupos.map((group) => `<option value="${escapeHtml(group.codigo)}">${escapeHtml(group.codigo)} - ${escapeHtml(group.turno || '')}</option>`).join('')}`
            : '<option value="">No hay grupos activos</option>';
    }

    if (subjectSelect) {
        subjectSelect.innerHTML = (options.materias || []).length
            ? `<option value="">Selecciona materia</option>${options.materias.map((subject) => `<option value="${escapeHtml(subject.id)}">${escapeHtml(subject.nombre)}</option>`).join('')}`
            : '<option value="">No hay materias habilitadas</option>';
    }

    if (classroomSelect) {
        classroomSelect.innerHTML = (options.aulas || []).length
            ? `<option value="">Selecciona aula</option>${options.aulas.map((classroom) => `<option value="${escapeHtml(classroom.nro_aula)}">Aula ${escapeHtml(classroom.nro_aula)} - ${escapeHtml(classroom.tipo || '')} ${escapeHtml(classroom.piso || '')}</option>`).join('')}`
            : '<option value="">No hay aulas disponibles</option>';
    }

    if (daySelect) {
        daySelect.innerHTML = (options.dias || []).length
            ? `<option value="">Selecciona dia</option>${options.dias.map((day) => `<option value="${escapeHtml(day.id)}">${escapeHtml(day.nombre)}</option>`).join('')}`
            : '<option value="">No hay dias registrados</option>';
    }

    renderTeacherOptions();
}

function renderTeacherOptions() {
    const teacherSelect = qs('#scheduleTeacherSelect');
    const selectedSubject = qs('#scheduleSubjectSelect')?.value || '';
    const previous = teacherSelect?.value || '';

    if (!teacherSelect) {
        return;
    }

    const teachers = selectedSubject
        ? (options.docentes || []).filter((teacher) => Array.isArray(teacher.materias_ids)
            && teacher.materias_ids.includes(selectedSubject)
            && teacher.estado_profesional === 'habilitado')
        : [];

    teacherSelect.innerHTML = teachers.length
        ? `<option value="">Selecciona docente</option>${teachers.map((teacher) => `<option value="${escapeHtml(teacher.username)}">${escapeHtml(teacher.nombre)} - ${escapeHtml(teacher.username)}</option>`).join('')}`
        : '<option value="">No hay docentes habilitados para esta materia</option>';

    teacherSelect.value = teachers.some((teacher) => teacher.username === previous) ? previous : '';
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
                <button type="button" data-edit-schedule="${escapeHtml(item.id)}">Editar</button>
                ${item.estado === 'propuesto' ? `<button type="button" data-delete-schedule="${escapeHtml(item.id)}">Eliminar</button>` : ''}
            </td>
        </tr>
    `).join('') || '<tr><td colspan="9">No hay horarios generados.</td></tr>';

    if (count) {
        count.textContent = `${filtered.length} bloque(s) encontrado(s)`;
    }

    qsa('[data-delete-schedule]').forEach((button) => {
        button.addEventListener('click', () => deleteSchedule(button.dataset.deleteSchedule));
    });

    qsa('[data-edit-schedule]').forEach((button) => {
        button.addEventListener('click', () => fillScheduleForm(button.dataset.editSchedule));
    });
}

function fillScheduleForm(id) {
    const form = qs('#scheduleEditForm');
    const schedule = schedules.find((item) => String(item.id) === String(id));

    if (!form || !schedule) {
        return;
    }

    form.elements.id.value = schedule.id;
    form.elements.id_grupo.value = schedule.grupo?.codigo || '';
    form.elements.id_materia.value = schedule.materia?.id || '';
    renderTeacherOptions();
    form.elements.username_docente.value = schedule.docente?.username || '';
    form.elements.id_aula.value = schedule.aula?.nro_aula || '';
    form.elements.id_dia.value = schedule.dia?.id || '';
    form.elements.turno.value = normalizeTurno(schedule.turno || schedule.grupo?.turno || 'mañana');
    form.elements.hora_inicio.value = schedule.hora_inicio || '';
    form.elements.hora_fin.value = schedule.hora_fin || '';
    form.elements.estado.value = schedule.estado || 'propuesto';
    qs('#scheduleEditTitle').textContent = `Editar bloque ${schedule.id}`;
    qs('#scheduleEditPanel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function clearScheduleForm() {
    const form = qs('#scheduleEditForm');

    if (!form) {
        return;
    }

    form.reset();
    form.elements.id.value = '';
    qs('#scheduleEditTitle').textContent = 'Nuevo bloque de horario';
    renderTeacherOptions();
    setMessage('#scheduleEditOutput', '');
}

async function saveSchedule(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const payload = formData(form);
    const id = payload.id;
    delete payload.id;
    payload.id_aula = Number(payload.id_aula);
    payload.id_dia = Number(payload.id_dia);
    payload.id_periodo_academico = currentPeriodId || null;

    const button = form.querySelector('button[type="submit"]');

    try {
        setButtonLoading(button, true, 'Guardando...');
        const data = await apiRequest(id ? `/api/horarios-grupos/${encodeURIComponent(id)}` : '/api/horarios-grupos', {
            method: id ? 'PUT' : 'POST',
            body: JSON.stringify(payload),
        });

        setMessage('#scheduleEditOutput', data);
        await loadScheduleData();
        if (id) {
            fillScheduleForm(id);
        } else {
            clearScheduleForm();
        }
    } catch (error) {
        setMessage('#scheduleEditOutput', error.data || error.message);
    } finally {
        setButtonLoading(button, false);
    }
}

function normalizeTurno(value) {
    const raw = String(value || '').toLowerCase();

    if (raw.includes('tarde')) {
        return 'tarde';
    }

    if (raw.includes('noche')) {
        return 'noche';
    }

    return 'mañana';
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
