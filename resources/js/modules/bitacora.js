import { apiRequest, cleanPayload, escapeHtml, formData, qs, setMessage, validateForm } from './api';

let records = [];

export function initBitacora() {
    if (!qs('#bitacoraTable')) {
        return;
    }

    qs('[data-load-bitacora]')?.addEventListener('click', loadBitacora);
    qs('#bitacoraFilters')?.addEventListener('submit', (event) => {
        event.preventDefault();

        if (validateForm(event.currentTarget)) {
            loadBitacora();
        }
    });

    loadBitacora();
}

async function loadBitacora() {
    const filters = qs('#bitacoraFilters') ? cleanPayload(formData(qs('#bitacoraFilters'))) : {};
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => params.set(key, value));

    try {
        const data = await apiRequest(`/api/bitacora${params.toString() ? `?${params}` : ''}`);
        records = data.bitacora || [];
        renderBitacora();
        setMessage('#bitacoraOutput', data.resumen
            ? `Mostrando ${data.resumen.total_mostrado} movimiento(s).`
            : 'Bitacora cargada correctamente.');
    } catch (error) {
        qs('#bitacoraTable').innerHTML = `<tr><td colspan="6">${escapeHtml(error.data?.message || error.message)}</td></tr>`;
        setMessage('#bitacoraOutput', error.data || error.message);
    }
}

function renderBitacora() {
    const table = qs('#bitacoraTable');
    const count = qs('#bitacoraCount');

    table.innerHTML = records.map((record) => `
        <tr>
            <td>
                <strong>${escapeHtml(record.created_at)}</strong>
                <small>${escapeHtml(record.metodo || '')}</small>
            </td>
            <td>
                <strong>${escapeHtml(record.username || 'Sistema')}</strong>
                <small>${escapeHtml(record.rol || record.tipo_usuario || 'Sin rol')}</small>
            </td>
            <td>
                <strong>${escapeHtml(record.accion)}</strong>
                <small>${escapeHtml(record.descripcion || '')}</small>
            </td>
            <td>${escapeHtml(record.modulo || '-')}</td>
            <td>${escapeHtml(record.ruta || '-')}</td>
            <td>${escapeHtml(record.ip || '-')}</td>
        </tr>
    `).join('') || '<tr><td colspan="6">No hay movimientos registrados.</td></tr>';

    if (count) {
        count.textContent = `${records.length} movimiento(s) encontrado(s)`;
    }
}
