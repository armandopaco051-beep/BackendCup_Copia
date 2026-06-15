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
            <article class="postulant-summary-card">
                <span>CU-06</span>
                <strong>Preinscripcion</strong>
                <p id="postulantPreinscriptionSummary">Cargando datos personales...</p>
                <a href="#mi-preinscripcion">Ver mi registro</a>
            </article>
            <article class="postulant-summary-card">
                <span>CU-07</span>
                <strong>Requisitos</strong>
                <p id="postulantRequirementsSummary">Cargando estado documental...</p>
                <a href="#mis-requisitos">Ver requisitos</a>
            </article>
            <article class="postulant-summary-card">
                <span>CU-08</span>
                <strong>Pago</strong>
                <p id="postulantPaymentSummary">Cargando estado del pago...</p>
                <a href="#mi-pago">Ver pago</a>
            </article>
            <article class="postulant-summary-card">
                <span>Grupo</span>
                <strong>Inscripcion</strong>
                <p id="postulantGroupSummary">Cargando grupo asignado...</p>
                <a href="#mi-grupo">Ver grupo y horario</a>
            </article>
        </section>

        <article id="mi-preinscripcion" class="module-card is-wide postulant-section">
            <div class="module-head">
                <span>CU-06</span>
                <div>
                    <h2>Mi preinscripcion</h2>
                    <p>Datos registrados y opciones de carrera para el Curso Preuniversitario.</p>
                </div>
                <span id="postulantStatus" class="status-pill">Cargando</span>
            </div>

            <div id="postulantPreinscriptionFields" class="profile-fields"></div>
            <div class="postulant-career-block">
                <h3>Carreras seleccionadas</h3>
                <div id="postulantCareerOptions" class="postulant-career-list">
                    <p class="module-note">Cargando carreras...</p>
                </div>
            </div>
            <a id="postulantFormDownload" class="secondary-action postulant-download" href="#" target="_blank" rel="noopener">
                Descargar formulario PDF
            </a>
        </article>

        <article id="mis-requisitos" class="module-card is-wide postulant-section">
            <div class="module-head">
                <span>CU-07</span>
                <div>
                    <h2>Mis requisitos fisicos</h2>
                    <p>Consulta el resultado de la revision realizada en ventanilla.</p>
                </div>
                <span id="postulantRequirementsStatus" class="status-pill">Cargando</span>
            </div>
            <div id="postulantRequirementsList" class="postulant-requirements-list">
                <p class="module-note">Cargando documentos...</p>
            </div>
            <p id="postulantRequirementsObservation" class="module-note"></p>
        </article>

        <article id="mi-pago" class="module-card is-wide postulant-section">
            <div class="module-head">
                <span>CU-08</span>
                <div>
                    <h2>Mi pago de matricula</h2>
                    <p>Estado de la transaccion unica de 700 Bs. procesada mediante Stripe.</p>
                </div>
                <span id="postulantPaymentStatus" class="status-pill">Cargando</span>
            </div>
            <div id="postulantPaymentFields" class="profile-fields"></div>
        </article>

        <article id="mi-grupo" class="module-card is-wide form-panel postulant-group-panel postulant-section">
            <div class="module-head">
                <span>Grupo</span>
                <div>
                    <h2>Mi grupo CUP</h2>
                    <p>Elige un grupo con cupo disponible para completar tu inscripcion academica.</p>
                </div>
            </div>

            <div id="postulantGroupCurrent" class="module-note">Cargando grupo actual...</div>

            <form id="postulantGroupForm" class="portal-form">
                <label>Grupo disponible
                    <select name="id_grupo" id="postulantGroupSelect" required>
                        <option value="">Cargando grupos...</option>
                    </select>
                </label>
                <p id="postulantGroupOutput" class="module-note"></p>
                <button class="primary-action" type="submit"><span>Inscribirme al grupo</span></button>
            </form>
        </article>

        <article id="mi-horario" class="module-card is-wide postulant-section">
            <div class="module-head">
                <span>Horario</span>
                <div>
                    <h2>Mi horario academico</h2>
                    <p>Materias, docentes y aulas correspondientes al grupo inscrito.</p>
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
                            <th>Docente</th>
                            <th>Aula y ubicacion</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="postulantScheduleBody">
                        <tr><td colspan="8">Cargando horario...</td></tr>
                    </tbody>
                </table>
            </div>
        </article>

        <article id="mis-calificaciones" class="module-card is-wide postulant-section">
            <div class="module-head">
                <span>Academico</span>
                <div>
                    <h2>Mis calificaciones</h2>
                    <p>Notas registradas por materia y promedio ponderado.</p>
                </div>
                <span id="postulantGradesStatus" class="status-pill">Cargando</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Grupo</th>
                            <th>Nota 1</th>
                            <th>Nota 2</th>
                            <th>Nota 3</th>
                            <th>Promedio</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="postulantGradesBody">
                        <tr><td colspan="7">Cargando calificaciones...</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="postulantAttendanceSummary" class="postulant-attendance-summary"></div>
        </article>

        <article id="mi-carrera" class="module-card is-wide postulant-section">
            <div class="module-head">
                <span>Resultado</span>
                <div>
                    <h2>Asignacion de carrera</h2>
                    <p>Resultado final segun promedio, opciones seleccionadas y cupos disponibles.</p>
                </div>
                <span id="postulantCareerStatus" class="status-pill">Pendiente</span>
            </div>
            <div id="postulantAssignedCareer" class="module-note">
                La asignacion de carrera todavia no fue publicada.
            </div>
        </article>

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
