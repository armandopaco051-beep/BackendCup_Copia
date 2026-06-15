@extends('layouts.app')

@section('title', 'Docentes y horarios | FICCT')

@section('content')
<main class="portal-shell" data-page="docentes-horarios">
    @include('dashboard.partials.sidebar', ['active' => 'docentes-horarios'])

    <section class="portal-main teacher-schedule-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Academico</span>
                <h1>Asignar docentes a horarios</h1>
                <p>Gestiona el docente de cada bloque segun materia, grupo, aula y hora.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <button class="secondary-action" type="button" data-load-teacher-schedules>Actualizar</button>
        </header>

        <section class="teacher-subject-layout schedule-workspace">
            <article class="module-card form-panel">
                <div class="module-head">
                    <span>Horario</span>
                    <div>
                        <h2>Editar docente del bloque</h2>
                        <p>El docente debe tener asignada la materia del horario.</p>
                    </div>
                </div>

                <form id="teacherScheduleForm" class="portal-form">
                    <input type="hidden" name="id">
                    <label>Bloque de horario
                        <select id="teacherScheduleSelect" name="horario_id" required>
                            <option value="">Cargando horarios...</option>
                        </select>
                    </label>
                    <label>Docente
                        <select id="teacherScheduleTeacherSelect" name="username_docente" required>
                            <option value="">Selecciona un horario primero</option>
                        </select>
                    </label>

                    <p id="teacherScheduleOutput" class="module-note"></p>

                    <button class="primary-action" type="submit">
                        <span>Guardar docente</span>
                    </button>
                </form>
            </article>

            <article class="module-card schedule-rules">
                <span class="section-kicker">Regla aplicada</span>
                <h2>Docente + materia + grupo</h2>
                <p>El permiso de asistencia y calificaciones sale de estos bloques horarios.</p>
                <div class="schedule-option-list">
                    <span><strong>1</strong> Docente debe dictar la materia</span>
                    <span><strong>2</strong> No debe tener cruce en la misma hora</span>
                    <span><strong>3</strong> Solo ve su grupo y materia asignada</span>
                    <span><strong>4</strong> Se guarda en horario_grupo</span>
                </div>
            </article>
        </section>

        <article class="module-card is-wide">
            <div class="users-list-head">
                <div>
                    <h2>Bloques de horario</h2>
                    <p id="teacherScheduleCount">Sin datos cargados</p>
                </div>
            </div>

            <label class="users-search">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m16 16 4 4"></path>
                </svg>
                <input id="teacherScheduleSearch" type="search" placeholder="Buscar grupo, materia, docente o dia...">
            </label>

            <div class="table-wrap users-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Dia</th>
                            <th>Grupo</th>
                            <th>Horario</th>
                            <th>Materia</th>
                            <th>Aula</th>
                            <th>Docente</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="teacherScheduleTable">
                        <tr><td colspan="8">Cargando horarios...</td></tr>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>
@endsection
