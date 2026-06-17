@extends('layouts.app')

@section('title', 'Reportes | FICCT')

@section('content')
@php
    $usuarioActual = auth()->user();
    $puedeExportarPdf = $usuarioActual?->tienePermiso('exportar_pdf') ?? false;
    $puedeExportarExcel = $usuarioActual?->tienePermiso('exportar_excel') ?? false;
@endphp
<main class="portal-shell" data-page="reportes">
    @include('dashboard.partials.sidebar', ['active' => 'reportes'])

    <section class="portal-main users-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Reportes</span>
                <h1>Reportes administrativos</h1>
                <p>Consulta información real del CUP y exporta el resultado filtrado en PDF o Excel.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <button class="secondary-action" type="button" data-refresh-reports>Actualizar</button>
        </header>

        <article class="module-card is-wide voice-report-card">
            <div class="voice-report-heading">
                <div class="module-head">
                    <span>CU-35</span>
                    <div>
                        <h2>Generar reporte por voz con Gemini</h2>
                        <p>Indica el reporte, sus filtros y el formato. Gemini interpreta la orden y Laravel consulta los datos reales.</p>
                    </div>
                </div>
                <span id="voiceSupportBadge" class="status-pill is-validated">Comprobando microfono</span>
            </div>

            <div class="voice-command-layout">
                <label class="voice-command-field">
                    Comando
                    <textarea id="voiceReportCommand" rows="3" maxlength="600"
                        placeholder="Ejemplo: genera un PDF de postulantes admitidos de Sistemas del periodo 2026-1"></textarea>
                </label>

                <div class="voice-command-actions">
                    <button id="voiceReportListen" class="secondary-action" type="button">Escuchar comando</button>
                    <button id="voiceReportProcess" class="primary-action" type="button">Procesar con Gemini</button>
                    <label class="checkbox-line voice-read-response">
                        <input id="voiceReportSpeak" type="checkbox" checked>
                        <span>Leer respuesta en voz alta</span>
                    </label>
                </div>
            </div>

            <div id="voiceReportStatus" class="voice-report-status" aria-live="polite">
                <span class="voice-status-indicator"></span>
                <div>
                    <strong>Asistente listo</strong>
                    <p>Prueba: "Muestrame los pagos confirmados de este mes".</p>
                </div>
            </div>

            <div id="voiceReportResult" class="voice-report-result" hidden>
                <div>
                    <span>Interpretacion de Gemini</span>
                    <strong id="voiceReportAnswer">Sin respuesta</strong>
                </div>
                <div id="voiceReportFilters" class="voice-report-filter-tags"></div>
                <a id="voiceReportDownload" class="secondary-action" href="#" hidden>Descargar reporte</a>
            </div>
        </article>

        <article class="module-card is-wide report-filter-card">
            <div class="module-head">
                <span>CU-34</span>
                <div>
                    <h2>Consultar reporte dinamico</h2>
                    <p>Combina los filtros necesarios. Los campos vacios no se aplican a la consulta.</p>
                </div>
            </div>

            <form id="reportFilters" class="portal-form">
                <div class="report-filter-grid">
                    <label>Tipo de reporte
                        <select name="tipo" required>
                            <option value="postulantes">Lista general de postulantes</option>
                            <option value="lista_admitidos">Lista oficial de admitidos</option>
                            <option value="postulantes_aprobados">Postulantes aprobados y promedios</option>
                            <option value="postulantes_reprobados">Postulantes reprobados y promedios</option>
                            <option value="resultados_estudiantes">Aprobados, reprobados y promedios</option>
                            <option value="estadisticas_materia">Estadisticas por materia</option>
                            <option value="grupos_habilitados">Grupos habilitados</option>
                            <option value="docentes_grupo">Docentes por grupo</option>
                            <option value="rendimiento_grupos">Rendimiento por grupo</option>
                            <option value="pagos">Pagos de matricula</option>
                            <option value="calificaciones">Detalle de calificaciones</option>
                        </select>
                    </label>
                    <label>Buscar
                        <input name="buscar" type="search" maxlength="150" placeholder="Nombre, CI, folio o comprobante">
                    </label>
                    <label>Periodo academico
                        <select name="periodo">
                            <option value="">Todos los periodos</option>
                        </select>
                    </label>
                    <label>Carrera
                        <select name="carrera">
                            <option value="">Todas las carreras</option>
                        </select>
                    </label>
                    <label>Estado
                        <select name="estado">
                            <option value="">Todos los estados</option>
                        </select>
                    </label>
                    <label>Grupo
                        <select name="grupo">
                            <option value="">Todos los grupos</option>
                        </select>
                    </label>
                    <label>Docente
                        <select name="docente">
                            <option value="">Todos los docentes</option>
                        </select>
                    </label>
                    <label data-report-date>Fecha inicial
                        <input name="fecha_inicio" type="date">
                    </label>
                    <label data-report-date>Fecha final
                        <input name="fecha_fin" type="date">
                    </label>
                    <label>Resultados en pantalla
                        <select name="limite">
                            <option value="100">100</option>
                            <option value="250">250</option>
                            <option value="500">500</option>
                        </select>
                    </label>
                </div>

                <div class="report-actions">
                    <button class="primary-action" type="submit">Consultar datos</button>
                    <button class="secondary-action" type="button" data-clear-report>Limpiar filtros</button>
                    <span id="reportOutput" class="module-note"></span>
                </div>
            </form>
        </article>

        <article id="admittedListGuide" class="module-card is-wide report-admitted-guide" hidden>
            <div class="module-head">
                <span>CU-21</span>
                <div>
                    <h2>Lista oficial de admitidos</h2>
                    <p>
                        Se obtiene después de calcular las notas finales y asignar las carreras
                        según promedio, primera o segunda opción y cupos disponibles.
                    </p>
                </div>
            </div>
            <a class="primary-action report-admitted-action" href="/dashboard/asignacion-carreras">
                Generar o recalcular lista de admitidos
            </a>
        </article>

        <section id="reportSummary" class="report-summary-grid" aria-label="Resumen del reporte">
            <article>
                <span>Resultados</span>
                <strong>0</strong>
                <small>Esperando consulta</small>
            </article>
        </section>

        <article class="module-card is-wide report-table-card">
            <div class="users-list-head report-result-head">
                <div>
                    <span class="section-kicker">Vista previa</span>
                    <h2 id="reportTitle">Reporte de postulantes</h2>
                    <p id="reportCount">Sin datos cargados</p>
                </div>
                <div class="report-export-actions">
                    @if ($puedeExportarPdf)
                        <button class="secondary-action" type="button" data-export-report="pdf">Descargar PDF</button>
                    @endif
                    @if ($puedeExportarExcel)
                        <button class="primary-action" type="button" data-export-report="excel">Descargar Excel (.xlsx)</button>
                    @endif
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead id="reportTableHead">
                        <tr><th>Resultado</th></tr>
                    </thead>
                    <tbody id="reportTableBody">
                        <tr><td>Selecciona los filtros y presiona Consultar datos.</td></tr>
                    </tbody>
                </table>
            </div>
            <p class="module-note">CU-32 PDF · CU-33 Excel · CU-34 Consulta dinamica</p>
        </article>
    </section>
</main>
@endsection
