const carouselSlides = [
    {
        eyebrow: 'Inscribete en linea, simple y seguro',
        title: 'Construye tu camino',
        text: 'Gestiona tu postulacion academica desde un portal institucional claro y confiable.',
    },
    {
        eyebrow: 'Postulate a una carrera que cambiara tu vida',
        title: 'Conocimiento que transforma',
        text: 'Accede a una experiencia academica orientada a ciencia, tecnologia e innovacion.',
    },
    {
        eyebrow: 'Universidad Autonoma Gabriel Rene Moreno',
        title: 'Bienvenido a FICCT',
        text: 'Inicia tu proceso de admision en una facultad orientada a tecnologia, investigacion e innovacion.',
    },
];

const $ = (selector) => document.querySelector(selector);
const $$ = (selector) => Array.from(document.querySelectorAll(selector));
let cachedUsers = [];
let cachedRoles = [];
let cachedPermissions = [];
let selectedRoleId = null;

function initLoginCarousel() {
    const slides = $$('.auth-slide');
    const dots = $$('.auth-dots button');
    const eyebrow = $('#slideEyebrow');
    const title = $('#slideTitle');
    const text = $('#slideText');

    if (!slides.length || !dots.length) {
        return;
    }

    let current = 0;
    let timer;

    const setSlide = (index) => {
        current = index;

        slides.forEach((slide, slideIndex) => {
            slide.classList.toggle('is-active', slideIndex === current);
        });

        dots.forEach((dot, dotIndex) => {
            dot.classList.toggle('is-active', dotIndex === current);
        });

        eyebrow.textContent = carouselSlides[current].eyebrow;
        title.textContent = carouselSlides[current].title;
        text.textContent = carouselSlides[current].text;
    };

    const start = () => {
        timer = window.setInterval(() => {
            setSlide((current + 1) % slides.length);
        }, 5200);
    };

    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            window.clearInterval(timer);
            setSlide(Number(dot.dataset.dot));
            start();
        });
    });

    start();
}

function showFieldError(field, message) {
    const error = document.querySelector(`[data-error-for="${field}"]`);

    if (error) {
        error.textContent = message || '';
    }
}

function showLoginAlert(message, type = 'error') {
    const alert = $('#loginAlert');

    if (!alert) {
        return;
    }

    alert.hidden = !message;
    alert.textContent = message || '';
    alert.style.background = type === 'success' ? 'var(--success-bg)' : 'var(--danger-bg)';
    alert.style.color = type === 'success' ? 'var(--success)' : 'var(--danger)';
    alert.style.borderColor = type === 'success' ? '#bcebd0' : '#ffd0cc';
}

function validateLogin(username, password) {
    let valid = true;

    showFieldError('username', '');
    showFieldError('password', '');
    showLoginAlert('');

    if (!username) {
        showFieldError('username', 'El usuario es obligatorio.');
        valid = false;
    }

    if (!password) {
        showFieldError('password', 'La contrasena es obligatoria.');
        valid = false;
    } else if (password.length < 6) {
        showFieldError('password', 'La contrasena debe tener al menos 6 caracteres.');
        valid = false;
    }

    return valid;
}

function applyBackendErrors(errors = {}) {
    Object.entries(errors).forEach(([field, messages]) => {
        showFieldError(field, Array.isArray(messages) ? messages[0] : String(messages));
    });
}

async function apiRequest(path, options = {}) {
    const response = await fetch(path, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...(options.headers || {}),
        },
        ...options,
    });

    const text = await response.text();
    const data = text ? JSON.parse(text) : {};

    if (!response.ok) {
        const error = new Error(data.message || 'La solicitud no pudo completarse.');
        error.data = data;
        error.status = response.status;
        throw error;
    }

    return data;
}

async function parseResponse(response) {
    const text = await response.text();

    if (!text) {
        return {};
    }

    try {
        return JSON.parse(text);
    } catch {
        return {
            message: response.ok
                ? 'La respuesta del servidor no esta en formato JSON.'
                : `El servidor respondio ${response.status}. Revisa que estes abriendo el sistema desde http://127.0.0.1:8000/login.`,
        };
    }
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function formValues(form) {
    return Object.fromEntries(new FormData(form).entries());
}

function writeOutput(selector, payload) {
    const output = $(selector);

    if (!output) {
        return;
    }

    output.textContent = typeof payload === 'string'
        ? payload
        : JSON.stringify(payload, null, 2);
}

function compactPayload(payload) {
    return Object.fromEntries(
        Object.entries(payload).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    );
}

function setButtonLoading(button, isLoading, label = 'Procesando...') {
    if (!button) {
        return;
    }

    if (isLoading) {
        button.dataset.originalText = button.textContent;
        button.disabled = true;
        button.textContent = label;
        return;
    }

    button.disabled = false;
    button.textContent = button.dataset.originalText || button.textContent;
}

function initLoginForm() {
    const form = $('#loginForm');
    const button = $('#loginButton');
    const password = $('#password');
    const toggle = $('[data-toggle-password]');

    if (!form) {
        return;
    }

    toggle?.addEventListener('click', () => {
        const isPassword = password.type === 'password';
        password.type = isPassword ? 'text' : 'password';
        toggle.textContent = isPassword ? 'Ocultar' : 'Mostrar';
        toggle.setAttribute('aria-label', isPassword ? 'Ocultar contrasena' : 'Mostrar contrasena');
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const username = $('#username').value.trim();
        const pass = password.value.trim();

        if (!validateLogin(username, pass)) {
            return;
        }

        button.disabled = true;
        button.querySelector('span').textContent = 'Validando...';

        try {
            const response = await fetch('/api/auth/login', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    username,
                    password: pass,
                }),
            });

            const data = await parseResponse(response);

            if (!response.ok) {
                applyBackendErrors(data.errors);
                showLoginAlert(data.message || 'No se pudo iniciar sesion. Verifica tus credenciales.');
                return;
            }

            showLoginAlert('Sesion iniciada correctamente. Redirigiendo...', 'success');
            window.setTimeout(() => {
                window.location.href = '/dashboard';
            }, 450);
        } catch {
            showLoginAlert('No se pudo conectar con el servidor. Revisa que Laravel este ejecutandose.');
        } finally {
            button.disabled = false;
            button.querySelector('span').textContent = 'Ingresar';
        }
    });
}

async function initDashboard() {
    const dashboardUser = $('#dashboardUser');
    const logoutButton = $('#logoutButton');

    if (!dashboardUser) {
        return;
    }

    try {
        const response = await fetch('/api/auth/me', {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            window.location.href = '/login';
            return;
        }

        const data = await response.json();
        const usuario = data.usuario;
        dashboardUser.textContent = `${usuario.username} | ${usuario.rol?.nombre || usuario.tipo}`;
        fillProfile(usuario);
        loadDashboardData();
    } catch {
        dashboardUser.textContent = 'No se pudo cargar la informacion de sesion.';
    }

    logoutButton?.addEventListener('click', async () => {
        await fetch('/api/auth/logout', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
            },
        });

        window.location.href = '/login';
    });

    initUseCasePanel();
}

function initUseCasePanel() {
    if (!$('.portal-shell')) {
        return;
    }

    $('[data-load-users]')?.addEventListener('click', loadUsers);
    $('[data-load-security]')?.addEventListener('click', loadSecurity);

    $('#createUserForm')?.addEventListener('submit', submitCreateUser);
    $('#assignRoleForm')?.addEventListener('submit', submitAssignRole);
    $('#createRoleForm')?.addEventListener('submit', submitCreateRole);
    $('#createPermissionForm')?.addEventListener('submit', submitCreatePermission);
    $('#syncPermissionsForm')?.addEventListener('submit', submitSyncPermissions);
    $('#permissionMatrixForm')?.addEventListener('submit', submitPermissionMatrix);
    $('#resetPasswordForm')?.addEventListener('submit', submitResetPassword);
    $('#preinscriptionForm')?.addEventListener('submit', submitPreinscription);
    $('#requirementsForm')?.addEventListener('submit', submitRequirements);
    $('#paymentIntentForm')?.addEventListener('submit', submitPaymentIntent);
    $('#paymentStatusForm')?.addEventListener('submit', submitPaymentStatus);
    $('#enableApplicantForm')?.addEventListener('submit', submitEnableApplicant);
    $('#enableStatusForm')?.addEventListener('submit', submitEnableStatus);
    $('#userSearch')?.addEventListener('input', (event) => {
        renderUsers(cachedUsers, event.currentTarget.value);
    });

    if ($('#usersTable')) {
        loadUsers();
    }

    if ($('#securityOutput')) {
        loadSecurity();
    }
}

function fillProfile(usuario) {
    if (!$('#profileSummary')) {
        return;
    }

    $('#profileName').textContent = usuario.perfil?.nombre || usuario.username;
    $('#profileRole').textContent = `${usuario.username} | ${usuario.rol?.nombre || usuario.tipo}`;
    writeOutput('#profileOutput', usuario);
}

function formatNumber(value) {
    return new Intl.NumberFormat('es-BO').format(Number(value || 0));
}

async function loadDashboardData() {
    if (!$('#metricPostulantes')) {
        return;
    }

    try {
        const data = await apiRequest('/api/dashboard');
        const metrics = data.metricas || {};

        $('#metricPostulantes').textContent = formatNumber(metrics.postulantes);
        $('#metricPreinscripciones').textContent = formatNumber(metrics.preinscripciones);
        $('#metricMatriculas').textContent = formatNumber(metrics.matriculas_pagadas);
        $('#metricAdmitidos').textContent = formatNumber(metrics.admitidos);

        renderRecentPreinscriptions(data.preinscripciones_recientes || []);
    } catch (error) {
        $('#metricPostulantes').textContent = '0';
        $('#metricPreinscripciones').textContent = '0';
        $('#metricMatriculas').textContent = '0';
        $('#metricAdmitidos').textContent = '0';
        renderRecentPreinscriptions([], error.data?.message || error.message);
    }
}

function renderRecentPreinscriptions(preinscriptions, errorMessage = '') {
    const container = $('#recentPreinscriptions');

    if (!container) {
        return;
    }

    if (errorMessage) {
        container.innerHTML = `
            <div>
                <span>
                    <strong>No se pudo cargar el dashboard</strong>
                    <small>${errorMessage}</small>
                </span>
                <em>Pendiente</em>
            </div>
        `;
        return;
    }

    if (!preinscriptions.length) {
        container.innerHTML = `
            <div>
                <span>
                    <strong>Sin preinscripciones registradas</strong>
                    <small>Cuando registres postulantes apareceran aqui.</small>
                </span>
                <em>Pendiente</em>
            </div>
        `;
        return;
    }

    container.innerHTML = preinscriptions.map((item) => {
        const stateClass = {
            pagado: 'is-paid',
            validado: 'is-validated',
            admitido: 'is-admitted',
        }[item.estado?.tipo] || '';

        return `
            <div>
                <span>
                    <strong>${item.nombre || item.username}</strong>
                    <small>${item.codigo_preinscripcion} · ${item.carrera}</small>
                </span>
                <em class="${stateClass}">${item.estado?.label || 'Pendiente'}</em>
            </div>
        `;
    }).join('');
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

async function loadUsers() {
    if (!$('#usersTable')) {
        return;
    }

    try {
        const data = await apiRequest('/api/usuarios');
        cachedUsers = data.usuarios || [];
        renderUsers(cachedUsers, $('#userSearch')?.value || '');
        writeOutput('#usersOutput', data);
    } catch (error) {
        writeOutput('#usersOutput', error.data || error.message);
    }
}

function renderUsers(users, query = '') {
    const table = $('#usersTable');
    const count = $('#usersCount');

    if (!table) {
        return;
    }

    const normalizedQuery = query.trim().toLowerCase();
    const filtered = users.filter((user) => {
        const perfil = user.perfil || {};
        const searchable = [
            user.username,
            user.tipo,
            user.rol?.nombre,
            perfil.nombre,
            perfil.correo,
            user.correo,
        ].filter(Boolean).join(' ').toLowerCase();

        return !normalizedQuery || searchable.includes(normalizedQuery);
    });

    const rows = filtered.map((user) => {
        const perfil = user.perfil || {};
        const nombre = perfil.nombre || user.username;
        const correo = perfil.correo || user.correo || 'Sin correo';
        const rol = user.rol?.nombre || 'Sin rol';

        return `
            <tr>
                <td>
                    <strong>${escapeHtml(nombre)}</strong>
                    <small>${escapeHtml(user.username)} · ${escapeHtml(user.tipo)}</small>
                </td>
                <td>${escapeHtml(correo)}</td>
                <td>${escapeHtml(rol)}</td>
                <td><span class="status-pill">Activo</span></td>
                <td>
                    <div class="table-actions">
                        <a href="/dashboard/perfil" aria-label="Ver usuario ${escapeHtml(user.username)}">Ver</a>
                        <a href="#createUserPanel" aria-label="Editar usuario ${escapeHtml(user.username)}">Editar</a>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    table.innerHTML = rows || '<tr><td colspan="5">No hay usuarios registrados.</td></tr>';

    if (count) {
        count.textContent = `${filtered.length} usuario(s) encontrado(s)`;
    }
}

async function loadSecurity() {
    if (!$('#securityOutput')) {
        return;
    }

    try {
        const [roles, permisos] = await Promise.all([
            apiRequest('/api/roles'),
            apiRequest('/api/permisos'),
        ]);

        cachedRoles = roles.roles || [];
        cachedPermissions = permisos.permisos || [];
        renderRolesCards(cachedRoles);
        renderPermissionMatrix(selectedRoleId || cachedRoles[0]?.id);
        writeOutput('#securityOutput', { roles: roles.roles, permisos: permisos.permisos });
    } catch (error) {
        writeOutput('#securityOutput', error.data || error.message);
    }
}

function renderRolesCards(roles) {
    const container = $('#rolesCards');

    if (!container) {
        return;
    }

    if (!roles.length) {
        container.innerHTML = `
            <article class="role-summary-card">
                <span class="role-shield">□</span>
                <strong>Sin roles registrados</strong>
                <small>Registra un rol para comenzar</small>
            </article>
        `;
        return;
    }

    container.innerHTML = roles.map((role) => `
        <button class="role-summary-card ${Number(role.id) === Number(selectedRoleId || roles[0].id) ? 'is-selected' : ''}"
            type="button"
            data-role-card="${role.id}">
            <span class="role-shield">□</span>
            <strong>${escapeHtml(role.nombre)}</strong>
            <small>${formatNumber(role.usuarios_count || 0)} usuario(s)</small>
        </button>
    `).join('');

    $$('[data-role-card]').forEach((button) => {
        button.addEventListener('click', () => {
            renderPermissionMatrix(Number(button.dataset.roleCard));
        });
    });
}

function permissionGroup(permissionName) {
    const name = permissionName.toLowerCase();

    if (name.includes('usuario') || name.includes('rol')) {
        return 'Usuarios';
    }

    if (name.includes('postulante') || name.includes('carrera') || name.includes('grupo')) {
        return 'Preinscripciones';
    }

    if (name.includes('pago')) {
        return 'Matriculas';
    }

    if (name.includes('nota') || name.includes('promedio') || name.includes('estado_final')) {
        return 'Calificaciones';
    }

    if (name.includes('reporte') || name.includes('pdf') || name.includes('excel') || name.includes('dashboard')) {
        return 'Reportes';
    }

    return 'Otros';
}

function permissionLabel(permissionName) {
    return permissionName
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function renderPermissionMatrix(roleId) {
    const rows = $('#permissionMatrixRows');
    const title = $('#permissionMatrixTitle');
    const hiddenRole = $('#matrixRoleId');

    if (!rows || !cachedRoles.length) {
        return;
    }

    selectedRoleId = Number(roleId);
    const role = cachedRoles.find((item) => Number(item.id) === selectedRoleId) || cachedRoles[0];
    const assigned = new Set((role.permisos || []).map((permission) => Number(permission.codigo)));

    if (title) {
        title.textContent = `Matriz de permisos · ${role.nombre}`;
    }

    if (hiddenRole) {
        hiddenRole.value = role.id;
    }

    const grouped = cachedPermissions.reduce((groups, permission) => {
        const group = permissionGroup(permission.nombre);
        groups[group] = groups[group] || [];
        groups[group].push(permission);
        return groups;
    }, {});

    rows.innerHTML = Object.entries(grouped).map(([group, permissions]) => `
        <div class="permission-row">
            <strong>${escapeHtml(group)}</strong>
            <span>
                ${permissions.map((permission) => `
                    <label>
                        <input type="checkbox"
                            name="permisos[]"
                            value="${permission.codigo}"
                            ${assigned.has(Number(permission.codigo)) ? 'checked' : ''}>
                        ${escapeHtml(permissionLabel(permission.nombre))}
                    </label>
                `).join('')}
            </span>
        </div>
    `).join('');

    renderRolesCards(cachedRoles);
}

async function submitCreateUser(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const values = formValues(form);
    const button = form.querySelector('button[type="submit"]');

    if (values.tipo === 'postulante') {
        writeOutput('#usersOutput', 'Para crear postulantes usa CU-06 Preinscripcion, porque requiere datos academicos completos.');
        return;
    }

    setButtonLoading(button, true);

    try {
        const perfil = values.tipo === 'docente'
            ? compactPayload({
                nombre: values.nombre,
                especializacion: values.especializacion,
                maestria: values.maestria,
            })
            : compactPayload({
                nombre: values.nombre,
                telefono: values.telefono,
                ciudad: values.ciudad,
            });

        const data = await apiRequest('/api/usuarios', {
            method: 'POST',
            body: JSON.stringify({
                username: values.username,
                password: values.password,
                tipo: values.tipo,
                perfil,
            }),
        });

        form.reset();
        writeOutput('#usersOutput', data);
        loadUsers();
    } catch (error) {
        writeOutput('#usersOutput', error.data || error.message);
    } finally {
        setButtonLoading(button, false);
    }
}

async function submitAssignRole(event) {
    event.preventDefault();
    const values = formValues(event.currentTarget);

    try {
        const data = await apiRequest(`/api/usuarios/${values.username}/rol`, {
            method: 'PATCH',
            body: JSON.stringify({ codigo_rol: Number(values.codigo_rol) }),
        });

        writeOutput('#usersOutput', data);
        loadUsers();
    } catch (error) {
        writeOutput('#usersOutput', error.data || error.message);
    }
}

async function submitCreateRole(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const values = formValues(form);

    try {
        const data = await apiRequest('/api/roles', {
            method: 'POST',
            body: JSON.stringify({ nombre: values.nombre }),
        });

        form.reset();
        writeOutput('#securityOutput', data);
        loadSecurity();
    } catch (error) {
        writeOutput('#securityOutput', error.data || error.message);
    }
}

async function submitCreatePermission(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const values = formValues(form);

    try {
        const data = await apiRequest('/api/permisos', {
            method: 'POST',
            body: JSON.stringify({ nombre: values.nombre }),
        });

        form.reset();
        writeOutput('#securityOutput', data);
        loadSecurity();
    } catch (error) {
        writeOutput('#securityOutput', error.data || error.message);
    }
}

async function submitSyncPermissions(event) {
    event.preventDefault();
    const values = formValues(event.currentTarget);
    const permisos = values.permisos.split(',').map((value) => Number(value.trim())).filter(Boolean);

    try {
        const data = await apiRequest(`/api/roles/${values.rol_id}/permisos`, {
            method: 'PUT',
            body: JSON.stringify({ permisos }),
        });

        writeOutput('#securityOutput', data);
        loadSecurity();
    } catch (error) {
        writeOutput('#securityOutput', error.data || error.message);
    }
}

async function submitPermissionMatrix(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const roleId = Number(form.elements.rol_id.value);
    const permisos = Array.from(form.querySelectorAll('input[name="permisos[]"]:checked'))
        .map((input) => Number(input.value));

    try {
        const data = await apiRequest(`/api/roles/${roleId}/permisos`, {
            method: 'PUT',
            body: JSON.stringify({ permisos }),
        });

        writeOutput('#securityOutput', data);
        selectedRoleId = roleId;
        loadSecurity();
    } catch (error) {
        writeOutput('#securityOutput', error.data || error.message);
    }
}

async function submitResetPassword(event) {
    event.preventDefault();
    const values = formValues(event.currentTarget);

    try {
        const data = await apiRequest(`/api/usuarios/${values.username}/restablecer-password`, {
            method: 'POST',
            body: JSON.stringify({
                password: values.password,
                password_confirmation: values.password_confirmation,
            }),
        });

        event.currentTarget.reset();
        writeOutput('#passwordOutput', data);
    } catch (error) {
        writeOutput('#passwordOutput', error.data || error.message);
    }
}

async function submitPreinscription(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const values = compactPayload(formValues(form));

    try {
        const data = await apiRequest('/api/preinscripciones', {
            method: 'POST',
            body: JSON.stringify(values),
        });

        form.reset();
        writeOutput('#preinscriptionOutput', data);
        loadUsers();
    } catch (error) {
        writeOutput('#preinscriptionOutput', error.data || error.message);
    }
}

async function submitRequirements(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const values = formValues(form);

    try {
        const data = await apiRequest(`/api/postulantes/${values.username}/requisitos`, {
            method: 'POST',
            body: JSON.stringify(compactPayload({
                ci_entregado: form.elements.ci_entregado.checked,
                titulo_entregado: form.elements.titulo_entregado.checked,
                libretas_entregadas: form.elements.libretas_entregadas.checked,
                validado_por: values.validado_por,
                observacion: values.observacion,
            })),
        });

        writeOutput('#requirementsOutput', data);
    } catch (error) {
        writeOutput('#requirementsOutput', error.data || error.message);
    }
}

async function submitPaymentIntent(event) {
    event.preventDefault();
    const values = formValues(event.currentTarget);

    try {
        const data = await apiRequest(`/api/postulantes/${values.username}/pago-matricula/intento`, {
            method: 'POST',
            body: JSON.stringify(compactPayload({
                registrado_por: values.registrado_por,
                observacion: values.observacion,
            })),
        });

        writeOutput('#paymentOutput', data);
    } catch (error) {
        writeOutput('#paymentOutput', error.data || error.message);
    }
}

async function submitPaymentStatus(event) {
    event.preventDefault();
    const values = formValues(event.currentTarget);

    try {
        const data = await apiRequest(`/api/postulantes/${values.username}/pago-matricula`);
        writeOutput('#paymentOutput', data);
    } catch (error) {
        writeOutput('#paymentOutput', error.data || error.message);
    }
}

async function submitEnableApplicant(event) {
    event.preventDefault();
    const values = formValues(event.currentTarget);

    try {
        const data = await apiRequest(`/api/postulantes/${values.username}/habilitacion`, {
            method: 'POST',
            body: JSON.stringify(compactPayload({ observacion: values.observacion })),
        });

        writeOutput('#enableOutput', data);
        loadUsers();
    } catch (error) {
        writeOutput('#enableOutput', error.data || error.message);
    }
}

async function submitEnableStatus(event) {
    event.preventDefault();
    const values = formValues(event.currentTarget);

    try {
        const data = await apiRequest(`/api/postulantes/${values.username}/habilitacion`);
        writeOutput('#enableOutput', data);
    } catch (error) {
        writeOutput('#enableOutput', error.data || error.message);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initLoginCarousel();
    initLoginForm();
    initDashboard();
});
