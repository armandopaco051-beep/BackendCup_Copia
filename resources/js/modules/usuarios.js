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

async function loadUsers() {
    try {
        const data = await apiRequest('/api/usuarios');
        users = data.usuarios || [];
        renderUsers(qs('#userSearch')?.value || '');
        setMessage('#usersOutput', data);
    } catch (error) {
        setMessage('#usersOutput', error.data || error.message);
    }
}

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

        return `
            <tr>
                <td>
                    <strong>${escapeHtml(name)}</strong>
                    <small>${escapeHtml(user.username)} · ${escapeHtml(user.tipo)}</small>
                </td>
                <td>${escapeHtml(email)}</td>
                <td>${escapeHtml(user.rol?.nombre || 'Sin rol')}</td>
                <td><span class="status-pill">Activo</span></td>
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

async function saveUser(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const values = formData(form);
    const button = form.querySelector('button[type="submit"]');
    const isEditing = values.form_mode === 'edit';

    if (values.tipo === 'postulante') {
        setMessage('#usersOutput', 'Para crear postulantes usa CU-06 Preinscripcion, porque requiere datos academicos completos.');
        return;
    }

    const profile = values.tipo === 'docente'
        ? cleanPayload({
            nombre: values.nombre,
            correo: values.correo,
            telefono: values.telefono,
            ciudad: values.ciudad,
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
                tipo: values.tipo,
                perfil: profile,
            }),
        });

        resetUserForm();
        setMessage('#usersOutput', data);
        loadUsers();
    } catch (error) {
        setMessage('#usersOutput', error.data || error.message);
    } finally {
        setButtonLoading(button, false);
    }
}

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
    form.elements.tipo.value = user.tipo;
    form.elements.nombre.value = profile.nombre || '';
    form.elements.correo.value = profile.correo || user.correo || '';
    form.elements.telefono.value = profile.telefono || '';
    form.elements.ciudad.value = profile.ciudad || '';
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

function syncUserTypeFields() {
    const form = qs('#createUserForm');

    if (!form) {
        return;
    }

    const type = form.elements.tipo.value;

    qsa('[data-user-field="docente"]', form).forEach((field) => {
        setFieldVisibility(field, type === 'docente');
    });

    qsa('[data-user-field="password"]', form).forEach((field) => {
        setFieldVisibility(field, form.elements.form_mode.value !== 'edit');
    });
}

function setFieldVisibility(field, visible) {
    field.hidden = !visible;
    qsa('input, select, textarea', field).forEach((input) => {
        input.disabled = !visible;
        if (!visible && input.name !== 'password') {
            input.value = '';
        }
    });
}

function setUserSubmitText(text) {
    const button = qs('#createUserForm button[type="submit"]');

    if (button) {
        button.textContent = text;
    }
}

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
        setMessage('#usersOutput', error.data || error.message);
    }
}

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
