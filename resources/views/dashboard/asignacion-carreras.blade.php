@extends('layouts.app')

@section('title', 'Asignacion de carreras | FICCT')

@section('content')
<main class="portal-shell" data-page="asignacion-carreras">
    @include('dashboard.partials.sidebar', ['active' => 'asignacion-carreras'])

    <section class="portal-main career-assignment-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Academico</span>
                <h1>Lista de Admitidos</h1>
                <p>Asigna cupos por promedio final, primera opcion y segunda opcion.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <div class="module-actions">
                <button class="secondary-action" type="button" data-load-career-assignments>Actualizar</button>
                <button class="primary-action" type="button" data-generate-career-assignments>Generar asignacion</button>
            </div>
        </header>

        <section class="schedule-summary-grid" id="careerAssignmentSummary">
            <article class="module-card schedule-summary-card">
                <span class="section-kicker">Nota minima</span>
                <h2>60.00</h2>
                <p>Solo compiten postulantes aprobados.</p>
            </article>
            <article class="module-card schedule-summary-card">
                <span class="section-kicker">Prioridad</span>
                <h2>Promedio</h2>
                <p>Se usan decimales y desempates.</p>
            </article>
            <article class="module-card schedule-summary-card">
                <span class="section-kicker">Opciones</span>
                <h2>1ra / 2da</h2>
                <p>Primero intenta la carrera principal.</p>
            </article>
        </section>

        <section class="teacher-subject-layout schedule-workspace">
            <article class="module-card form-panel">
                <div class="module-head">
                    <span>Cupos</span>
                    <div>
                        <h2>Generar resultado final</h2>
                        <p>Ordena por promedio final, nota 3, nota 2 y nota 1 antes de asignar cupos.</p>
                    </div>
                </div>

                <form id="careerAssignmentForm" class="portal-form">
                    <label class="inline-check">
                        <input type="checkbox" name="sobrescribir" value="1">
                        <span>Sobrescribir asignacion existente</span>
                    </label>

                    <p id="careerAssignmentOutput" class="module-note"></p>

                    <button class="primary-action" type="submit">
                        <span>Ejecutar asignacion</span>
                    </button>
                </form>
            </article>

            <article class="module-card schedule-rules">
                <span class="section-kicker">Regla de negocio</span>
                <h2>Merito academico</h2>
                <p>Si la primera opcion no tiene cupo, el sistema intenta la segunda opcion del postulante.</p>
                <div class="schedule-option-list">
                    <span><strong>1</strong> Promedio final descendente</span>
                    <span><strong>2</strong> Nota 3, nota 2, nota 1</span>
                    <span><strong>3</strong> Primera opcion con cupo</span>
                    <span><strong>4</strong> Segunda opcion con cupo</span>
                </div>
            </article>
        </section>

        <article class="module-card is-wide">
            <div class="users-list-head">
                <div>
                    <h2>Cupos por carrera</h2>
                    <p id="careerQuotaCount">Sin datos cargados</p>
                </div>
            </div>

            <div id="careerQuotaCards" class="career-quota-grid">
                <p class="module-note">Cargando cupos...</p>
            </div>
        </article>

        <article class="module-card is-wide">
            <div class="users-list-head">
                <div>
                    <h2>Resultado de asignacion</h2>
                    <p id="careerAssignmentCount">Sin datos cargados</p>
                </div>
            </div>

            <label class="users-search">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m16 16 4 4"></path>
                </svg>
                <input id="careerAssignmentSearch" type="search" placeholder="Buscar postulante, carrera o estado...">
            </label>

            <div class="table-wrap users-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Postulante</th>
                            <th>Promedio</th>
                            <th>Desempate</th>
                            <th>Primera opcion</th>
                            <th>Segunda opcion</th>
                            <th>Asignada</th>
                            <th>Estado</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody id="careerAssignmentTable">
                        <tr><td colspan="8">Cargando asignaciones...</td></tr>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>
@endsection
