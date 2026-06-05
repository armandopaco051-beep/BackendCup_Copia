@extends('layouts.app')

@section('title', 'Validar requisitos fisicos | FICCT')

@section('content')
<main class="portal-shell" data-page="requisitos">
    @include('dashboard.partials.sidebar', ['active' => 'requisitos'])

    <section class="portal-main requirements-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Admisiones</span>
                <h1>Validar requisitos fisicos</h1>
                <p>Revision documental por postulante.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
        </header>

        <label class="users-search requirements-search">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m16 16 4 4"></path>
            </svg>
            <input id="requirementSearch" type="search" placeholder="Buscar por codigo, username, nombre o CI...">
        </label>

        <section class="requirements-workspace">
            <aside class="module-card requirements-list-panel">
                <h2>Postulantes pendientes</h2>
                <div id="requirementsApplicants" class="requirements-applicants">
                    <button type="button">
                        <strong>Cargando postulantes...</strong>
                        <small>Conectando con la base</small>
                    </button>
                </div>
            </aside>

            <article class="module-card requirements-detail-panel">
                <div class="requirements-detail-head">
                    <div>
                        <h2 id="requirementStudentName">Selecciona un postulante</h2>
                        <p id="requirementStudentMeta">Busca por codigo o elige de la lista.</p>
                    </div>
                    <span id="requirementsProgress" class="status-pill is-validated">0 de 3 validados</span>
                </div>

                <form id="requirementsForm" class="requirements-document-form">
                    <input type="hidden" name="username" id="requirementUsername">
                    <input type="hidden" name="validado_por" id="requirementValidator">

                    <div id="requirementsDocuments" class="requirements-documents">
                        <div class="requirement-document">
                            <span class="document-state"></span>
                            <strong>CI entregado</strong>
                            <button type="button" disabled>Validar</button>
                        </div>
                    </div>

                    <label class="requirement-observation">
                        Observacion
                        <textarea name="observacion" rows="3" placeholder="Detalle de ventanilla"></textarea>
                    </label>

                    <p id="requirementsOutput" class="form-alert" hidden></p>

                    <div class="requirements-actions">
                        <button class="secondary-action" type="button" data-request-corrections>Solicitar correcciones</button>
                        <button class="primary-action requirements-approve" type="submit"><span>Validar requisitos</span></button>
                    </div>
                </form>
            </article>
        </section>
    </section>
</main>
@endsection
