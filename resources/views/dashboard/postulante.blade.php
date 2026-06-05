@extends('layouts.app')

@section('title', 'Panel postulante | FICCT')

@section('content')
<main class="portal-shell" data-page="postulante">
    @include('dashboard.partials.sidebar', ['active' => 'postulante-dashboard'])

    <section class="portal-main">
        <header class="users-header">
            <div>
                <span class="section-kicker">Postulante</span>
                <h1>Mi proceso CUP</h1>
                <p>Consulta el avance de tu preinscripcion, requisitos, pago y calificaciones.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
        </header>

        <section class="summary-grid">
            <article>
                <span>CU-06</span>
                <strong>Preinscripcion</strong>
                <p>Datos personales y carreras seleccionadas para el proceso de admision.</p>
            </article>
            <article>
                <span>CU-07</span>
                <strong>Requisitos</strong>
                <p>Estado de documentos fisicos validados por ventanilla.</p>
            </article>
            <article>
                <span>CU-08</span>
                <strong>Pago</strong>
                <p>Estado del pago de matricula del Curso Preuniversitario.</p>
            </article>
            <article>
                <span>Academico</span>
                <strong>Calificaciones</strong>
                <p>Notas y promedio cuando sean publicados por el docente.</p>
            </article>
        </section>

        <article id="profileSummary" class="module-card profile-panel">
            <div>
                <span class="section-kicker">Cuenta actual</span>
                <h2 id="profileName">Cargando perfil...</h2>
                <p id="profileRole">Esperando respuesta del servidor.</p>
            </div>
            <div id="profileFields" class="profile-fields"></div>
        </article>
    </section>
</main>
@endsection
