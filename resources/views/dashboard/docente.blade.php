@extends('layouts.app')

@section('title', 'Panel docente | FICCT')

@section('content')
<main class="portal-shell" data-page="docente">
    @include('dashboard.partials.sidebar', ['active' => 'docente-dashboard'])

    <section class="portal-main">
        <header class="users-header">
            <div>
                <span class="section-kicker">Docente</span>
                <h1>Panel docente</h1>
                <p>Accede a tus funciones academicas del Curso Preuniversitario.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
        </header>

        <section class="summary-grid">
            <article>
                <span>Notas</span>
                <strong>Calificaciones</strong>
                <p>Registra y actualiza notas de postulantes por grupo y materia.</p>
                <a class="secondary-action" href="/dashboard/calificaciones">Abrir calificaciones</a>
            </article>
            <article>
                <span>Cuenta</span>
                <strong>Perfil docente</strong>
                <p>Consulta tus datos registrados como docente.</p>
                <a class="secondary-action" href="/dashboard/perfil">Ver perfil</a>
            </article>
        </section>

        <article class="module-card is-wide">
            <div class="module-head">
                <span>Resumen</span>
                <div>
                    <h2>Funciones disponibles</h2>
                    <p>Por ahora el docente trabaja principalmente con el registro de calificaciones.</p>
                </div>
            </div>
        </article>
    </section>
</main>
@endsection
