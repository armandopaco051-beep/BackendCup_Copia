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
                <p>Consulta los grupos guardados y amplia la capacidad cuando sea necesario.</p>
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
                        <p>El cupo indicado se aplica solamente a los grupos nuevos.</p>
                    </div>
                </div>

                <form id="distributionForm" class="portal-form">
                    <div class="form-grid">
                        <label>Periodo academico
                            <select name="periodo_id" id="distributionPeriodSelect" required>
                                <option value="">Cargando periodos...</option>
                            </select>
                        </label>
                        <label>Cupo maximo para grupos nuevos
                            <input name="cupo_maximo" type="number" min="1" max="200" value="70" required>
                        </label>
                    </div>

                    <div class="distribution-turns" aria-label="Turnos disponibles">
                        <span>Mañana</span>
                        <span>Tarde</span>
                        <span>Noche</span>
                    </div>

                    <div class="distribution-actions">
                        <button class="secondary-action" type="submit">Calcular ampliacion</button>
                        <button class="primary-action distribution-generate" type="button" data-generate-groups>
                            Generar grupos faltantes
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
                        <span>Capacidad guardada</span>
                        <strong id="distributionCapacity">0</strong>
                    </div>
                    <div>
                        <span>Grupos activos</span>
                        <strong id="distributionGroupsCount">0</strong>
                    </div>
                </div>
                <p id="distributionNotice">Selecciona un periodo para consultar su distribucion guardada.</p>
            </aside>
        </section>

        <article class="module-card is-wide distribution-result-card">
            <div class="users-list-head">
                <div>
                    <h2>Distribucion guardada</h2>
                    <p id="distributionCount">Cargando grupos</p>
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
                            <th>Inscritos</th>
                            <th>Disponibles</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="distributionTable">
                        <tr><td colspan="7">Cargando distribucion guardada.</td></tr>
                    </tbody>
                </table>
            </div>
        </article>

        <article id="distributionPreviewPanel" class="module-card is-wide distribution-preview-panel" hidden>
            <div class="users-list-head">
                <div>
                    <h2>Vista previa de ampliacion</h2>
                    <p id="distributionPreviewCount">Sin calculo pendiente</p>
                </div>
                <span class="status-pill is-validated">No modifica lo guardado</span>
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
                    <tbody id="distributionPreviewTable"></tbody>
                </table>
            </div>
        </article>

        <article id="distributionEditPanel" class="module-card is-wide distribution-edit-panel" hidden>
            <div class="module-head">
                <span>Editar</span>
                <div>
                    <h2>Actualizar grupo guardado</h2>
                    <p>La capacidad no puede ser menor que sus estudiantes inscritos.</p>
                </div>
            </div>

            <form id="distributionEditForm" class="portal-form">
                <div class="form-grid">
                    <label>Codigo
                        <input name="codigo" type="text" readonly>
                    </label>
                    <label>Cupo maximo
                        <input name="cupo_maximo" type="number" min="1" max="200" required>
                        <small>Inscritos actualmente: <strong id="distributionEditOccupancy">0</strong></small>
                    </label>
                    <label>Turno
                        <select name="turno" required>
                            <option value="mañana">Mañana</option>
                            <option value="tarde">Tarde</option>
                            <option value="noche">Noche</option>
                        </select>
                    </label>
                    <label>Estado
                        <select name="estado" required>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </label>
                </div>
                <label>Descripcion
                    <textarea name="descripcion" rows="3" maxlength="500"></textarea>
                </label>
                <div class="distribution-actions">
                    <button class="primary-action" type="submit">Guardar cambios</button>
                    <button class="secondary-action" type="button" data-cancel-group-edit>Cancelar</button>
                </div>
            </form>
        </article>
    </section>
</main>
@endsection
