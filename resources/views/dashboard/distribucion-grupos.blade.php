@extends('layouts.app')

@section('title', 'Distribucion de grupos | FICCT')

@section('content')
<main class="portal-shell" data-page="distribucion">
    @include('dashboard.partials.sidebar', ['active' => 'distribucion'])

    <section class="portal-main distribution-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">CU-13</span>
                <h1>Calcular distribucion de grupos</h1>
                <p>Genera grupos automaticamente segun postulantes habilitados y cupo maximo.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <button class="secondary-action" type="button" data-calculate-groups>Vista previa</button>
        </header>

        <section class="distribution-layout">
            <article class="module-card distribution-control-card">
                <div class="module-head">
                    <span>Parametros</span>
                    <div>
                        <h2>Reglas de calculo</h2>
                        <p>El sistema usa Grupo-G01, Grupo-G02 y turnos rotativos.</p>
                    </div>
                </div>

                <form id="distributionForm" class="portal-form">
                    <div class="form-grid">
                        <label>ID periodo
                            <input name="periodo_id" type="number" placeholder="Opcional">
                        </label>
                        <label>Cupo maximo por grupo
                            <input name="cupo_maximo" type="number" min="1" max="200" value="70">
                        </label>
                    </div>

                    <div class="distribution-turns">
                        <span>Mañana</span>
                        <span>Tarde</span>
                        <span>Noche</span>
                    </div>

                    <div class="distribution-actions">
                        <button class="secondary-action" type="submit">Calcular distribucion</button>
                        <button class="primary-action distribution-generate" type="button" data-generate-groups>
                            <span>Generar grupos</span>
                        </button>
                    </div>
                </form>
            </article>

            <aside class="module-card distribution-summary-card">
                <span class="section-kicker">Resumen</span>
                <div class="distribution-summary">
                    <div>
                        <span>Postulantes habilitados</span>
                        <strong id="distributionTotal">0</strong>
                    </div>
                    <div>
                        <span>Cupo maximo</span>
                        <strong id="distributionCapacity">70</strong>
                    </div>
                    <div>
                        <span>Grupos necesarios</span>
                        <strong id="distributionGroupsCount">0</strong>
                    </div>
                </div>
                <p id="distributionNotice">Calcula una vista previa antes de generar grupos.</p>
            </aside>
        </section>

        <article class="module-card is-wide distribution-result-card">
            <div class="users-list-head">
                <div>
                    <h2>Grupos calculados</h2>
                    <p id="distributionCount">Sin datos calculados</p>
                </div>
                <span id="distributionPeriod" class="status-pill">Periodo sin definir</span>
            </div>

            <div class="table-wrap users-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Turno</th>
                            <th>Cupo maximo</th>
                            <th>Descripcion</th>
                        </tr>
                    </thead>
                    <tbody id="distributionTable">
                        <tr><td colspan="4">Pulsa calcular distribucion.</td></tr>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>
@endsection
