import { apiRequest, escapeHtml, formData, qs, qsa, setMessage, validateForm } from './api';

let roles = [];
let permissions = [];
let selectedRoleId = null;

export function initRolesPermisos() {
    if (!qs('#rolesCards')) {
        return;
    }

    qs('[data-load-security]')?.addEventListener('click', loadSecurity);
    qs('#createRoleForm')?.addEventListener('submit', createRole);
    qs('#createPermissionForm')?.addEventListener('submit', createPermission);
    qs('#permissionMatrixForm')?.addEventListener('submit', syncPermissions);

    loadSecurity();
}
// hace la peticion para cargar los roles y permisos
async function loadSecurity() {
    try {
        const [rolesResponse, permissionsResponse] = await Promise.all([
            apiRequest('/api/roles'),
            apiRequest('/api/permisos'),
        ]);

        roles = rolesResponse.roles || [];
        permissions = permissionsResponse.permisos || [];
        selectedRoleId = selectedRoleId || roles[0]?.id || null;

        renderRoles();
        renderPermissionMatrix();
        setMessage('#securityOutput', { message: 'Roles y permisos cargados correctamente.' });
    } catch (error) {
        setMessage('#securityOutput', error.data || error.message);
    }
}

// renderiza los roles
function renderRoles() {
    const target = qs('#rolesCards');

    if (!target) {
        return;
    }

    target.innerHTML = roles.map((role) => `
        <button class="role-card ${Number(selectedRoleId) === Number(role.id) ? 'is-selected' : ''}" type="button" data-role-card="${role.id}">
            <span class="status-pill">${escapeHtml(role.usuarios_count ?? role.usuarios?.length ?? 0)} usuarios</span>
            <strong>${escapeHtml(role.nombre)}</strong>
        </button>
    `).join('') || '<p>No hay roles registrados.</p>';

    qsa('[data-role-card]').forEach((button) => {
        button.addEventListener('click', () => {
            selectedRoleId = Number(button.dataset.roleCard);
            renderRoles();
            renderPermissionMatrix();
        });
    });
}

// renderiza la matriz de permisos
function renderPermissionMatrix() {
    const rows = qs('#permissionMatrixRows');
    const input = qs('#matrixRoleId');
    const title = qs('#permissionMatrixTitle');
    const role = roles.find((item) => Number(item.id) === Number(selectedRoleId));

    if (!rows || !role) {
        return;
    }

    if (input) {
        input.value = role.id;
    }

    if (title) {
        title.textContent = `Matriz de permisos · ${role.nombre}`;
    }

    const assigned = new Set((role.permisos || []).map((permission) => Number(permission.codigo)));

    rows.innerHTML = permissions.map((permission) => `
        <label class="permission-row">
            <strong>${escapeHtml(permission.nombre)}</strong>
            <span class="permission-actions">
                <input type="checkbox" name="permisos[]" value="${permission.codigo}" ${assigned.has(Number(permission.codigo)) ? 'checked' : ''}>
                Asignado
            </span>
        </label>
    `).join('') || '<p>No hay permisos registrados.</p>';
}

// crea un rol
async function createRole(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    try {
        const values = formData(form);
        const data = await apiRequest('/api/roles', {
            method: 'POST',
            body: JSON.stringify({ nombre: values.nombre }),
        });

        form.reset();
        setMessage('#securityOutput', data);
        loadSecurity();
    } catch (error) {
        setMessage('#securityOutput', error.data || error.message);
    }
}

// crea un permiso
async function createPermission(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!validateForm(form)) {
        return;
    }

    try {
        const values = formData(form);
        const data = await apiRequest('/api/permisos', {
            method: 'POST',
            body: JSON.stringify({ nombre: values.nombre }),
        });

        form.reset();
        setMessage('#securityOutput', data);
        loadSecurity();
    } catch (error) {
        setMessage('#securityOutput', error.data || error.message);
    }
}

// sincroniza los permisos
async function syncPermissions(event) {
    event.preventDefault();

    const form = event.currentTarget;
    const roleId = Number(form.elements.rol_id.value);
    const selected = qsa('input[name="permisos[]"]:checked', form).map((input) => Number(input.value));

    try {
        const data = await apiRequest(`/api/roles/${roleId}/permisos`, {
            method: 'PUT',
            body: JSON.stringify({ permisos: selected }),
        });

        setMessage('#securityOutput', data);
        selectedRoleId = roleId;
        loadSecurity();
    } catch (error) {
        setMessage('#securityOutput', error.data || error.message);
    }
}
