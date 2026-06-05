@extends('layouts.app')

@section('title', 'Calificaciones CUP | FICCT')

@section('content')
<main class="portal-shell" data-page="calificaciones">
    @include('dashboard.partials.sidebar', ['active' => 'calificaciones'])

    <section class="portal-main grades-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Academico</span>
                <h1>Registrar calificaciones</h1>
                <p>Gestiona notas por postulante, grupo y materia con promedio automatico.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <a class="primary-action users-new-button" href="#gradeFormPanel">
                <span>Nueva calificacion</span>
            </a>
        </header>

        <article class="module-card is-wide">
            <div class="users-list-head">
                <div>
                    <h2>Listado de calificaciones</h2>
                    <p id="gradesCount">Sin datos cargados</p>
                </div>
                <button class="secondary-action" type="button" data-load-grades>Actualizar</button>
            </div>

            <label class="users-search">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m16 16 4 4"></path>
                </svg>
                <input id="gradeSearch" type="search" placeholder="Buscar por postulante, grupo o materia...">
            </label>

            <div class="table-wrap users-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Postulante</th>
                            <th>Grupo</th>
                            <th>Materia</th>
                            <th>Notas</th>
                            <th>Promedio</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="gradesTable">
                        <tr><td colspan="7">Cargando calificaciones...</td></tr>
                    </tbody>
                </table>
            </div>
        </article>

        <article id="gradeFormPanel" class="module-card is-wide form-panel">
            <div class="module-head">
                <span>Notas</span>
                <div>
                    <h2 id="gradeFormTitle">Nueva calificacion</h2>
                    <p>El promedio se calcula automaticamente con nota 1, nota 2 y nota 3.</p>
                </div>
            </div>

            <form id="gradeForm" class="portal-form">
                <input type="hidden" name="id">
                <div class="form-grid">
                    <label>Postulante
                        <select name="username_postulante" id="gradeApplicantSelect" required>
                            <option value="">Cargando postulantes...</option>
                        </select>
                    </label>
                    <label>Grupo
                        <select name="id_grupo" id="gradeGroupSelect" required>
                            <option value="">Cargando grupos...</option>
                        </select>
                    </label>
                    <label>Materia
                        <select name="id_materia" id="gradeSubjectSelect" required>
                            <option value="">Cargando materias...</option>
                        </select>
                    </label>
                    <label>Nota 1<input name="nota1" type="number" min="0" max="100" required placeholder="80"></label>
                    <label>Nota 2<input name="nota2" type="number" min="0" max="100" required placeholder="75"></label>
                    <label>Nota 3<input name="nota3" type="number" min="0" max="100" required placeholder="90"></label>
                    <label>Descripcion<textarea name="descripcion" rows="3" placeholder="Observacion opcional"></textarea></label>
                </div>
                <p id="gradesOutput" class="module-note"></p>
                <div class="distribution-actions">
                    <button class="primary-action" type="submit"><span>Guardar calificacion</span></button>
                    <button class="secondary-action" type="button" data-clear-grade>Limpiar</button>
                </div>
            </form>
        </article>
    </section>
</main>
@endsection
