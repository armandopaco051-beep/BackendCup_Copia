@extends('layouts.app')

@section('title', 'Asistencia CUP | FICCT')

@section('content')
<main class="portal-shell" data-page="asistencias">
    @include('dashboard.partials.sidebar', ['active' => 'asistencias'])

    <section class="portal-main attendance-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Academico</span>
                <h1>Registrar asistencia</h1>
                <p>Marca presentes, retrasos y faltas por grupo, materia y fecha.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <button class="secondary-action" type="button" data-load-attendance>Actualizar</button>
        </header>

        <article class="module-card is-wide form-panel">
            <div class="module-head">
                <span>Asistencia</span>
                <div>
                    <h2>Control de clase</h2>
                    <p>Selecciona el grupo para cargar la lista de postulantes asignados.</p>
                </div>
            </div>

            <form id="attendanceForm" class="portal-form">
                <div class="form-grid">
                    <label>Grupo
                        <select name="id_grupo" id="attendanceGroupSelect" required>
                            <option value="">Cargando grupos...</option>
                        </select>
                    </label>
                    <label>Materia
                        <select name="id_materia" id="attendanceSubjectSelect" required>
                            <option value="">Selecciona un grupo...</option>
                        </select>
                    </label>
                    <label>Fecha
                        <input name="fecha" type="date" required>
                    </label>
                </div>

                <div class="attendance-roster">
                    <div class="users-list-head">
                        <div>
                            <h2>Lista de alumnos</h2>
                            <p id="attendanceRosterCount">Selecciona un grupo para cargar alumnos</p>
                        </div>
                        <button class="secondary-action" type="button" data-mark-all-present>Marcar presentes</button>
                    </div>
                    <div id="attendanceRoster" class="attendance-roster-list">
                        <p class="module-note">Los estudiantes apareceran aqui cuando selecciones un grupo.</p>
                    </div>
                </div>

                <p id="attendanceOutput" class="module-note"></p>
                <div class="distribution-actions">
                    <button class="primary-action" type="submit"><span>Guardar asistencia</span></button>
                    <button class="secondary-action" type="button" data-clear-attendance>Limpiar</button>
                </div>
            </form>
        </article>

        <article class="module-card is-wide">
            <div class="users-list-head">
                <div>
                    <h2>Registros recientes</h2>
                    <p id="attendanceCount">Sin datos cargados</p>
                </div>
            </div>

            <label class="users-search">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m16 16 4 4"></path>
                </svg>
                <input id="attendanceSearch" type="search" placeholder="Buscar por alumno, grupo, materia o estado...">
            </label>

            <div class="table-wrap users-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Postulante</th>
                            <th>Grupo</th>
                            <th>Materia</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTable">
                        <tr><td colspan="6">Cargando asistencias...</td></tr>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>
@endsection
