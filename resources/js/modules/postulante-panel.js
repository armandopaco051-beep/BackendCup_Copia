import {
    apiRequest,
    escapeHtml,
    formData,
    qs,
    setButtonLoading,
    setMessage,
    statusClass,
    validateForm,
} from './api';

let currentEnrollment = null;
let groups = [];

export function initPostulantePanel() {
    if (!qs('[data-page="postulante"]')) {
        return;
    }

    qs('#postulantGroupForm')?.addEventListener('submit', savePostulantGroup);
    loadPostulantPanel();
    loadPostulantGroups();
}

async function loadPostulantPanel() {
    try {
        const data = await apiRequest('/api/postulante/mi-panel');
        renderPreinscription(data);
        renderRequirements(data.requisitos);
        renderPayment(data.pago);
        renderSchedule(data.horario || []);
        renderGrades(data.calificaciones || [], data.asistencias || {});
        renderAssignedCareer(data.carrera_asignada);
        renderPanelGroupSummary(data.grupo);
    } catch (error) {
        const message = error.status === 404
            ? 'Tu cuenta no esta vinculada con un registro de postulante.'
            : (error.data?.message || error.message);

        setMessage('#postulantPreinscriptionSummary', message);
        setMessage('#postulantRequirementsSummary', message);
        setMessage('#postulantPaymentSummary', message);
        setMessage('#postulantGroupSummary', message);
        renderEmptyTable('#postulantScheduleBody', 8, message);
        renderEmptyTable('#postulantGradesBody', 7, message);
    }
}

function renderPreinscription(data) {
    const postulante = data.postulante || {};
    const carreras = data.carreras || [];
    const fields = qs('#postulantPreinscriptionFields');
    const careerTarget = qs('#postulantCareerOptions');
    const download = qs('#postulantFormDownload');

    setMessage(
        '#postulantPreinscriptionSummary',
        `${postulante.username || 'Sin folio'} | ${statusLabel(postulante.estado || 'pendiente')}`,
    );
    setStatus('#postulantStatus', postulante.estado || 'pendiente');

    if (fields) {
        const rows = [
            ['Folio', postulante.username],
            ['Nombre', postulante.nombre],
            ['CI', postulante.ci],
            ['Correo', postulante.correo],
            ['Telefono', postulante.telefono],
            ['Ciudad', postulante.ciudad],
            ['Colegio', postulante.colegio_procedencia],
            ['Direccion', postulante.direccion],
            ['Fecha de nacimiento', postulante.fecha_nacimiento],
            ['Genero', postulante.genero],
            ['Codigo de titulo', postulante.cod_titulo_bachiller],
        ];

        fields.innerHTML = rows.map(([label, value]) => `
            <div>
                <span>${escapeHtml(label)}</span>
                <strong>${escapeHtml(value || 'Sin registrar')}</strong>
            </div>
        `).join('');
    }

    if (careerTarget) {
        careerTarget.innerHTML = carreras.length
            ? carreras.map((career, index) => `
                <div>
                    <span>${escapeHtml(career.descripcion || `Opcion ${index + 1}`)}</span>
                    <strong>${escapeHtml(career.nombre || career.codigo)}</strong>
                </div>
            `).join('')
            : '<p class="module-note">No hay carreras seleccionadas.</p>';
    }

    if (download && postulante.formulario_url) {
        download.href = postulante.formulario_url;
        download.hidden = false;
    }
}

function renderRequirements(requirements = {}) {
    const documents = requirements.documentos || [];
    const target = qs('#postulantRequirementsList');
    const observation = qs('#postulantRequirementsObservation');
    const summary = `${requirements.completados || 0} de ${requirements.total || 3} documentos validados`;

    setMessage('#postulantRequirementsSummary', summary);
    setStatus('#postulantRequirementsStatus', requirements.estado || 'pendiente');

    if (target) {
        target.innerHTML = documents.map((document) => `
            <div class="${document.entregado ? 'is-complete' : ''}">
                <span class="postulant-document-mark" aria-hidden="true">${document.entregado ? 'OK' : '--'}</span>
                <strong>${escapeHtml(document.nombre)}</strong>
                <span class="status-pill ${document.entregado ? 'is-admitted' : ''}">
                    ${document.entregado ? 'Validado' : 'Pendiente'}
                </span>
            </div>
        `).join('');
    }

    if (observation) {
        const detail = requirements.observacion
            ? `Observacion: ${requirements.observacion}`
            : 'No existen observaciones registradas.';
        const date = requirements.fecha_validacion
            ? ` Revision: ${requirements.fecha_validacion}.`
            : '';

        observation.textContent = detail + date;
    }
}

function renderPayment(payment = {}) {
    const fields = qs('#postulantPaymentFields');
    const state = payment.estado || 'sin_pago';

    setMessage(
        '#postulantPaymentSummary',
        state === 'sin_pago' ? 'No existe un pago registrado.' : statusLabel(state),
    );
    setStatus('#postulantPaymentStatus', state);

    if (!fields) {
        return;
    }

    const rows = [
        ['Monto', payment.monto ? `${payment.monto} Bs.` : 'Sin pago'],
        ['Fecha', payment.fecha || 'Sin fecha'],
        ['Comprobante', payment.comprobante || 'Sin comprobante'],
        ['Observacion', payment.observacion || 'Sin observacion'],
    ];

    fields.innerHTML = rows.map(([label, value]) => `
        <div>
            <span>${escapeHtml(label)}</span>
            <strong>${escapeHtml(value)}</strong>
        </div>
    `).join('');
}

function renderSchedule(schedule) {
    const target = qs('#postulantScheduleBody');

    if (!target) {
        return;
    }

    if (!schedule.length) {
        renderEmptyTable('#postulantScheduleBody', 8, 'Todavia no tienes un horario asignado.');
        return;
    }

    target.innerHTML = schedule.map((item) => `
        <tr>
            <td><strong>${escapeHtml(item.dia)}</strong></td>
            <td>${escapeHtml(item.hora_inicio)} - ${escapeHtml(item.hora_fin)}</td>
            <td>${escapeHtml(item.materia)}</td>
            <td>${escapeHtml(item.grupo)}</td>
            <td>${escapeHtml(statusLabel(item.turno))}</td>
            <td>${escapeHtml(item.docente)}</td>
            <td>
                <strong>Aula ${escapeHtml(item.aula || 'sin asignar')}</strong>
                <small>${escapeHtml(item.tipo_aula || 'Aula')} | Piso ${escapeHtml(item.piso || 'sin definir')}</small>
            </td>
            <td><span class="status-pill ${statusClass(item.estado)}">${escapeHtml(statusLabel(item.estado))}</span></td>
        </tr>
    `).join('');
}

function renderGrades(grades, attendance) {
    const target = qs('#postulantGradesBody');
    const attendanceTarget = qs('#postulantAttendanceSummary');

    setStatus('#postulantGradesStatus', grades.length ? `${grades.length} materias` : 'sin_notas');

    if (target) {
        target.innerHTML = grades.length
            ? grades.map((grade) => `
                <tr>
                    <td><strong>${escapeHtml(grade.materia)}</strong></td>
                    <td>${escapeHtml(grade.grupo)}</td>
                    <td>${formatGrade(grade.nota1)}</td>
                    <td>${formatGrade(grade.nota2)}</td>
                    <td>${formatGrade(grade.nota3)}</td>
                    <td><strong>${formatGrade(grade.promedio)}</strong></td>
                    <td><span class="status-pill ${statusClass(grade.estado)}">${escapeHtml(statusLabel(grade.estado))}</span></td>
                </tr>
            `).join('')
            : '<tr><td colspan="7">Todavia no se publicaron calificaciones.</td></tr>';
    }

    if (attendanceTarget) {
        attendanceTarget.innerHTML = `
            <div><span>Clases registradas</span><strong>${escapeHtml(attendance.total || 0)}</strong></div>
            <div><span>Presentes</span><strong>${escapeHtml(attendance.presente || 0)}</strong></div>
            <div><span>Retrasos</span><strong>${escapeHtml(attendance.retraso || 0)}</strong></div>
            <div><span>Faltas</span><strong>${escapeHtml(attendance.falta || 0)}</strong></div>
        `;
    }
}

function renderAssignedCareer(assignment) {
    const target = qs('#postulantAssignedCareer');

    if (!assignment) {
        setStatus('#postulantCareerStatus', 'pendiente');
        return;
    }

    setStatus('#postulantCareerStatus', assignment.estado);

    if (target) {
        target.innerHTML = `
            <strong>${escapeHtml(assignment.nombre || assignment.codigo || 'Sin carrera asignada')}</strong>
            <span>Promedio final: ${formatGrade(assignment.promedio_final)}</span>
            <span>${assignment.opcion_asignada ? `Opcion asignada: ${escapeHtml(assignment.opcion_asignada)}` : ''}</span>
            <p>${escapeHtml(assignment.motivo || 'Resultado calculado segun nota y cupos disponibles.')}</p>
        `;
    }
}

function renderPanelGroupSummary(group) {
    setMessage(
        '#postulantGroupSummary',
        group ? `${group.codigo} | Turno ${group.turno || 'sin definir'}` : 'Todavia no tienes grupo asignado.',
    );
}

async function loadPostulantGroups() {
    try {
        const data = await apiRequest('/api/postulante/grupos-disponibles');
        currentEnrollment = data.inscripcion_actual || null;
        groups = data.grupos || [];
        renderCurrentEnrollment();
        renderGroupOptions();
    } catch (error) {
        setMessage('#postulantGroupOutput', error.data || error.message);
    }
}

function renderCurrentEnrollment() {
    const target = qs('#postulantGroupCurrent');

    if (!target) {
        return;
    }

    if (!currentEnrollment) {
        target.textContent = 'Todavia no tienes grupo asignado. Podras elegir uno cuando tu cuenta este habilitada.';
        return;
    }

    target.innerHTML = `
        Estas inscrito en <strong>${escapeHtml(currentEnrollment.id_grupo)}</strong>
        ${currentEnrollment.grupo?.turno ? `| Turno ${escapeHtml(currentEnrollment.grupo.turno)}` : ''}.
    `;
}

function renderGroupOptions() {
    const select = qs('#postulantGroupSelect');
    const form = qs('#postulantGroupForm');
    const button = form?.querySelector('button[type="submit"]');

    if (!select || !form || !button) {
        return;
    }

    if (currentEnrollment) {
        select.innerHTML = '<option value="">Ya tienes grupo asignado</option>';
        button.disabled = true;
        return;
    }

    button.disabled = !groups.length;
    select.innerHTML = groups.length
        ? `<option value="">Selecciona grupo</option>${groups.map((group) => `<option value="${escapeHtml(group.codigo)}">${escapeHtml(group.codigo)} - ${escapeHtml(group.turno || '')} (${escapeHtml(group.cupos_disponibles)} cupos)</option>`).join('')}`
        : '<option value="">No hay grupos disponibles</option>';
}

async function savePostulantGroup(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const button = form.querySelector('button[type="submit"]');
    const payload = formData(form);

    try {
        setButtonLoading(button, true, 'Inscribiendo...');
        const data = await apiRequest('/api/postulante/grupo', {
            method: 'POST',
            body: JSON.stringify(payload),
        });

        setMessage('#postulantGroupOutput', data);
        await Promise.all([loadPostulantGroups(), loadPostulantPanel()]);
    } catch (error) {
        setMessage('#postulantGroupOutput', error.data || error.message);
    } finally {
        if (!currentEnrollment) {
            setButtonLoading(button, false);
        }
    }
}

function setStatus(selector, status) {
    const target = qs(selector);

    if (!target) {
        return;
    }

    target.className = `status-pill ${statusClass(status)}`;
    target.textContent = statusLabel(status);
}

function statusLabel(status) {
    const labels = {
        sin_pago: 'Sin pago',
        sin_notas: 'Sin notas',
        pendiente: 'Pendiente',
        pendiente_pago: 'Pendiente de pago',
        pendiente_revision: 'Pendiente de revision',
        observado: 'Observado',
        validado: 'Validado',
        registrado: 'Registrado',
        pagado: 'Pagado',
        rechazado: 'Rechazado',
        habilitado: 'Habilitado',
        admitido: 'Admitido',
        propuesto: 'Propuesto',
        confirmado: 'Confirmado',
        aprobado: 'Aprobado',
        reprobado: 'Reprobado',
        asignado: 'Asignado',
        lista_espera: 'Lista de espera',
        sin_opcion: 'Sin opcion',
    };

    return labels[status] || String(status || 'Pendiente').replaceAll('_', ' ');
}

function formatGrade(value) {
    return value === null || value === undefined || value === ''
        ? '-'
        : escapeHtml(Number(value).toFixed(2).replace('.00', ''));
}

function renderEmptyTable(selector, colspan, message) {
    const target = qs(selector);

    if (target) {
        target.innerHTML = `<tr><td colspan="${colspan}">${escapeHtml(message)}</td></tr>`;
    }
}
