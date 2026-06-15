@extends('layouts.app')

@section('title', 'Panel docente | FICCT')

@section('content')
<main class="portal-shell" data-page="docente">
    @include('dashboard.partials.sidebar', ['active' => 'docente-dashboard'])

    <section class="portal-main teacher-panel-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Docente</span>
                <h1>Panel docente</h1>
                <p>Consulta tu carga academica, grupos, materias, aulas y horarios asignados.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <button class="secondary-action" type="button" data-refresh-teacher-panel>Actualizar horario</button>
        </header>

        <section class="teacher-schedule-summary">
            <article class="module-card">
                <span>Bloques semanales</span>
                <strong id="teacherScheduleBlocks">0</strong>
                <p>Clases asignadas de lunes a viernes.</p>
            </article>
            <article class="module-card">
                <span>Grupos</span>
                <strong id="teacherScheduleGroups">0</strong>
                <p>Grupos diferentes bajo tu responsabilidad.</p>
            </article>
            <article class="module-card">
                <span>Materias</span>
                <strong id="teacherScheduleSubjects">0</strong>
                <p>Materias que aparecen en tu horario.</p>
            </article>
            <article class="module-card">
                <span>Horas semanales</span>
                <strong id="teacherScheduleHours">0</strong>
                <p>Carga calculada a partir de cada bloque.</p>
            </article>
        </section>

        <article id="mi-horario-docente" class="module-card is-wide teacher-schedule-card">
            <div class="users-list-head">
                <div>
                    <span class="section-kicker">Mi horario</span>
                    <h2>Horario academico asignado</h2>
                    <p id="teacherScheduleCount">Cargando bloques...</p>
                </div>
                <div class="teacher-schedule-filters">
                    <label class="teacher-schedule-filter">
                        Grupo
                        <select id="teacherScheduleGroup">
                            <option value="">Cargando grupos...</option>
                        </select>
                    </label>
                    <label class="teacher-schedule-filter">
                        Dia
                        <select id="teacherScheduleDay">
                            <option value="">Todos los dias</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Dia</th>
                            <th>Horario</th>
                            <th>Materia</th>
                            <th>Grupo</th>
                            <th>Turno</th>
                            <th>Aula y ubicacion</th>
                            <th>Periodo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="teacherScheduleBody">
                        <tr><td colspan="8">Cargando tu horario...</td></tr>
                    </tbody>
                </table>
            </div>
        </article>

        <section class="summary-grid teacher-actions-grid">
            <article>
                <span>Notas</span>
                <strong>Calificaciones</strong>
                <p>Registra y actualiza notas solamente de tus grupos y materias.</p>
                <a class="secondary-action" href="/dashboard/calificaciones">Abrir calificaciones</a>
            </article>
            <article>
                <span>Asistencia</span>
                <strong>Control diario</strong>
                <p>Marca presente, retraso o falta de los estudiantes de tus grupos.</p>
                <a class="secondary-action" href="/dashboard/asistencias">Registrar asistencia</a>
            </article>
            <article>
                <span>Cuenta</span>
                <strong>Perfil docente</strong>
                <p>Consulta tus datos profesionales y limites de carga.</p>
                <a class="secondary-action" href="/dashboard/perfil">Ver perfil</a>
            </article>
        </section>
    </section>
</main>
@endsection
