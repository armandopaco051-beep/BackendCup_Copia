import { apiRequest, escapeHtml, numberFormat, qs, statusClass } from './api';

export function initDashboard() {
    if (!qs('#metricPostulantes')) {
        return;
    }

    loadDashboard();
}

async function loadDashboard() {
    try {
        const data = await apiRequest('/api/dashboard');
        const metrics = data.metricas || {};

        qs('#metricPostulantes').textContent = numberFormat(metrics.postulantes);
        qs('#metricPreinscripciones').textContent = numberFormat(metrics.preinscripciones);
        qs('#metricMatriculas').textContent = numberFormat(metrics.matriculas_pagadas);
        qs('#metricAdmitidos').textContent = numberFormat(metrics.admitidos);

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
