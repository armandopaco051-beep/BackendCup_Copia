import { apiRequest, escapeHtml, numberFormat, qs, statusClass } from './api';

export function initDashboard() {
    if (!qs('#metricInscritos')) {
        return;
    }

    loadDashboard();
}

async function loadDashboard() {
    try {
        const data = await apiRequest('/api/dashboard');
        const metrics = data.metricas || {};

        qs('#metricInscritos').textContent = numberFormat(metrics.inscritos);
        qs('#metricAprobados').textContent = numberFormat(metrics.aprobados);
        qs('#metricReprobados').textContent = numberFormat(metrics.reprobados);
        qs('#metricGruposHabilitados').textContent = numberFormat(metrics.grupos_habilitados);

        const period = qs('#dashboardPeriod');
        if (period && data.periodo?.nombre) {
            const icon = period.querySelector('svg')?.outerHTML || '';
            period.innerHTML = `${icon}${escapeHtml(data.periodo.nombre)}`;
        }

        renderRecent(data.preinscripciones_recientes || []);
    } catch {
        const recent = qs('#recentPreinscriptions');
        if (recent) {
            recent.innerHTML = '<div><strong>No se pudo cargar el dashboard</strong></div>';
        }
    }
}

function renderRecent(items) {
    const target = qs('#recentPreinscriptions');

    if (!target) {
        return;
    }

    target.innerHTML = items.map((item) => `
        <div>
            <span>
                <strong>${escapeHtml(item.nombre)}</strong>
                <small>${escapeHtml(item.codigo_preinscripcion)} · ${escapeHtml(item.carrera)}</small>
            </span>
            <em class="${statusClass(item.estado?.tipo)}">${escapeHtml(item.estado?.label || 'Pendiente')}</em>
        </div>
    `).join('') || '<div><strong>No hay preinscripciones recientes.</strong></div>';
}
