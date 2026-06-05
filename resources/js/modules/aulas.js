import { apiRequest, cleanPayload, escapeHtml, formData, numberFormat, qs, qsa, statusClass, validateForm } from './api';

let classrooms = [];
let schema = {};

export function initAulas() {
    if (!qs('#classroomsTable')) {
        return;
    }

    qs('[data-load-classrooms]')?.addEventListener('click', loadClassrooms);
    qs('[data-load-classroom-capacity]')?.addEventListener('click', loadCapacity);
    qs('[data-clear-classroom]')?.addEventListener('click', clearForm);
    qs('#classroomSearch')?.addEventListener('input', (event) => renderClassrooms(event.currentTarget.value));
    qs('#classroomForm')?.addEventListener('submit', saveClassroom);

    loadClassrooms();
}

async function loadClassrooms() {
    try {
        const data = await apiRequest('/api/aulas');
        classrooms = data.aulas || [];
        schema = data.configuracion || {};
        applySchema();
        renderClassrooms(qs('#classroomSearch')?.value || '');
        loadCapacity();
    } catch (error) {
        qs('#classroomsTable').innerHTML = `<tr><td colspan="6">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
    }
}

async function loadCapacity() {
    try {
        const data = await apiRequest('/api/aulas/cupos');
        renderCapacity(data.aulas || [], data.resumen || {});
    } catch (error) {
        qs('#classroomCapacityTable').innerHTML = `<tr><td colspan="6">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
    }
}

function applySchema() {
    const notice = qs('#classroomSchemaNotice');
    const capacity = qs('#classroomForm [name="capacidad"]');
    const state = qs('#classroomForm [name="estado"]');

    if (capacity) {
        capacity.disabled = false;
        capacity.readOnly = true;
        capacity.value = '70';
    }

    if (state) {
        state.disabled = !schema.soporta_capacidad_estado;
    }

    if (notice) {
        notice.textContent = schema.soporta_capacidad_estado
            ? 'La capacidad se registra automaticamente en 70 estudiantes por aula.'
            : 'Tu tabla actual guarda solo aula, tipo y piso. Ejecuta la migracion para activar estado.';
    }
}

function renderClassrooms(filter = '') {
    const table = qs('#classroomsTable');
    const count = qs('#classroomCount');
    const query = filter.trim().toLowerCase();

    const filtered = classrooms.filter((classroom) => [
        classroom.nro_aula,
        classroom.tipo,
        classroom.piso,
        classroom.capacidad,
        classroom.estado,
    ].filter(Boolean).join(' ').toLowerCase().includes(query));

    table.innerHTML = filtered.map((classroom) => `
        <tr>
            <td><strong>Aula ${escapeHtml(classroom.nro_aula)}</strong></td>
            <td>${escapeHtml(classroom.tipo)}</td>
            <td>${escapeHtml(classroom.piso)}</td>
            <td>${escapeHtml(classroom.capacidad || 70)}</td>
            <td><span class="status-pill ${statusClass(classroom.estado)}">${escapeHtml(classroom.estado || 'Sin especificar')}</span></td>
            <td class="table-actions">
                <button type="button" data-edit-classroom="${escapeHtml(classroom.nro_aula)}">Editar</button>
                <button type="button" data-delete-classroom="${escapeHtml(classroom.nro_aula)}">Eliminar</button>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="6">No hay aulas registradas.</td></tr>';

    if (count) {
        count.textContent = `${filtered.length} aula(s) registrada(s)`;
    }

    qsa('[data-edit-classroom]').forEach((button) => {
        button.addEventListener('click', () => fillForm(Number(button.dataset.editClassroom)));
    });

    qsa('[data-delete-classroom]').forEach((button) => {
        button.addEventListener('click', () => deleteClassroom(Number(button.dataset.deleteClassroom)));
    });
}

function renderCapacity(items, summary) {
    qs('#classroomTotal').textContent = numberFormat(summary.total_aulas || items.length);
    qs('#classroomAvailable').textContent = numberFormat(summary.disponibles);
    qs('#classroomCapacity').textContent = numberFormat(summary.sin_cupo);
    const average = items.length
        ? Math.round(items.reduce((total, item) => total + Number(item.porcentaje_uso || 0), 0) / items.length)
        : 0;

    qs('#classroomAverage').textContent = `${numberFormat(average)}%`;

    const table = qs('#classroomCapacityTable');
    const count = qs('#classroomCapacityCount');

    table.innerHTML = items.map((item) => {
        const percent = Math.min(100, Number(item.porcentaje_uso || 0));
        const stateClass = item.estado_cupo === 'disponible' ? 'is-admitted' : item.estado_cupo === 'sin_cupo' ? 'is-rejected' : 'is-validated';
        const label = {
            disponible: 'Disponible',
            casi_lleno: 'Casi lleno',
            sin_cupo: 'Sin cupo',
        }[item.estado_cupo] || item.estado_cupo;

        return `
            <tr>
                <td><strong>Aula ${escapeHtml(item.nro_aula)}</strong></td>
                <td>${escapeHtml(item.tipo)}</td>
                <td>${escapeHtml(item.capacidad)}</td>
                <td>${escapeHtml(item.ocupacion)}</td>
                <td>
                    <div class="capacity-meter"><span style="width:${percent}%"></span></div>
                    <small>${percent}%</small>
                </td>
                <td><span class="status-pill ${stateClass}">${escapeHtml(label)}</span></td>
            </tr>
        `;
    }).join('') || '<tr><td colspan="6">No hay aulas para validar cupos.</td></tr>';

    if (count) {
        count.textContent = `${numberFormat(summary.total_aulas || items.length)} aula(s): ${numberFormat(summary.disponibles)} disponibles, ${numberFormat(summary.casi_llenas)} casi llenas, ${numberFormat(summary.sin_cupo)} sin cupo.`;
    }
}

function fillForm(id) {
    const form = qs('#classroomForm');
    const classroom = classrooms.find((item) => Number(item.nro_aula) === Number(id));

    if (!form || !classroom) {
        return;
    }

    form.elements.nro_aula.value = classroom.nro_aula;
    form.elements.nro_aula.readOnly = true;
    form.elements.tipo.value = classroom.tipo || '';
    form.elements.piso.value = classroom.piso || '';
    form.elements.capacidad.value = '70';
    form.elements.capacidad.readOnly = true;
    form.elements.estado.value = classroom.estado || 'disponible';
    qs('#classroomFormTitle').textContent = `Editar aula ${classroom.nro_aula}`;
}

function clearForm() {
    const form = qs('#classroomForm');

    if (!form) {
        return;
    }

    form.reset();
    form.elements.nro_aula.readOnly = false;
    form.elements.capacidad.value = '70';
    form.elements.capacidad.readOnly = true;
    qs('#classroomFormTitle').textContent = 'Nueva aula';
}

async function saveClassroom(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const payload = cleanPayload(formData(form));
    const updating = form.elements.nro_aula.readOnly;
    const classroomId = payload.nro_aula;
    payload.nro_aula = Number(payload.nro_aula);
    payload.capacidad = 70;

    try {
        await apiRequest(updating ? `/api/aulas/${classroomId}` : '/api/aulas', {
            method: updating ? 'PUT' : 'POST',
            body: JSON.stringify(payload),
        });

        clearForm();
        loadClassrooms();
    } catch (error) {
        qs('#classroomSchemaNotice').textContent = error.data?.message || error.message;
    }
}

async function deleteClassroom(id) {
    if (!window.confirm(`Eliminar aula ${id}?`)) {
        return;
    }

    await apiRequest(`/api/aulas/${id}`, { method: 'DELETE' });
    loadClassrooms();
}
