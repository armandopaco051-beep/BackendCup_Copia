@extends('layouts.app')

@section('title', 'Docentes y materias | FICCT')

@section('content')
<main class="portal-shell" data-page="docentes-materias">
    @include('dashboard.partials.sidebar', ['active' => 'docentes-materias'])

    <section class="portal-main teacher-subject-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Academico</span>
                <h1>Asignar materias a docentes</h1>
                <p>Define que materias puede dictar cada docente antes de generar horarios.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <button class="secondary-action" type="button" data-load-teacher-subjects>Actualizar</button>
        </header>

        <section class="teacher-subject-layout">
            <article class="module-card form-panel">
                <div class="module-head">
                    <span>Docente</span>
                    <div>
                        <h2>Seleccionar docente</h2>
                        <p>Marca una o varias materias habilitadas.</p>
                    </div>
                </div>

                <form id="teacherSubjectForm" class="portal-form">
                    <label>Docente
                        <select name="username_docente" id="teacherSubjectTeacherSelect" required>
                            <option value="">Cargando docentes...</option>
                        </select>
                    </label>

                    <div class="teacher-subject-list-head">
                        <strong>Materias habilitadas</strong>
                        <button class="secondary-action" type="button" data-clear-teacher-subjects>Limpiar</button>
                    </div>

                    <div id="teacherSubjectChecks" class="teacher-subject-checks">
                        <p class="module-note">Selecciona un docente para ver sus materias.</p>
                    </div>

                    <p id="teacherSubjectOutput" class="module-note"></p>

                    <button class="primary-action" type="submit">
                        <span>Guardar asignacion</span>
                    </button>
                </form>
            </article>

            <article class="module-card teacher-subject-detail">
                <span class="section-kicker">Resumen</span>
                <h2 id="teacherSubjectDetailName">Sin docente seleccionado</h2>
                <p id="teacherSubjectDetailMeta">Las materias asignadas apareceran aqui.</p>
                <div id="teacherSubjectDetailTags" class="teacher-subject-tags">
                    <span class="status-pill">Sin materias</span>
                </div>
            </article>
        </section>

        <article class="module-card is-wide">
            <div class="users-list-head">
                <div>
                    <h2>Docentes registrados</h2>
                    <p id="teacherSubjectCount">Sin datos cargados</p>
                </div>
            </div>

            <label class="users-search">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m16 16 4 4"></path>
                </svg>
                <input id="teacherSubjectSearch" type="search" placeholder="Buscar docente o materia...">
            </label>

            <div class="table-wrap users-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Docente</th>
                            <th>Correo</th>
                            <th>Estado profesional</th>
                            <th>Materias asignadas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="teacherSubjectTable">
                        <tr><td colspan="5">Cargando docentes...</td></tr>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>
@endsection
