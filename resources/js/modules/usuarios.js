import {
    apiRequest,
    cleanPayload,
    escapeHtml,
    formData,
    qs,
    qsa,
    setButtonLoading,
    setMessage,
    validateForm,
} from './api';

let users = [];

export function initUsuarios() {
    if (!qs('#usersTable') && !qs('#resetPasswordForm')) {
        return;
    }

    qs('[data-load-users]')?.addEventListener('click', loadUsers);
    qs('#userSearch')?.addEventListener('input', (event) => renderUsers(event.currentTarget.value));
    qs('#createUserForm')?.addEventListener('submit', saveUser);
    qs('#createUserForm [name="tipo"]')?.addEventListener('change', syncUserTypeFields);
    qs('[data-clear-user-form]')?.addEventListener('click', resetUserForm);
    qs('#assignRoleForm')?.addEventListener('submit', assignRole);
    qs('#resetPasswordForm')?.addEventListener('submit', resetPassword);

    syncUserTypeFields();

    if (qs('#usersTable')) {
        loadUsers();
    }
}
// carga los usuarios
async function loadUsers() {
    try {
        const data = await apiRequest('/api/usuarios');
        users = data.usuarios || [];
        renderUsers(qs('#userSearch')?.value || '');
    } catch (error) {
        setMessage('#usersOutput', userErrorMessage(error));
    }
}

// renderiza los usuarios
function renderUsers(filter = '') {
    const table = qs('#usersTable');
    const count = qs('#usersCount');

    if (!table) {
        return;
    }

    const query = filter.trim().toLowerCase();
    const filtered = users.filter((user) => {
        const profile = user.perfil || {};
        return [
            user.username,
            user.tipo,
            user.rol?.nombre,
            profile.nombre,
            profile.correo,
            user.correo,
        ].filter(Boolean).join(' ').toLowerCase().includes(query);
    });

    table.innerHTML = filtered.map((user) => {
        const profile = user.perfil || {};
        const name = profile.nombre || user.username;
        const email = profile.correo || user.correo || 'Sin correo';
        const state = user.tipo === 'docente'
            ? (profile.estado_profesional || 'pendiente_revision')
            : 'activo';

        return `
            <tr>
                <td>
                    <strong>${escapeHtml(name)}</strong>
                    <small>${escapeHtml(user.username)} · ${escapeHtml(user.tipo)}</small>
                </td>
                <td>${escapeHtml(email)}</td>
                <td>${escapeHtml(user.rol?.nombre || 'Sin rol')}</td>
                <td><span class="status-pill ${professionalStateClass(state)}">${escapeHtml(labelState(state))}</span></td>
                <td class="table-actions">
                    <a href="/dashboard/perfil" aria-label="Ver usuario ${escapeHtml(user.username)}">Ver</a>
                    <button type="button" data-edit-user="${escapeHtml(user.username)}" aria-label="Editar usuario ${escapeHtml(user.username)}">Editar</button>
                </td>
            </tr>
        `;
    }).join('') || '<tr><td colspan="5">No hay usuarios registrados.</td></tr>';

    if (count) {
        count.textContent = `${filtered.length} usuario(s) encontrado(s)`;
    }

    qsa('[data-edit-user]').forEach((button) => {
        button.addEventListener('click', () => fillUserForm(button.dataset.editUser));
    });
}

// guarda o actualiza un usuario
async function saveUser(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const values = formData(form);
    const button = form.querySelector('button[type="submit"]');
    const isEditing = values.form_mode === 'edit';
    const accountType = values.tipo === 'coordinador' ? 'administrativo' : values.tipo;
    const roleName = {
        administrativo: 'administrador',
        coordinador: 'coordinador',
        docente: 'docente',
    }[values.tipo];

    if (accountType === 'postulante') {
        setMessage('#usersOutput', 'Para crear postulantes usa CU-06 Preinscripcion, porque requiere datos academicos completos.');
        return;
    }

    const profile = accountType === 'docente'
        ? cleanPayload({
            nombre: values.nombre,
            correo: values.correo,
            telefono: values.telefono,
            ciudad: values.ciudad,
            titulo_profesional: values.titulo_profesional,
            nro_registro_profesional: values.nro_registro_profesional,
            estado_profesional: values.estado_profesional,
            observacion_profesional: values.observacion_profesional,
            max_grupos_periodo: values.max_grupos_periodo ? Number(values.max_grupos_periodo) : undefined,
            max_horas_semana: values.max_horas_semana ? Number(values.max_horas_semana) : undefined,
            especializacion: values.especializacion,
            maestria: values.maestria,
        })
        : cleanPayload({
            nombre: values.nombre,
            correo: values.correo,
            telefono: values.telefono,
            ciudad: values.ciudad,
        });

    setButtonLoading(button, true);

    try {
        const data = await apiRequest(isEditing ? `/api/usuarios/${encodeURIComponent(values.username)}` : '/api/usuarios', {
            method: isEditing ? 'PUT' : 'POST',
            body: JSON.stringify({
                ...(!isEditing ? {
                    username: values.username,
                    password: values.password,
                } : {}),
                tipo: accountType,
                rol_nombre: roleName,
                perfil: profile,
            }),
        });

        resetUserForm();
        setMessage('#usersOutput', data);
        await loadUsers();
        qs('.users-list-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch (error) {
        setMessage('#usersOutput', userErrorMessage(error));
    } finally {
        setButtonLoading(button, false);
    }
}

// llena el formulario con los datos del usuario
function fillUserForm(username) {
    const form = qs('#createUserForm');
    const user = users.find((item) => item.username === username);

    if (!form || !user) {
        return;
    }

    const profile = user.perfil || {};
    form.elements.form_mode.value = 'edit';
    form.elements.username.value = user.username;
    form.elements.username.readOnly = true;
    form.elements.tipo.value = user.rol?.nombre === 'coordinador'
        ? 'coordinador'
        : user.tipo;
    form.elements.nombre.value = profile.nombre || '';
    form.elements.correo.value = profile.correo || user.correo || '';
    form.elements.telefono.value = profile.telefono || '';
    form.elements.ciudad.value = profile.ciudad || '';
    form.elements.titulo_profesional.value = profile.titulo_profesional || '';
    form.elements.nro_registro_profesional.value = profile.nro_registro_profesional || '';
    form.elements.estado_profesional.value = profile.estado_profesional || 'pendiente_revision';
    form.elements.observacion_profesional.value = profile.observacion_profesional || '';
    form.elements.max_grupos_periodo.value = profile.max_grupos_periodo || 3;
    form.elements.max_horas_semana.value = profile.max_horas_semana || 30;
    form.elements.especializacion.value = profile.especializacion || '';
    form.elements.maestria.value = profile.maestria || '';

    const passwordInput = form.elements.password;
    passwordInput.value = '';
    passwordInput.required = false;

    syncUserTypeFields();
    qs('#userFormTitle').textContent = 'Actualizar usuario';
    setUserSubmitText('Actualizar usuario');
    setMessage('#usersOutput', `Editando usuario ${user.username}.`);
    qs('#createUserPanel').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// resetea el formulario
function resetUserForm() {
    const form = qs('#createUserForm');

    if (!form) {
        return;
    }

    form.reset();
    form.elements.form_mode.value = 'create';
    form.elements.username.readOnly = false;
    form.elements.password.required = true;
    qs('#userFormTitle').textContent = 'Nuevo usuario';
    setUserSubmitText('Crear usuario');
    syncUserTypeFields();
}

// sincroniza los campos del formulario según el tipo de usuario
function syncUserTypeFields() {
    const form = qs('#createUserForm');

    if (!form) {
        return;
    }

    const type = form.elements.tipo.value;

    qsa('[data-user-field="docente"]', form).forEach((field) => {
        setFieldVisibility(field, type === 'docente');
    });

    if (form.elements.titulo_profesional) {
        form.elements.titulo_profesional.required = type === 'docente';
    }

    qsa('[data-user-field="password"]', form).forEach((field) => {
        setFieldVisibility(field, form.elements.form_mode.value !== 'edit');
    });
}

// devuelve el label del estado
function labelState(state) {
    return {
        activo: 'Activo',
        pendiente_revision: 'Pendiente revision',
        habilitado: 'Habilitado',
        observado: 'Observado',
        rechazado: 'Rechazado',
    }[state] || state;
}

// devuelve la clase del estado profesional
function professionalStateClass(state) {
    return {
        activo: 'is-admitted',
        habilitado: 'is-admitted',
        pendiente_revision: 'is-validated',
        observado: 'is-validated',
        rechazado: 'is-rejected',
    }[state] || '';
}

// establece la visibilidad de un campo
function setFieldVisibility(field, visible) {
    field.hidden = !visible;
    qsa('input, select, textarea', field).forEach((input) => {
        input.disabled = !visible;
        if (!visible && input.name !== 'password') {
            input.value = '';
        }
    });
}

// establece el texto del botón de submit
function setUserSubmitText(text) {
    const button = qs('#createUserForm button[type="submit"]');

    if (button) {
        button.textContent = text;
    }
}

// devuelve el mensaje de error del usuario
function userErrorMessage(error) {
    const errors = error?.data?.errors;

    if (errors && typeof errors === 'object') {
        return Object.values(errors).flat().join(' ');
    }

    return error?.data?.message || error?.message || 'No se pudo completar la operacion.';
}

// asigna un rol a un usuario
async function assignRole(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const values = formData(form);

    try {
        const data = await apiRequest(`/api/usuarios/${encodeURIComponent(values.username)}/rol`, {
            method: 'PATCH',
            body: JSON.stringify({ codigo_rol: Number(values.codigo_rol) }),
        });

        setMessage('#usersOutput', data);
        loadUsers();
    } catch (error) {
        setMessage('#usersOutput', userErrorMessage(error));
    }
}

// restablece la contraseña de un usuario
async function resetPassword(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const values = formData(form);

    if (values.password !== values.password_confirmation) {
        setMessage('#passwordOutput', 'La confirmacion no coincide con la nueva contrasena.');
        return;
    }

    try {
        const data = await apiRequest(`/api/usuarios/${encodeURIComponent(values.username)}/restablecer-password`, {
            method: 'POST',
            body: JSON.stringify({
                password: values.password,
                password_confirmation: values.password_confirmation,
            }),
        });

        form.reset();
        setMessage('#passwordOutput', data);
    } catch (error) {
        setMessage('#passwordOutput', error.data || error.message);
    }
}
