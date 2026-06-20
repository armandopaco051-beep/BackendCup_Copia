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

let reportOptions = {};
let currentReport = null;

export function initReportes() {
    const form = qs('#reportFilters');

    if (!form) {
        return;
    }

    form.addEventListener('submit', loadReport);
    form.elements.tipo.addEventListener('change', updateReportFields);
    form.elements.periodo.addEventListener('change', filterGroupsByPeriod);
    qs('[data-refresh-reports]')?.addEventListener('click', loadReport);
    qs('[data-clear-report]')?.addEventListener('click', clearFilters);
    document.querySelectorAll('[data-export-report]').forEach((button) => {
        button.addEventListener('click', () => exportReport(button.dataset.exportReport));
    });
    initVoiceReports();

    loadOptions();
}
// carga las opciones para los filtros
async function loadOptions() {
    try {
        reportOptions = await apiRequest('/api/reportes/opciones');
        updateGeminiAvailability(reportOptions.gemini_configurado);
        fillReportTypes();
        fillSelect('periodo', reportOptions.periodos || [], 'id', 'nombre', 'Todos los periodos');
        fillSelect('carrera', reportOptions.carreras || [], 'codigo', 'nombre', 'Todas las carreras');
        fillSelect('materia', reportOptions.materias || [], 'id', 'nombre', 'Todas las materias');
        fillSelect('docente', reportOptions.docentes || [], 'username', 'nombre', 'Todos los docentes');
        applyReportScope();
        filterGroupsByPeriod();
        updateReportFields();
        await loadReport();
    } catch (error) {
        setMessage('#reportOutput', error.data || error.message);
    }
}

function fillReportTypes() {
    const select = qs('#reportFilters')?.elements.tipo;
    const types = Array.isArray(reportOptions.tipos) ? reportOptions.tipos : [];

    if (!select) {
        return;
    }

    if (types.length === 0) {
        select.innerHTML = '<option value="">Sin reportes disponibles</option>';
        select.disabled = true;
        return;
    }

    select.disabled = false;
    const previous = select.value;
    select.innerHTML = types.map((type) => `
        <option value="${escapeHtml(type.codigo)}">${escapeHtml(type.nombre)}</option>
    `).join('');

    select.value = types.some((type) => String(type.codigo) === String(previous))
        ? previous
        : types[0].codigo;
}

function applyReportScope() {
    const form = qs('#reportFilters');
    const forcedTeacher = reportOptions.alcance?.docente_forzado;
    const teacherSelect = form?.elements.docente;

    if (!teacherSelect) {
        return;
    }

    teacherSelect.disabled = false;
    teacherSelect.closest('label')?.classList.remove('is-disabled');

    if (!forcedTeacher) {
        return;
    }

    teacherSelect.value = forcedTeacher;
    teacherSelect.disabled = true;
    teacherSelect.closest('label')?.classList.add('is-disabled');
    setMessage('#reportOutput', reportOptions.alcance.descripcion);
}

// actualiza la disponibilidad de voice reports
function updateGeminiAvailability(configured) {
    const badge = qs('#voiceSupportBadge');

    if (!configured) {
        badge.textContent = 'Falta clave de Gemini';
        badge.className = 'status-pill is-rejected';
        setVoiceStatus(
            'Gemini pendiente de configurar',
            'Agrega GEMINI_API_KEY en .env y ejecuta php artisan config:clear.',
            'error',
        );
    }
}

// carga el reporte
async function loadReport(event) {
    event?.preventDefault();

    const form = qs('#reportFilters');
    if (!form?.elements.tipo?.value || !validateForm(form)) {
        return;
    }

    const button = event?.submitter || qs('[data-refresh-reports]');
    setButtonLoading(button, true, 'Consultando...');

    try {
        const params = currentParams();
        currentReport = await apiRequest(`/api/reportes?${params.toString()}`);
        renderReport(currentReport);
        setMessage(
            '#reportOutput',
            currentReport.tipo === 'lista_admitidos' && !currentReport.lista_generada
                ? 'Todavia no se genero la asignacion de carreras. Usa el boton Generar o recalcular lista de admitidos.'
                : `Consulta completada: ${currentReport.resumen.total_resultados} resultado(s).`,
        );
    } catch (error) {
        setMessage('#reportOutput', error.data || error.message);
        renderError(error.data?.message || error.message);
    } finally {
        setButtonLoading(button, false);
    }
}

// actualiza los campos del reporte
function updateReportFields() {
    const form = qs('#reportFilters');
    if (!form?.elements.tipo) {
        return;
    }

    const type = form.elements.tipo.value;
    const states = reportOptions.estados?.[type] || [];

    fillSelect('estado', states.map((state) => ({ codigo: state, nombre: label(state) })), 'codigo', 'nombre', 'Todos los estados');

    const datesEnabled = ['postulantes', 'pagos'].includes(type);
    document.querySelectorAll('[data-report-date]').forEach((labelElement) => {
        const input = labelElement.querySelector('input');
        input.disabled = !datesEnabled;
        if (!datesEnabled) {
            input.value = '';
        }
        labelElement.classList.toggle('is-disabled', !datesEnabled);
    });

    const careerEnabled = [
        'postulantes',
        'lista_admitidos',
        'postulantes_aprobados',
        'postulantes_reprobados',
        'pagos',
        'calificaciones',
        'resultados_estudiantes',
        'estadisticas_materia',
        'rendimiento_grupos',
    ].includes(type);
    const careerSelect = form.elements.carrera;
    if (careerSelect) {
        careerSelect.disabled = !careerEnabled;
        careerSelect.closest('label')?.classList.toggle('is-disabled', !careerEnabled);
        if (!careerEnabled) {
            careerSelect.value = '';
        }
    }

    const subjectEnabled = [
        'calificaciones',
        'resultados_estudiantes',
        'postulantes_aprobados',
        'postulantes_reprobados',
        'estadisticas_materia',
        'grupos_habilitados',
        'docentes_grupo',
        'rendimiento_grupos',
    ].includes(type);
    const subjectSelect = form.elements.materia;
    if (subjectSelect) {
        subjectSelect.disabled = !subjectEnabled;
        subjectSelect.closest('label')?.classList.toggle('is-disabled', !subjectEnabled);
        if (!subjectEnabled) {
            subjectSelect.value = '';
        }
    }

    const guide = qs('#admittedListGuide');
    if (guide) {
        guide.hidden = type !== 'lista_admitidos';
    }
}

// filtra los grupos por periodo
function filterGroupsByPeriod() {
    const form = qs('#reportFilters');
    if (!form?.elements.periodo) {
        return;
    }

    const period = String(form.elements.periodo.value || '');
    const groups = (reportOptions.grupos || []).filter((group) => !period || String(group.periodo || '') === period);

    fillSelect(
        'grupo',
        groups.map((group) => ({
            codigo: group.codigo,
            nombre: `${group.codigo}${group.turno ? ` · ${label(group.turno)}` : ''}`,
        })),
        'codigo',
        'nombre',
        'Todos los grupos',
    );
}

// llena un select con opciones
function fillSelect(name, items, valueKey, labelKey, placeholder) {
    const select = qs('#reportFilters')?.elements[name];
    if (!select) {
        return;
    }

    const safeItems = Array.isArray(items) ? items : [];
    const previous = select.value;
    select.innerHTML = `<option value="">${escapeHtml(placeholder)}</option>${safeItems.map((item) => `
        <option value="${escapeHtml(item[valueKey])}">${escapeHtml(item[labelKey])}</option>
    `).join('')}`;

    if (safeItems.some((item) => String(item[valueKey]) === String(previous))) {
        select.value = previous;
    }
}

// obtiene los parametros actuales del formulario
function currentParams() {
    const raw = formData(qs('#reportFilters'));
    const params = new URLSearchParams();

    Object.entries(raw).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) {
            params.set(key, value);
        }
    });

    return params;
}

// renderiza el reporte
function renderReport(report) {
    qs('#reportTitle').textContent = report.titulo;
    qs('#reportCount').textContent = `${report.resumen.total_resultados} resultado(s), ${report.resumen.mostrados} mostrado(s)`;
    renderSummary(report.resumen);
    renderTable(report.columnas, report.datos);

}

// renderiza el resumen del reporte
function renderSummary(summary) {
    const container = qs('#reportSummary');
    const entries = Object.entries(summary);

    container.innerHTML = entries.map(([key, value]) => `
        <article>
            <span>${escapeHtml(label(key))}</span>
            <strong>${escapeHtml(formatValue(value))}</strong>
            <small>Datos de la consulta actual</small>
        </article>
    `).join('');
}

// renderiza la tabla del reporte
function renderTable(columns, rows) {
    const keys = Object.keys(columns);
    qs('#reportTableHead').innerHTML = `<tr>${Object.values(columns).map((title) => `<th>${escapeHtml(title)}</th>`).join('')}</tr>`;

    qs('#reportTableBody').innerHTML = rows.map((row) => `
        <tr>
            ${keys.map((key) => `<td>${renderCell(key, row[key])}</td>`).join('')}
        </tr>
    `).join('') || `<tr><td colspan="${keys.length}">No existen datos para los filtros seleccionados.</td></tr>`;
}

function renderCell(key, value) {
    if (['estado', 'pago', 'requisitos'].includes(key)) {
        return `<span class="status-pill ${statusClass(String(value))}">${escapeHtml(label(value))}</span>`;
    }

    return escapeHtml(value ?? '-');
}

function renderError(message) {
    qs('#reportTableHead').innerHTML = '<tr><th>Resultado</th></tr>';
    qs('#reportTableBody').innerHTML = `<tr><td>${escapeHtml(message)}</td></tr>`;
}

function exportReport(format) {
    const form = qs('#reportFilters');
    if (!validateForm(form)) {
        return;
    }

    const params = currentParams();
    params.delete('limite');
    const link = document.createElement('a');
    link.href = `/api/reportes/${format}?${params.toString()}`;
    link.download = format === 'excel' ? 'reporte.xlsx' : 'reporte.pdf';
    document.body.appendChild(link);
    link.click();
    link.remove();
}

function clearFilters() {
    const form = qs('#reportFilters');
    form.reset();
    applyReportScope();
    updateReportFields();
    filterGroupsByPeriod();
    loadReport();
}

function label(value) {
    return String(value ?? '')
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function formatValue(value) {
    if (typeof value === 'number') {
        return new Intl.NumberFormat('es-BO', { maximumFractionDigits: 2 }).format(value);
    }

    return value;
}

function initVoiceReports() {
    const listenButton = qs('#voiceReportListen');
    const processButton = qs('#voiceReportProcess');
    const supportBadge = qs('#voiceSupportBadge');
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    processButton?.addEventListener('click', processVoiceCommand);

    if (!SpeechRecognition) {
        supportBadge.textContent = 'Microfono no compatible';
        supportBadge.className = 'status-pill is-rejected';
        listenButton.disabled = true;
        setVoiceStatus(
            'Escribe el comando',
            'Este navegador no admite reconocimiento de voz. Puedes usar el campo de texto y Gemini funcionara normalmente.',
            'error',
        );
        return;
    }

    supportBadge.textContent = 'Microfono disponible';
    supportBadge.className = 'status-pill is-admitted';

    const recognition = new SpeechRecognition();
    recognition.lang = 'es-BO';
    recognition.continuous = false;
    recognition.interimResults = true;
    recognition.maxAlternatives = 1;

    listenButton.addEventListener('click', () => recognition.start());

    recognition.addEventListener('start', () => {
        listenButton.disabled = true;
        listenButton.textContent = 'Escuchando...';
        setVoiceStatus('Escuchando', 'Habla con claridad e indica tipo, filtros y formato del reporte.', 'listening');
    });

    recognition.addEventListener('result', (event) => {
        const transcript = Array.from(event.results)
            .map((result) => result[0].transcript)
            .join(' ')
            .trim();
        const finalResult = event.results[event.results.length - 1].isFinal;

        qs('#voiceReportCommand').value = transcript;
        setVoiceStatus(
            finalResult ? 'Comando reconocido' : 'Escuchando',
            transcript,
            finalResult ? 'success' : 'listening',
        );
    });

    recognition.addEventListener('end', () => {
        listenButton.disabled = false;
        listenButton.textContent = 'Escuchar comando';

        if (qs('#voiceReportCommand').value.trim().length >= 5) {
            processVoiceCommand();
        }
    });

    recognition.addEventListener('error', (event) => {
        listenButton.disabled = false;
        listenButton.textContent = 'Escuchar comando';
        const message = event.error === 'not-allowed'
            ? 'Debes permitir el acceso al microfono en el navegador.'
            : 'No se pudo reconocer la voz. Intenta nuevamente o escribe el comando.';
        setVoiceStatus('No se pudo escuchar', message, 'error');
    });
}

async function processVoiceCommand() {
    const command = qs('#voiceReportCommand')?.value.trim();
    const button = qs('#voiceReportProcess');

    if (!command || command.length < 5) {
        setVoiceStatus('Falta el comando', 'Escribe o dicta una solicitud de al menos cinco caracteres.', 'error');
        return;
    }

    setButtonLoading(button, true, 'Interpretando...');
    setVoiceStatus('Gemini esta interpretando', 'Convirtiendo la solicitud en filtros seguros para Laravel.', 'processing');
    qs('#voiceReportResult').hidden = true;

    try {
        const data = await apiRequest('/api/reportes/voz', {
            method: 'POST',
            body: JSON.stringify({ comando: command }),
        });

        applyVoiceInterpretation(data.interpretacion);
        currentReport = data.reporte;
        renderReport(currentReport);
        renderVoiceResult(data);
        setMessage('#reportOutput', `Consulta por IA completada: ${currentReport.resumen.total_resultados} resultado(s).`);
        setVoiceStatus('Reporte generado', data.message, 'success');
        speakResponse(data.message);

        if (data.descarga) {
            window.setTimeout(() => window.location.assign(data.descarga), 650);
        }
    } catch (error) {
        const message = error.data?.errors?.gemini?.[0]
            || error.data?.message
            || error.message;
        setVoiceStatus('No se pudo procesar', message, 'error');
        setMessage('#reportOutput', message);
    } finally {
        setButtonLoading(button, false);
    }
}

function applyVoiceInterpretation(interpretation) {
    const form = qs('#reportFilters');
    const filters = interpretation.filtros || {};

    setFormValue(form, 'tipo', interpretation.tipo);
    updateReportFields();
    setFormValue(form, 'buscar', filters.buscar || '');
    setFormValue(form, 'periodo', filters.periodo || '');
    filterGroupsByPeriod();
    setFormValue(form, 'carrera', filters.carrera || '');
    setFormValue(form, 'estado', filters.estado || '');
    setFormValue(form, 'grupo', filters.grupo || '');
    setFormValue(form, 'materia', filters.materia || '');
    setFormValue(form, 'docente', filters.docente || '');
    setFormValue(form, 'fecha_inicio', filters.fecha_inicio || '');
    setFormValue(form, 'fecha_fin', filters.fecha_fin || '');
}

function setFormValue(form, name, value) {
    if (form.elements[name]) {
        form.elements[name].value = value;
    }
}

function renderVoiceResult(data) {
    const result = qs('#voiceReportResult');
    const interpretation = data.interpretacion;
    const filters = interpretation.filtros || {};
    const tags = [
        ['Reporte', label(interpretation.tipo)],
        ['Formato', label(interpretation.formato)],
        ...Object.entries(filters).map(([key, value]) => [label(key), value]),
    ];

    qs('#voiceReportAnswer').textContent = data.message;
    qs('#voiceReportFilters').innerHTML = tags.map(([key, value]) => `
        <span><strong>${escapeHtml(key)}:</strong> ${escapeHtml(value)}</span>
    `).join('');

    const download = qs('#voiceReportDownload');
    if (data.descarga) {
        download.href = data.descarga;
        download.textContent = `Descargar ${interpretation.formato.toUpperCase()}`;
        download.hidden = false;
    } else {
        download.hidden = true;
    }

    result.hidden = false;
}

function setVoiceStatus(title, detail, state = '') {
    const status = qs('#voiceReportStatus');
    if (!status) {
        return;
    }

    status.className = `voice-report-status ${state ? `is-${state}` : ''}`;
    status.querySelector('strong').textContent = title;
    status.querySelector('p').textContent = detail;
}

function speakResponse(message) {
    if (!qs('#voiceReportSpeak')?.checked || !('speechSynthesis' in window)) {
        return;
    }

    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(message);
    utterance.lang = 'es-BO';
    utterance.rate = 1;
    window.speechSynthesis.speak(utterance);
}
