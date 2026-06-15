@extends('layouts.app')

@section('title', 'Horarios de grupos | FICCT')

@section('content')
<main class="portal-shell" data-page="horarios-grupos">
    @include('dashboard.partials.sidebar', ['active' => 'horarios-grupos'])

    <section class="portal-main group-schedule-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Academico</span>
                <h1>Horarios de grupos</h1>
                <p>Genera una propuesta con rotacion circular de materias, aulas y docentes.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <div class="module-actions">
                <button class="secondary-action" type="button" data-load-schedules>Actualizar</button>
                <button class="primary-action" type="button" data-confirm-schedules>Confirmar horarios</button>
            </div>
        </header>

        <section class="schedule-summary-grid" id="scheduleSummary">
            <article class="module-card schedule-summary-card">
                <span class="section-kicker">Turno manana</span>
                <h2>07:00 - 12:00</h2>
                <p>4 materias de 1 h 15 min.</p>
            </article>
            <article class="module-card schedule-summary-card">
                <span class="section-kicker">Turno tarde</span>
                <h2>13:00 - 18:00</h2>
                <p>Bloques seguidos, sin descanso.</p>
            </article>
            <article class="module-card schedule-summary-card">
                <span class="section-kicker">Turno noche</span>
                <h2>18:00 - 23:00</h2>
                <p>Rotacion por grupo y dia.</p>
            </article>
        </section>

        <section class="teacher-subject-layout schedule-workspace">
            <article class="module-card form-panel">
                <div class="module-head">
                    <span>Horario</span>
                    <div>
                        <h2>Generar propuesta</h2>
                        <p>Usa los grupos creados, 4 materias habilitadas, aulas disponibles y docentes asignados.</p>
                    </div>
                </div>

                <form id="scheduleGenerateForm" class="portal-form">
                    <label>Periodo academico
                        <select name="periodo_id" id="schedulePeriodSelect">
                            <option value="">Periodo actual</option>
                        </select>
                    </label>

                    <label class="inline-check">
                        <input type="checkbox" name="sobrescribir" value="1">
                        <span>Sobrescribir propuesta existente</span>
                    </label>

                    <p id="scheduleOutput" class="module-note"></p>

                    <button class="primary-action" type="submit">
                        <span>Generar propuesta</span>
                    </button>
                </form>
            </article>

            <article class="module-card schedule-rules">
                <span class="section-kicker">Reglas aplicadas</span>
                <h2>Rotacion circular</h2>
                <p>El sistema cambia el orden de materias por grupo y por dia para reducir cruces.</p>
                <div id="scheduleOptions" class="schedule-option-list">
                    <span>Cargando configuracion...</span>
                </div>
            </article>
        </section>

        <article id="scheduleEditPanel" class="module-card is-wide form-panel">
            <div class="module-head">
                <span>Manual</span>
                <div>
                    <h2 id="scheduleEditTitle">Editar bloque de horario</h2>
                    <p>Modifica grupo, materia, docente, aula, dia, hora y estado del horario generado.</p>
                </div>
            </div>

            <form id="scheduleEditForm" class="portal-form">
                <input type="hidden" name="id">
                <div class="form-grid">
                    <label>Grupo
                        <select name="id_grupo" id="scheduleGroupSelect" required>
                            <option value="">Cargando grupos...</option>
                        </select>
                    </label>
                    <label>Materia
                        <select name="id_materia" id="scheduleSubjectSelect" required>
                            <option value="">Cargando materias...</option>
                        </select>
                    </label>
                    <label>Docente
                        <select name="username_docente" id="scheduleTeacherSelect" required>
                            <option value="">Selecciona una materia</option>
                        </select>
                    </label>
                    <label>Aula
                        <select name="id_aula" id="scheduleClassroomSelect" required>
                            <option value="">Cargando aulas...</option>
                        </select>
                    </label>
                    <label>Dia
                        <select name="id_dia" id="scheduleDaySelect" required>
                            <option value="">Cargando dias...</option>
                        </select>
                    </label>
                    <label>Turno
                        <select name="turno" required>
                            <option value="mañana">Mañana</option>
                            <option value="tarde">Tarde</option>
                            <option value="noche">Noche</option>
                        </select>
                    </label>
                    <label>Hora inicio<input name="hora_inicio" type="time" required></label>
                    <label>Hora fin<input name="hora_fin" type="time" required></label>
                    <label>Estado
                        <select name="estado" required>
                            <option value="propuesto">Propuesto</option>
                            <option value="confirmado">Confirmado</option>
                        </select>
                    </label>
                </div>

                <p id="scheduleEditOutput" class="module-note"></p>

                <div class="distribution-actions">
                    <button class="primary-action" type="submit"><span>Guardar bloque</span></button>
                    <button class="secondary-action" type="button" data-clear-schedule-edit>Nuevo bloque</button>
                </div>
            </form>
        </article>

        <article class="module-card is-wide">
            <div class="users-list-head">
                <div>
                    <h2>Propuesta de horarios</h2>
                    <p id="scheduleCount">Sin datos cargados</p>
                </div>
            </div>

            <label class="users-search">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m16 16 4 4"></path>
                </svg>
                <input id="scheduleSearch" type="search" placeholder="Buscar grupo, materia, aula o docente...">
            </label>

            <div class="table-wrap users-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Dia</th>
                            <th>Grupo</th>
                            <th>Turno</th>
                            <th>Horario</th>
                            <th>Materia</th>
                            <th>Aula</th>
                            <th>Docente</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="scheduleTable">
                        <tr><td colspan="9">Cargando horarios...</td></tr>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>
@endsection
