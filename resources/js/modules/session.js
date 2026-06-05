import { apiRequest, escapeHtml, qs, qsa } from './api';

let currentUser = null;

export function getCurrentUser() {
    return currentUser;
}

export async function initSession() {
    const userLabels = qsa('#dashboardUser');
    const logoutButtons = qsa('[data-logout]');

    logoutButtons.forEach((button) => {
        button.addEventListener('click', logout);
    });

    if (!userLabels.length && !qs('#profileSummary')) {
        return;
    }

    try {
        const data = await apiRequest('/api/auth/me');
        currentUser = data.usuario;

        userLabels.forEach((label) => {
            label.textContent = `${currentUser.username} | ${currentUser.rol?.nombre || currentUser.tipo}`;
        });

        renderProfile(currentUser);
    } catch {
        userLabels.forEach((label) => {
            label.textContent = 'No se pudo cargar la informacion de sesion.';
        });
    }
}

async function logout() {
    await fetch('/api/auth/logout', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
    });

    window.location.href = '/login';
}

function renderProfile(user) {
    if (!qs('#profileSummary')) {
        return;
    }

    const profile = user.perfil || {};
    const profileName = qs('#profileName');
    const profileRole = qs('#profileRole');
    const profileFields = qs('#profileFields');

    if (profileName) {
        profileName.textContent = profile.nombre || user.username;
    }

    if (profileRole) {
        profileRole.textContent = `${user.username} | ${user.rol?.nombre || user.tipo}`;
    }

    if (!profileFields) {
        return;
    }

    const rows = [
        ['Usuario', user.username],
        ['Tipo', user.tipo],
        ['Rol', user.rol?.nombre || 'Sin rol'],
        ['Correo', user.correo || profile.correo || 'Sin correo'],
        ['Nombre', profile.nombre],
        ['Telefono', profile.telefono],
        ['Ciudad', profile.ciudad],
        ['CI', profile.ci],
        ['Especializacion', profile.especializacion],
        ['Maestria', profile.maestria],
        ['Colegio', profile.colegio_procedencia],
        ['Direccion', profile.direccion],
        ['Fecha nacimiento', profile.fecha_nacimiento],
        ['Genero', profile.genero],
    ].filter(([, value]) => value);

    profileFields.innerHTML = rows.map(([label, value]) => `
        <div>
            <span>${escapeHtml(label)}</span>
            <strong>${escapeHtml(value)}</strong>
        </div>
    `).join('');
}
