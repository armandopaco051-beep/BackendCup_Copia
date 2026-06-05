@extends('layouts.app')

@section('title', 'Catalogos academicos | FICCT')

@section('content')
<main class="portal-shell" data-page="catalogos">
    @include('dashboard.partials.sidebar', ['active' => 'catalogos'])

    <section class="portal-main catalogs-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Academico</span>
                <h1>Carreras y materias</h1>
                <p>Gestiona carreras habilitadas para preinscripcion y materias para registrar calificaciones.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <div class="module-actions">
                <a class="secondary-action" href="#carreras">Gestionar carreras</a>
                <a class="secondary-action" href="#materias">Gestionar materias</a>
            </div>
        </header>

        <section class="catalogs-stack">
            <article id="carreras" class="module-card is-wide form-panel">
                <div class="module-head">
                    <span>Carreras</span>
                    <div>
                        <h2 id="careerFormTitle">Gestionar carrera</h2>
                        <p>Las carreras habilitadas aparecen en el formulario de preinscripcion.</p>
                    </div>
                </div>

                <form id="careerCatalogForm" class="portal-form">
                    <input type="hidden" name="original_codigo">
                    <div class="form-grid">
                        <label>Codigo<input name="codigo" required maxlength="50" pattern="[A-Za-z0-9_-]{2,50}" placeholder="SIS"></label>
                        <label>Nombre<input name="nombre" required maxlength="500" placeholder="Ingenieria de Sistemas"></label>
                        <label>Estado
                            <select name="estado" required>
                                <option value="habilitada">Habilitada</option>
                                <option value="inactiva">Inactiva</option>
                            </select>
                        </label>
                    </div>
                    <p id="careerCatalogOutput" class="module-note"></p>
                    <div class="distribution-actions">
                        <button class="primary-action" type="submit"><span>Guardar carrera</span></button>
                        <button class="secondary-action" type="button" data-clear-career-catalog>Limpiar</button>
                    </div>
                </form>
            </article>

            <article class="module-card is-wide">
                <div class="users-list-head">
                    <div>
                        <h2>Carreras registradas</h2>
                        <p id="careerCatalogCount">Sin datos cargados</p>
                    </div>
                    <button class="secondary-action" type="button" data-load-career-catalog>Actualizar</button>
                </div>
                <div class="table-wrap users-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="careerCatalogTable">
                            <tr><td colspan="4">Cargando carreras...</td></tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article id="materias" class="module-card is-wide form-panel">
                <div class="module-head">
                    <span>Materias</span>
                    <div>
                        <h2 id="subjectFormTitle">Gestionar materia</h2>
                        <p>Las materias habilitadas aparecen al registrar calificaciones.</p>
                    </div>
                </div>

                <form id="subjectCatalogForm" class="portal-form">
                    <input type="hidden" name="original_id">
                    <div class="form-grid">
                        <label>ID<input name="id" required maxlength="100" pattern="[A-Za-z0-9_-]{2,100}" placeholder="MAT-001"></label>
                        <label>Nombre<input name="nombre" required maxlength="500" placeholder="Matematica"></label>
                        <label>Estado
                            <select name="estado" required>
                                <option value="habilitada">Habilitada</option>
                                <option value="inactiva">Inactiva</option>
                            </select>
                        </label>
                    </div>
                    <p id="subjectCatalogOutput" class="module-note"></p>
                    <div class="distribution-actions">
                        <button class="primary-action" type="submit"><span>Guardar materia</span></button>
                        <button class="secondary-action" type="button" data-clear-subject-catalog>Limpiar</button>
                    </div>
                </form>
            </article>

            <article class="module-card is-wide">
                <div class="users-list-head">
                    <div>
                        <h2>Materias registradas</h2>
                        <p id="subjectCatalogCount">Sin datos cargados</p>
                    </div>
                    <button class="secondary-action" type="button" data-load-subject-catalog>Actualizar</button>
                </div>
                <div class="table-wrap users-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="subjectCatalogTable">
                            <tr><td colspan="4">Cargando materias...</td></tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </section>
</main>
@endsection
