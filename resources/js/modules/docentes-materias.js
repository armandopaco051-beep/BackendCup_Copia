import { apiRequest, escapeHtml, qs, qsa, setMessage, validateForm } from './api';

let teachers = [];
let subjects = [];

export function initDocentesMaterias() {
    if (!qs('#teacherSubjectTable') && !qs('#teacherSubjectForm')) {
        return;
    }

    qs('[data-load-teacher-subjects]')?.addEventListener('click', loadTeacherSubjects);
    qs('[data-clear-teacher-subjects]')?.addEventListener('click', clearTeacherSubjects);
    qs('#teacherSubjectSearch')?.addEventListener('input', (event) => renderTeachers(event.currentTarget.value));
    qs('#teacherSubjectTeacherSelect')?.addEventListener('change', syncSelectedTeacher);
    qs('#teacherSubjectForm')?.addEventListener('submit', saveTeacherSubjects);

    loadTeacherSubjects();
}

async function loadTeacherSubjects() {
    try {
        const data = await apiRequest('/api/docentes-materias');
        teachers = data.docentes || [];
        subjects = data.materias || [];
        renderTeacherOptions();
        renderSubjectChecks();
        renderTeachers(qs('#teacherSubjectSearch')?.value || '');
        syncSelectedTeacher();
    } catch (error) {
        setMessage('#teacherSubjectOutput', error.data || error.message);
        if (qs('#teacherSubjectTable')) {
            qs('#teacherSubjectTable').innerHTML = `<tr><td colspan="4">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
        }
    }
}

function renderTeacherOptions() {
    const select = qs('#teacherSubjectTeacherSelect');

    if (!select) {
        return;
    }

    const previous = select.value;
    select.innerHTML = teachers.length
        ? `<option value="">Selecciona docente</option>${teachers.map((teacher) => `<option value="${escapeHtml(teacher.username)}">${escapeHtml(teacher.nombre)} - ${escapeHtml(teacher.username)}</option>`).join('')}`
        : '<option value="">No hay docentes registrados</option>';
    select.value = teachers.some((teacher) => teacher.username === previous) ? previous : '';
}

function renderSubjectChecks() {
    const container = qs('#teacherSubjectChecks');

    if (!container) {
        return;
    }

    container.innerHTML = subjects.length
        ? subjects.map((subject) => `
            <label class="teacher-subject-check">
                <input type="checkbox" name="materias" value="${escapeHtml(subject.id)}">
                <span>
                    <strong>${escapeHtml(subject.nombre)}</strong>
                    <small>${escapeHtml(subject.id)}</small>
                </span>
            </label>
        `).join('')
        : '<p class="module-note">No hay materias habilitadas.</p>';
}

function renderTeachers(filter = '') {
    const table = qs('#teacherSubjectTable');
    const count = qs('#teacherSubjectCount');

    if (!table) {
        return;
    }

    const query = filter.trim().toLowerCase();
    const filtered = teachers.filter((teacher) => [
        teacher.username,
        teacher.nombre,
        teacher.correo,
        ...(teacher.materias || []).map((subject) => subject.nombre),
    ].filter(Boolean).join(' ').toLowerCase().includes(query));

    table.innerHTML = filtered.map((teacher) => `
        <tr>
            <td>
                <strong>${escapeHtml(teacher.nombre)}</strong>
                <small>${escapeHtml(teacher.username)}</small>
            </td>
            <td>${escapeHtml(teacher.correo || 'Sin correo')}</td>
            <td>${renderSubjectTags(teacher.materias)}</td>
            <td class="table-actions">
                <button type="button" data-select-teacher="${escapeHtml(teacher.username)}">Asignar</button>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="4">No hay docentes registrados.</td></tr>';

    if (count) {
        count.textContent = `${filtered.length} docente(s) encontrado(s)`;
    }

    qsa('[data-select-teacher]').forEach((button) => {
        button.addEventListener('click', () => {
            qs('#teacherSubjectTeacherSelect').value = button.dataset.selectTeacher;
            syncSelectedTeacher();
            qs('#teacherSubjectForm')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
}

function renderSubjectTags(items = []) {
    if (!items.length) {
        return '<span class="status-pill">Sin materias</span>';
    }

    return `<div class="teacher-subject-tags">${items.map((subject) => `<span class="status-pill is-admitted">${escapeHtml(subject.nombre)}</span>`).join('')}</div>`;
}

function syncSelectedTeacher() {
    const selected = qs('#teacherSubjectTeacherSelect')?.value || '';
    const teacher = teachers.find((item) => item.username === selected);
    const selectedSubjects = new Set(teacher?.materias_ids || []);

    qsa('#teacherSubjectChecks input[type="checkbox"]').forEach((input) => {
        input.checked = selectedSubjects.has(input.value);
        input.disabled = !teacher;
    });

    renderTeacherDetail(teacher);
}

function renderTeacherDetail(teacher) {
    qs('#teacherSubjectDetailName').textContent = teacher?.nombre || 'Sin docente seleccionado';
    qs('#teacherSubjectDetailMeta').textContent = teacher
        ? `${teacher.username}${teacher.correo ? ` · ${teacher.correo}` : ''}`
        : 'Las materias asignadas apareceran aqui.';
    qs('#teacherSubjectDetailTags').innerHTML = renderSubjectTags(teacher?.materias || []);
}

async function saveTeacherSubjects(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const username = form.elements.username_docente.value;
    const selectedSubjects = qsa('#teacherSubjectChecks input[type="checkbox"]:checked')
        .map((input) => input.value);

    try {
        const data = await apiRequest(`/api/docentes/${encodeURIComponent(username)}/materias`, {
            method: 'PUT',
            body: JSON.stringify({
                materias: selectedSubjects,
            }),
        });

        setMessage('#teacherSubjectOutput', data);
        await loadTeacherSubjects();
        qs('#teacherSubjectTeacherSelect').value = username;
        syncSelectedTeacher();
    } catch (error) {
        setMessage('#teacherSubjectOutput', error.data || error.message);
    }
}

function clearTeacherSubjects() {
    qsa('#teacherSubjectChecks input[type="checkbox"]').forEach((input) => {
        input.checked = false;
    });
    setMessage('#teacherSubjectOutput', '');
}
