@extends('layouts.app')

@section('title', 'Asignar estudiantes a grupos | FICCT')

@section('content')
<main class="portal-shell" data-page="postulantes-grupos">
    @include('dashboard.partials.sidebar', ['active' => 'postulantes-grupos'])

    <section class="portal-main student-group-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Academico</span>
                <h1>Asignar estudiantes a grupos</h1>
                <p>Inscribe postulantes habilitados a un grupo con cupo disponible.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <button class="secondary-action" type="button" data-load-student-groups>Actualizar</button>
        </header>

        <section class="teacher-subject-layout schedule-workspace">
            <article class="module-card form-panel">
                <div class="module-head">
                    <span>Grupo</span>
                    <div>
                        <h2>Inscribir postulante</h2>
                        <p>El sistema valida estado habilitado y cupo disponible.</p>
                    </div>
                </div>

                <form id="studentGroupForm" class="portal-form">
                    <label>Postulante
                        <select name="username_postulante" id="studentGroupApplicantSelect" required>
                            <option value="">Cargando postulantes...</option>
                        </select>
                    </label>
                    <label>Grupo
                        <select name="id_grupo" id="studentGroupSelect" required>
                            <option value="">Cargando grupos...</option>
                        </select>
                    </label>

                    <p id="studentGroupOutput" class="module-note"></p>

                    <button class="primary-action" type="submit">
                        <span>Inscribir al grupo</span>
                    </button>
                </form>
            </article>

            <article class="module-card schedule-rules">
                <span class="section-kicker">Reglas</span>
                <h2>Inscripcion oficial</h2>
                <p>Desde esta asignacion salen las listas de asistencia y calificaciones por grupo.</p>
                <div class="schedule-option-list">
                    <span><strong>1</strong> Postulante habilitado</span>
                    <span><strong>2</strong> Grupo activo</span>
                    <span><strong>3</strong> Cupo disponible</span>
                    <span><strong>4</strong> Una inscripcion por periodo</span>
                </div>
            </article>
        </section>

        <article class="module-card is-wide">
            <div class="users-list-head">
                <div>
                    <h2>Cupos por grupo</h2>
                    <p id="studentGroupQuotaCount">Sin datos cargados</p>
                </div>
            </div>

            <div id="studentGroupQuotaCards" class="career-quota-grid">
                <p class="module-note">Cargando cupos...</p>
            </div>
        </article>

        <article class="module-card is-wide">
            <div class="users-list-head">
                <div>
                    <h2>Estudiantes inscritos</h2>
                    <p id="studentGroupCount">Sin datos cargados</p>
                </div>
            </div>

            <label class="users-search">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m16 16 4 4"></path>
                </svg>
                <input id="studentGroupSearch" type="search" placeholder="Buscar estudiante, CI o grupo...">
            </label>

            <div class="table-wrap users-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Postulante</th>
                            <th>Grupo</th>
                            <th>Turno</th>
                            <th>Periodo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="studentGroupTable">
                        <tr><td colspan="6">Cargando inscripciones...</td></tr>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>
@endsection
