import {
    apiRequest,
    cleanPayload,
    escapeHtml,
    formData,
    qs,
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
    qs('#createUserForm')?.addEventListener('submit', createUser);
    qs('#assignRoleForm')?.addEventListener('submit', assignRole);
    qs('#resetPasswordForm')?.addEventListener('submit', resetPassword);

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
                    <a href="#createUserPanel" aria-label="Editar usuario ${escapeHtml(user.username)}">Editar</a>
                </td>
            </tr>
        `;
    }).join('') || '<tr><td colspan="5">No hay usuarios registrados.</td></tr>';

    if (count) {
        count.textContent = `${filtered.length} usuario(s) encontrado(s)`;
    }
}

async function createUser(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    const values = formData(form);
    const button = form.querySelector('button[type="submit"]');

    if (values.tipo === 'postulante') {
        setMessage('#usersOutput', 'Para crear postulantes usa CU-06 Preinscripcion, porque requiere datos academicos completos.');
        return;
    }

    const profile = values.tipo === 'docente'
        ? cleanPayload({
            nombre: values.nombre,
            especializacion: values.especializacion,
            maestria: values.maestria,
        })
        : cleanPayload({
            nombre: values.nombre,
            telefono: values.telefono,
            ciudad: values.ciudad,
        });

    setButtonLoading(button, true);

    try {
        const data = await apiRequest('/api/usuarios', {
            method: 'POST',
            body: JSON.stringify({
                username: values.username,
                password: values.password,
                tipo: values.tipo,
                perfil: profile,
            }),
        });

        form.reset();
        setMessage('#usersOutput', data);
        loadUsers();
    } catch (error) {
        setMessage('#usersOutput', error.data || error.message);
    } finally {
        setButtonLoading(button, false);
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
