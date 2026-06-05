import { apiRequest, cleanPayload, escapeHtml, formData, qs, qsa, setMessage, statusClass, validateForm } from './api';

let grades = [];
let options = {
    postulantes: [],
    grupos: [],
    materias: [],
};

export function initCalificaciones() {
    if (!qs('#gradesTable') && !qs('#gradeForm')) {
        return;
    }

    qs('[data-load-grades]')?.addEventListener('click', loadGrades);
    qs('[data-clear-grade]')?.addEventListener('click', clearForm);
    qs('#gradeSearch')?.addEventListener('input', (event) => renderGrades(event.currentTarget.value));
    qs('#gradeForm')?.addEventListener('submit', saveGrade);

    loadOptions();
    loadGrades();
}

async function loadOptions() {
    if (!qs('#gradeForm')) {
        return;
    }

    try {
        const data = await apiRequest('/api/calificaciones/opciones');
        options = {
            postulantes: data.postulantes || [],
            grupos: data.grupos || [],
            materias: data.materias || [],
        };
        renderOptions();
    } catch (error) {
        setMessage('#gradesOutput', error.data || error.message);
    }
}

function renderOptions() {
    const applicants = qs('#gradeApplicantSelect');
    const groups = qs('#gradeGroupSelect');
    const subjects = qs('#gradeSubjectSelect');

    if (applicants) {
        applicants.innerHTML = options.postulantes.length
            ? `<option value="">Selecciona postulante</option>${options.postulantes.map((item) => `<option value="${escapeHtml(item.username)}">${escapeHtml(item.nombre)} - ${escapeHtml(item.ci || item.username)}</option>`).join('')}`
            : '<option value="">No hay postulantes registrados</option>';
    }

    if (groups) {
        groups.innerHTML = options.grupos.length
            ? `<option value="">Selecciona grupo</option>${options.grupos.map((item) => `<option value="${escapeHtml(item.codigo)}">${escapeHtml(item.codigo)} - ${escapeHtml(item.turno || item.descripcion || '')}</option>`).join('')}`
            : '<option value="">No hay grupos registrados</option>';
    }

    if (subjects) {
        subjects.innerHTML = options.materias.length
            ? `<option value="">Selecciona materia</option>${options.materias.map((item) => `<option value="${escapeHtml(item.id)}">${escapeHtml(item.nombre)}</option>`).join('')}`
            : '<option value="">No hay materias registradas</option>';
    }
}

async function loadGrades() {
    if (!qs('#gradesTable')) {
        return;
    }

    try {
        const data = await apiRequest('/api/calificaciones');
        grades = data.calificaciones || [];
        renderGrades(qs('#gradeSearch')?.value || '');
    } catch (error) {
        qs('#gradesTable').innerHTML = `<tr><td colspan="7">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
    }
}

function renderGrades(filter = '') {
    const table = qs('#gradesTable');
    const count = qs('#gradesCount');
    const query = filter.trim().toLowerCase();

    const filtered = grades.filter((grade) => [
        grade.postulante?.nombre,
        grade.postulante?.ci,
        grade.username_postulante,
        grade.id_grupo,
        grade.materia?.nombre,
        grade.id_materia,
        grade.estado,
    ].filter(Boolean).join(' ').toLowerCase().includes(query));

    table.innerHTML = filtered.map((grade) => `
        <tr>
            <td>
                <strong>${escapeHtml(grade.postulante?.nombre || grade.username_postulante)}</strong>
                <small>${escapeHtml(grade.username_postulante)}</small>
            </td>
            <td>${escapeHtml(grade.id_grupo)}</td>
            <td>${escapeHtml(grade.materia?.nombre || grade.id_materia)}</td>
            <td>${escapeHtml([grade.nota1, grade.nota2, grade.nota3].filter((value) => value !== null && value !== undefined).join(' / '))}</td>
            <td>${escapeHtml(grade.promedio ?? '-')}</td>
            <td><span class="status-pill ${statusClass(grade.estado)}">${escapeHtml(grade.estado || 'pendiente')}</span></td>
            <td class="table-actions">
                <button type="button" data-edit-grade="${grade.id}">Editar</button>
                <button type="button" data-delete-grade="${grade.id}">Eliminar</button>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="7">No hay calificaciones registradas.</td></tr>';

    if (count) {
        count.textContent = `${filtered.length} calificacion(es) encontrada(s)`;
    }

    qsa('[data-edit-grade]').forEach((button) => {
        button.addEventListener('click', () => fillForm(Number(button.dataset.editGrade)));
    });

    qsa('[data-delete-grade]').forEach((button) => {
        button.addEventListener('click', () => deleteGrade(Number(button.dataset.deleteGrade)));
    });
}

function fillForm(id) {
    const form = qs('#gradeForm');
    const grade = grades.find((item) => Number(item.id) === Number(id));

    if (!form || !grade) {
        return;
    }

    form.elements.id.value = grade.id;
    form.elements.username_postulante.value = grade.username_postulante;
    form.elements.id_grupo.value = grade.id_grupo;
    form.elements.id_materia.value = grade.id_materia;
    form.elements.nota1.value = grade.nota1;
    form.elements.nota2.value = grade.nota2;
    form.elements.nota3.value = grade.nota3;
    form.elements.descripcion.value = grade.descripcion || '';
    qs('#gradeFormTitle').textContent = `Editar calificacion ${grade.id}`;
}

function clearForm() {
    const form = qs('#gradeForm');

    if (!form) {
        return;
    }

    form.reset();
    form.elements.id.value = '';
    qs('#gradeFormTitle').textContent = 'Nueva calificacion';
    setMessage('#gradesOutput', '');
}

async function saveGrade(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const payload = cleanPayload(formData(form));
    const id = payload.id;
    delete payload.id;

    ['nota1', 'nota2', 'nota3'].forEach((field) => {
        payload[field] = Number(payload[field]);
    });

    try {
        const data = await apiRequest(id ? `/api/calificaciones/${id}` : '/api/calificaciones', {
            method: id ? 'PUT' : 'POST',
            body: JSON.stringify(payload),
        });

        clearForm();
        setMessage('#gradesOutput', data);
        loadGrades();
    } catch (error) {
        setMessage('#gradesOutput', error.data || error.message);
    }
}

async function deleteGrade(id) {
    if (!window.confirm(`Eliminar calificacion ${id}?`)) {
        return;
    }

    try {
        const data = await apiRequest(`/api/calificaciones/${id}`, { method: 'DELETE' });
        setMessage('#gradesOutput', data);
        loadGrades();
    } catch (error) {
        setMessage('#gradesOutput', error.data || error.message);
    }
}
