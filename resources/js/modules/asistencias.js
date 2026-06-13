import { apiRequest, escapeHtml, formData, qs, qsa, setMessage, statusClass, validateForm } from './api';

let attendance = [];
let options = {
    grupos: [],
    materias: [],
    postulantes: [],
};

export function initAsistencias() {
    if (!qs('#attendanceForm') && !qs('#attendanceTable')) {
        return;
    }

    qs('[data-load-attendance]')?.addEventListener('click', () => {
        loadOptions();
        loadAttendance();
    });
    qs('[data-clear-attendance]')?.addEventListener('click', clearAttendanceForm);
    qs('[data-mark-all-present]')?.addEventListener('click', markAllPresent);
    qs('#attendanceSearch')?.addEventListener('input', (event) => renderAttendance(event.currentTarget.value));
    qs('#attendanceGroupSelect')?.addEventListener('change', () => {
        renderSubjectsByGroup();
        renderRosterByGroup();
    });
    qs('#attendanceForm')?.addEventListener('submit', saveAttendance);

    setToday();
    loadOptions();
    loadAttendance();
}

async function loadOptions() {
    if (!qs('#attendanceForm')) {
        return;
    }

    try {
        const data = await apiRequest('/api/asistencias/opciones');
        options = {
            grupos: data.grupos || [],
            materias: data.materias || [],
            postulantes: data.postulantes || [],
        };
        renderGroups();
        renderSubjectsByGroup();
        renderRosterByGroup();
    } catch (error) {
        setMessage('#attendanceOutput', error.data || error.message);
    }
}

async function loadAttendance() {
    if (!qs('#attendanceTable')) {
        return;
    }

    try {
        const data = await apiRequest('/api/asistencias');
        attendance = data.asistencias || [];
        renderAttendance(qs('#attendanceSearch')?.value || '');
    } catch (error) {
        qs('#attendanceTable').innerHTML = `<tr><td colspan="6">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
    }
}

function renderGroups() {
    const select = qs('#attendanceGroupSelect');

    if (!select) {
        return;
    }

    select.innerHTML = options.grupos.length
        ? `<option value="">Selecciona grupo</option>${options.grupos.map((grupo) => `<option value="${escapeHtml(grupo.codigo)}">${escapeHtml(grupo.codigo)} - ${escapeHtml(grupo.turno || grupo.descripcion || '')}</option>`).join('')}`
        : '<option value="">No hay grupos asignados</option>';
}

function renderSubjectsByGroup() {
    const select = qs('#attendanceSubjectSelect');
    const selectedGroup = qs('#attendanceGroupSelect')?.value || '';

    if (!select) {
        return;
    }

    const subjects = selectedGroup
        ? options.materias.filter((materia) => materia.grupo === selectedGroup)
        : [];

    select.innerHTML = subjects.length
        ? `<option value="">Selecciona materia</option>${subjects.map((materia) => `<option value="${escapeHtml(materia.id)}">${escapeHtml(materia.nombre)}</option>`).join('')}`
        : '<option value="">No hay materias para este grupo</option>';
}

function renderRosterByGroup() {
    const container = qs('#attendanceRoster');
    const count = qs('#attendanceRosterCount');
    const selectedGroup = qs('#attendanceGroupSelect')?.value || '';

    if (!container) {
        return;
    }

    const students = selectedGroup
        ? options.postulantes.filter((student) => Array.isArray(student.grupos) && student.grupos.includes(selectedGroup))
        : [];

    container.innerHTML = students.map((student) => `
        <article class="attendance-student" data-attendance-student="${escapeHtml(student.username)}">
            <div>
                <strong>${escapeHtml(student.nombre)}</strong>
                <small>${escapeHtml(student.ci || student.username)} · ${escapeHtml(student.username)}</small>
            </div>
            <fieldset>
                ${attendanceStatusOption(student.username, 'presente', 'Presente')}
                ${attendanceStatusOption(student.username, 'retraso', 'Retraso')}
                ${attendanceStatusOption(student.username, 'falta', 'Falta')}
            </fieldset>
        </article>
    `).join('') || '<p class="module-note">No hay alumnos registrados en este grupo.</p>';

    if (count) {
        count.textContent = `${students.length} alumno(s) en el grupo`;
    }
}

function attendanceStatusOption(username, value, label) {
    return `
        <label>
            <input type="radio" name="estado_${escapeHtml(username)}" value="${value}" ${value === 'presente' ? 'checked' : ''}>
            <span>${label}</span>
        </label>
    `;
}

async function saveAttendance(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const students = qsa('[data-attendance-student]').map((item) => {
        const username = item.dataset.attendanceStudent;
        const checked = qs('input[type="radio"]:checked', item);

        return {
            username_postulante: username,
            estado: checked?.value || 'presente',
        };
    });

    if (!students.length) {
        setMessage('#attendanceOutput', 'Selecciona un grupo con alumnos para registrar asistencia.');
        return;
    }

    const values = formData(form);

    try {
        const data = await apiRequest('/api/asistencias/lote', {
            method: 'POST',
            body: JSON.stringify({
                id_grupo: values.id_grupo,
                id_materia: values.id_materia,
                fecha: values.fecha,
                asistencias: students,
            }),
        });

        setMessage('#attendanceOutput', data);
        loadAttendance();
    } catch (error) {
        setMessage('#attendanceOutput', error.data || error.message);
    }
}

function renderAttendance(filter = '') {
    const table = qs('#attendanceTable');
    const count = qs('#attendanceCount');

    if (!table) {
        return;
    }

    const query = filter.trim().toLowerCase();
    const filtered = attendance.filter((item) => [
        item.fecha,
        item.postulante?.nombre,
        item.postulante?.ci,
        item.username_postulante,
        item.id_grupo,
        item.materia?.nombre,
        item.id_materia,
        item.estado,
    ].filter(Boolean).join(' ').toLowerCase().includes(query));

    table.innerHTML = filtered.map((item) => `
        <tr>
            <td>${escapeHtml(item.fecha || '-')}</td>
            <td>
                <strong>${escapeHtml(item.postulante?.nombre || item.username_postulante)}</strong>
                <small>${escapeHtml(item.username_postulante)}</small>
            </td>
            <td>${escapeHtml(item.id_grupo)}</td>
            <td>${escapeHtml(item.materia?.nombre || item.id_materia)}</td>
            <td><span class="status-pill ${statusClass(item.estado)}">${escapeHtml(item.estado)}</span></td>
            <td class="table-actions">
                <button type="button" data-delete-attendance="${item.id}">Eliminar</button>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="6">No hay asistencias registradas.</td></tr>';

    if (count) {
        count.textContent = `${filtered.length} registro(s) encontrado(s)`;
    }

    qsa('[data-delete-attendance]').forEach((button) => {
        button.addEventListener('click', () => deleteAttendance(Number(button.dataset.deleteAttendance)));
    });
}

async function deleteAttendance(id) {
    if (!window.confirm(`Eliminar asistencia ${id}?`)) {
        return;
    }

    try {
        const data = await apiRequest(`/api/asistencias/${id}`, { method: 'DELETE' });
        setMessage('#attendanceOutput', data);
        loadAttendance();
    } catch (error) {
        setMessage('#attendanceOutput', error.data || error.message);
    }
}

function markAllPresent() {
    qsa('#attendanceRoster input[value="presente"]').forEach((input) => {
        input.checked = true;
    });
}

function clearAttendanceForm() {
    const form = qs('#attendanceForm');

    if (!form) {
        return;
    }

    form.reset();
    setToday();
    renderSubjectsByGroup();
    renderRosterByGroup();
    setMessage('#attendanceOutput', '');
}

function setToday() {
    const input = qs('#attendanceForm [name="fecha"]');

    if (input && !input.value) {
        input.value = new Date().toISOString().slice(0, 10);
    }
}
