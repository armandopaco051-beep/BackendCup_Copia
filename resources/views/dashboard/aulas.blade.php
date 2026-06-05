@extends('layouts.app')

@section('title', 'Gestion de aulas | FICCT')

@section('content')
<main class="portal-shell" data-page="aulas">
    @include('dashboard.partials.sidebar', ['active' => 'aulas'])

    <section class="portal-main classrooms-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Infraestructura</span>
                <h1>Gestion de aulas</h1>
                <p>Registra aulas y define capacidad para validar cupos por grupo.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <a class="primary-action users-new-button" href="#classroomFormPanel">
                <span>Nueva aula</span>
            </a>
        </header>

        <section class="habilitation-stats classrooms-stats">
            <article>
                <span>Total aulas</span>
                <strong id="classroomTotal">0</strong>
            </article>
            <article>
                <span>Disponibles</span>
                <strong id="classroomAvailable">0</strong>
            </article>
            <article>
                <span>Capacidad total</span>
                <strong id="classroomCapacity">0</strong>
            </article>
            <article>
                <span>Promedio capacidad</span>
                <strong id="classroomAverage">0</strong>
            </article>
        </section>

        <article class="module-card is-wide classrooms-list-card">
            <div class="users-list-head">
                <div>
                    <h2>Listado de aulas</h2>
                    <p id="classroomCount">Sin datos cargados</p>
                </div>
                <button class="secondary-action" type="button" data-load-classrooms>Actualizar</button>
            </div>

            <label class="users-search">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m16 16 4 4"></path>
                </svg>
                <input id="classroomSearch" type="search" placeholder="Buscar por aula, tipo, piso o estado...">
            </label>

            <div class="table-wrap users-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Aula</th>
                            <th>Tipo</th>
                            <th>Piso</th>
                            <th>Capacidad</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="classroomsTable">
                        <tr><td colspan="6">Cargando aulas...</td></tr>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="module-card is-wide classroom-capacity-card">
            <div class="users-list-head">
                <div>
                    <span class="section-kicker">Validacion de cupos</span>
                    <h2>Cupos por aula</h2>
                    <p id="classroomCapacityCount">Ocupacion real segun horarios registrados.</p>
                </div>
                <button class="secondary-action" type="button" data-load-classroom-capacity>Actualizar cupos</button>
            </div>

            <div class="table-wrap users-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Aula</th>
                            <th>Piso</th>
                            <th>Capacidad</th>
                            <th>Ocupacion</th>
                            <th>Uso</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="classroomCapacityTable">
                        <tr><td colspan="6">Cargando cupos por aula...</td></tr>
                    </tbody>
                </table>
            </div>
        </article>

        <article id="classroomFormPanel" class="module-card form-panel">
            <div class="module-head">
                <span>Aula</span>
                <div>
                    <h2 id="classroomFormTitle">Nueva aula</h2>
                    <p id="classroomSchemaNotice">Consultando estructura del backend...</p>
                </div>
            </div>

            <form id="classroomForm" class="portal-form">
                <div class="form-grid">
                    <label>Nro. aula<input name="nro_aula" type="number" min="1" required placeholder="301"></label>
                    <label>Tipo<input name="tipo" required placeholder="Teorica"></label>
                    <label>Piso<input name="piso" required placeholder="3"></label>
                    <label>Capacidad<input name="capacidad" type="number" value="70" readonly></label>
                    <label>Estado
                        <select name="estado">
                            <option value="disponible">Disponible</option>
                            <option value="mantenimiento">Mantenimiento</option>
                            <option value="inactiva">Inactiva</option>
                        </select>
                    </label>
                </div>
                <div class="distribution-actions">
                    <button class="primary-action distribution-generate" type="submit"><span>Guardar aula</span></button>
                    <button class="secondary-action" type="button" data-clear-classroom>Limpiar</button>
                </div>
            </form>
        </article>
    </section>
</main>
@endsection
