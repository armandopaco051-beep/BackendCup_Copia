import { apiRequest, cleanPayload, escapeHtml, formData, qs, qsa, setMessage, statusClass, validateForm } from './api';

let careers = [];
let subjects = [];

export function initCatalogosAcademicos() {
    if (!qs('#careerCatalogTable') && !qs('#subjectCatalogTable')) {
        return;
    }

    qs('[data-load-career-catalog]')?.addEventListener('click', loadCareers);
    qs('[data-load-subject-catalog]')?.addEventListener('click', loadSubjects);
    qs('[data-clear-career-catalog]')?.addEventListener('click', clearCareerForm);
    qs('[data-clear-subject-catalog]')?.addEventListener('click', clearSubjectForm);
    qs('#careerCatalogForm')?.addEventListener('submit', saveCareer);
    qs('#subjectCatalogForm')?.addEventListener('submit', saveSubject);

    loadCareers();
    loadSubjects();
}

async function loadCareers() {
    if (!qs('#careerCatalogTable')) {
        return;
    }

    try {
        const data = await apiRequest('/api/carreras');
        careers = data.carreras || [];
        renderCareers();
    } catch (error) {
        qs('#careerCatalogTable').innerHTML = `<tr><td colspan="5">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
    }
}

function renderCareers() {
    const table = qs('#careerCatalogTable');
    const count = qs('#careerCatalogCount');

    table.innerHTML = careers.map((career) => `
        <tr>
            <td>${escapeHtml(career.codigo)}</td>
            <td><strong>${escapeHtml(career.nombre)}</strong></td>
            <td>${escapeHtml(career.cupo_maximo ?? 0)}</td>
            <td><span class="status-pill ${statusClass(career.estado)}">${escapeHtml(career.estado || 'habilitada')}</span></td>
            <td class="table-actions">
                <button type="button" data-edit-career-catalog="${escapeHtml(career.codigo)}">Editar</button>
                <button type="button" data-delete-career-catalog="${escapeHtml(career.codigo)}">Eliminar</button>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="5">No hay carreras registradas.</td></tr>';

    if (count) {
        count.textContent = `${careers.length} carrera(s) registrada(s)`;
    }

    qsa('[data-edit-career-catalog]').forEach((button) => {
        button.addEventListener('click', () => fillCareerForm(button.dataset.editCareerCatalog));
    });

    qsa('[data-delete-career-catalog]').forEach((button) => {
        button.addEventListener('click', () => deleteCareer(button.dataset.deleteCareerCatalog));
    });
}

function fillCareerForm(code) {
    const form = qs('#careerCatalogForm');
    const career = careers.find((item) => item.codigo === code);

    if (!form || !career) {
        return;
    }

    form.elements.original_codigo.value = career.codigo;
    form.elements.codigo.value = career.codigo;
    form.elements.nombre.value = career.nombre;
    form.elements.cupo_maximo.value = career.cupo_maximo ?? 0;
    form.elements.estado.value = career.estado || 'habilitada';
    qs('#careerFormTitle').textContent = `Editar carrera ${career.codigo}`;
}

function clearCareerForm() {
    const form = qs('#careerCatalogForm');

    if (!form) {
        return;
    }

    form.reset();
    form.elements.original_codigo.value = '';
    qs('#careerFormTitle').textContent = 'Gestionar carrera';
    setMessage('#careerCatalogOutput', '');
}

async function saveCareer(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const payload = cleanPayload(formData(form));
    const original = payload.original_codigo;
    delete payload.original_codigo;
    payload.codigo = payload.codigo?.trim().toUpperCase();
    payload.nombre = payload.nombre?.trim();
    payload.cupo_maximo = Number(payload.cupo_maximo || 0);

    try {
        const data = await apiRequest(original ? `/api/carreras/${encodeURIComponent(original)}` : '/api/carreras', {
            method: original ? 'PUT' : 'POST',
            body: JSON.stringify(payload),
        });

        clearCareerForm();
        setMessage('#careerCatalogOutput', data);
        loadCareers();
    } catch (error) {
        setMessage('#careerCatalogOutput', error.data || error.message);
    }
}

async function deleteCareer(code) {
    if (!window.confirm(`Eliminar carrera ${code}?`)) {
        return;
    }

    try {
        const data = await apiRequest(`/api/carreras/${encodeURIComponent(code)}`, { method: 'DELETE' });
        setMessage('#careerCatalogOutput', data);
        loadCareers();
    } catch (error) {
        setMessage('#careerCatalogOutput', error.data || error.message);
    }
}

async function loadSubjects() {
    if (!qs('#subjectCatalogTable')) {
        return;
    }

    try {
        const data = await apiRequest('/api/materias');
        subjects = data.materias || [];
        renderSubjects();
    } catch (error) {
        qs('#subjectCatalogTable').innerHTML = `<tr><td colspan="4">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
    }
}

function renderSubjects() {
    const table = qs('#subjectCatalogTable');
    const count = qs('#subjectCatalogCount');

    table.innerHTML = subjects.map((subject) => `
        <tr>
            <td>${escapeHtml(subject.id)}</td>
            <td><strong>${escapeHtml(subject.nombre)}</strong></td>
            <td><span class="status-pill ${statusClass(subject.estado)}">${escapeHtml(subject.estado || 'habilitada')}</span></td>
            <td class="table-actions">
                <button type="button" data-edit-subject-catalog="${escapeHtml(subject.id)}">Editar</button>
                <button type="button" data-delete-subject-catalog="${escapeHtml(subject.id)}">Eliminar</button>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="4">No hay materias registradas.</td></tr>';

    if (count) {
        count.textContent = `${subjects.length} materia(s) registrada(s)`;
    }

    qsa('[data-edit-subject-catalog]').forEach((button) => {
        button.addEventListener('click', () => fillSubjectForm(button.dataset.editSubjectCatalog));
    });

    qsa('[data-delete-subject-catalog]').forEach((button) => {
        button.addEventListener('click', () => deleteSubject(button.dataset.deleteSubjectCatalog));
    });
}

function fillSubjectForm(id) {
    const form = qs('#subjectCatalogForm');
    const subject = subjects.find((item) => item.id === id);

    if (!form || !subject) {
        return;
    }

    form.elements.original_id.value = subject.id;
    form.elements.id.value = subject.id;
    form.elements.nombre.value = subject.nombre;
    form.elements.estado.value = subject.estado || 'habilitada';
    qs('#subjectFormTitle').textContent = `Editar materia ${subject.id}`;
}

function clearSubjectForm() {
    const form = qs('#subjectCatalogForm');

    if (!form) {
        return;
    }

    form.reset();
    form.elements.original_id.value = '';
    qs('#subjectFormTitle').textContent = 'Gestionar materia';
    setMessage('#subjectCatalogOutput', '');
}

async function saveSubject(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const payload = cleanPayload(formData(form));
    const original = payload.original_id;
    delete payload.original_id;
    payload.id = payload.id?.trim().toUpperCase();
    payload.nombre = payload.nombre?.trim();

    try {
        const data = await apiRequest(original ? `/api/materias/${encodeURIComponent(original)}` : '/api/materias', {
            method: original ? 'PUT' : 'POST',
            body: JSON.stringify(payload),
        });

        clearSubjectForm();
        setMessage('#subjectCatalogOutput', data);
        loadSubjects();
    } catch (error) {
        setMessage('#subjectCatalogOutput', error.data || error.message);
    }
}

async function deleteSubject(id) {
    if (!window.confirm(`Eliminar materia ${id}?`)) {
        return;
    }

    try {
        const data = await apiRequest(`/api/materias/${encodeURIComponent(id)}`, { method: 'DELETE' });
        setMessage('#subjectCatalogOutput', data);
        loadSubjects();
    } catch (error) {
        setMessage('#subjectCatalogOutput', error.data || error.message);
    }
}
