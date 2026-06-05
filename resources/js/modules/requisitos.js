import { apiRequest, cleanPayload, escapeHtml, formData, qs, qsa, setMessage } from './api';

let applicants = [];
let selectedApplicant = null;
let documents = {
    ci_entregado: false,
    titulo_entregado: false,
    libretas_entregadas: false,
};

const documentList = [
    { key: 'ci_entregado', label: 'Fotocopia de cedula' },
    { key: 'titulo_entregado', label: 'Diploma de bachiller' },
    { key: 'libretas_entregadas', label: 'Libretas escolares' },
];

export function initRequisitos() {
    if (!qs('#requirementsApplicants')) {
        return;
    }

    qs('#requirementSearch')?.addEventListener('input', (event) => renderApplicants(event.currentTarget.value));
    qs('#requirementsForm')?.addEventListener('submit', saveRequirements);
    qs('[data-request-corrections]')?.addEventListener('click', () => {
        documents = { ci_entregado: false, titulo_entregado: false, libretas_entregadas: false };
        renderDocuments();
        showRequirementsMessage('Marca nuevamente los documentos entregados por el postulante.', false);
    });

    loadApplicants();
}

async function loadApplicants() {
    try {
        const data = await apiRequest('/api/preinscripciones');
        applicants = data.preinscripciones || [];
        renderApplicants(qs('#requirementSearch')?.value || '');

        if (selectedApplicant) {
            selectApplicant(selectedApplicant.username);
            return;
        }

        if (applicants[0]) {
            selectApplicant(applicants[0].username);
        }
    } catch (error) {
        qs('#requirementsApplicants').innerHTML = `<p>${escapeHtml(error.data?.message || error.message)}</p>`;
    }
}

function renderApplicants(filter = '') {
    const target = qs('#requirementsApplicants');
    const query = filter.trim().toLowerCase();

    if (!target) {
        return;
    }

    target.innerHTML = applicants.filter((applicant) => [
        applicant.folio,
        applicant.username,
        applicant.ci,
        applicant.nombre,
        applicant.carrera,
    ].filter(Boolean).join(' ').toLowerCase().includes(query)).map((applicant) => `
        <button class="${selectedApplicant?.username === applicant.username ? 'is-selected' : ''}" type="button" data-requirement-applicant="${escapeHtml(applicant.username)}">
            <strong>${escapeHtml(applicant.nombre)}</strong>
            <small>${escapeHtml(applicant.folio)} - ${escapeHtml(applicant.carrera)}</small>
        </button>
    `).join('') || '<p>No hay postulantes registrados.</p>';

    qsa('[data-requirement-applicant]').forEach((button) => {
        button.addEventListener('click', () => selectApplicant(button.dataset.requirementApplicant));
    });
}

async function selectApplicant(username) {
    selectedApplicant = applicants.find((applicant) => applicant.username === username) || null;

    if (!selectedApplicant) {
        return;
    }

    qs('#requirementUsername').value = selectedApplicant.username;
    qs('#requirementStudentName').textContent = selectedApplicant.nombre;
    qs('#requirementStudentMeta').textContent = `${selectedApplicant.folio} - ${selectedApplicant.carrera} - CI ${selectedApplicant.ci}`;

    try {
        const data = await apiRequest(`/api/postulantes/${encodeURIComponent(username)}/requisitos`);
        const requirements = data.requisitos || {};
        documents = {
            ci_entregado: Boolean(requirements.ci_entregado),
            titulo_entregado: Boolean(requirements.titulo_entregado),
            libretas_entregadas: Boolean(requirements.libretas_entregadas),
        };
        showRequirementsMessage(data.estado === 'validado' ? 'Requisitos fisicos ya validados.' : '', data.estado === 'validado');
    } catch {
        documents = { ci_entregado: false, titulo_entregado: false, libretas_entregadas: false };
        showRequirementsMessage('', false);
    }

    renderApplicants(qs('#requirementSearch')?.value || '');
    renderDocuments();
}

function renderDocuments() {
    const target = qs('#requirementsDocuments');
    const progress = qs('#requirementsProgress');

    if (!target) {
        return;
    }

    const validCount = documentList.filter((item) => documents[item.key]).length;

    if (progress) {
        progress.textContent = `${validCount} de ${documentList.length} validados`;
        progress.classList.toggle('is-admitted', validCount === documentList.length);
    }

    target.innerHTML = documentList.map((item) => {
        const checked = Boolean(documents[item.key]);

        return `
            <div class="requirement-document ${checked ? 'is-valid' : ''}">
                <span class="document-state" aria-hidden="true"></span>
                <strong>${escapeHtml(item.label)}</strong>
                <button type="button" data-document-key="${item.key}">${checked ? 'Validado' : 'Validar'}</button>
            </div>
        `;
    }).join('');

    qsa('[data-document-key]').forEach((button) => {
        button.addEventListener('click', () => {
            documents[button.dataset.documentKey] = !documents[button.dataset.documentKey];
            renderDocuments();
        });
    });
}

async function saveRequirements(event) {
    event.preventDefault();

    const values = formData(event.currentTarget);
    const username = values.username || selectedApplicant?.username;

    if (!username) {
        showRequirementsMessage('Selecciona un postulante antes de validar requisitos.', false);
        return;
    }

    try {
        const data = await apiRequest(`/api/postulantes/${encodeURIComponent(username)}/requisitos`, {
            method: 'POST',
            body: JSON.stringify(cleanPayload({
                ci_entregado: documents.ci_entregado,
                titulo_entregado: documents.titulo_entregado,
                libretas_entregadas: documents.libretas_entregadas,
                validado_por: values.validado_por,
                observacion: values.observacion,
            })),
        });

        showRequirementsMessage(data.message || 'Requisitos fisicos validados correctamente.', data.estado === 'validado');
        loadApplicants();
    } catch (error) {
        showRequirementsMessage(error.data?.message || error.message, false);
    }
}

function showRequirementsMessage(message, success = false) {
    setMessage('#requirementsOutput', message);

    const output = qs('#requirementsOutput');

    if (!output) {
        return;
    }

    output.hidden = !message;
    output.classList.toggle('is-success', success);
}
