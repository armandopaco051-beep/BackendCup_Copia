@extends('layouts.app')

@section('title', 'Periodo academico | FICCT')

@section('content')
<main class="portal-shell" data-page="periodo">
    @include('dashboard.partials.sidebar', ['active' => 'periodo'])

    <section class="portal-main period-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Configuracion</span>
                <h1>Periodo academico</h1>
                <p>Define el ciclo CUP y la ventana unica para preinscripcion, requisitos y pago.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <button class="secondary-action" type="button" data-load-periods>Actualizar</button>
        </header>

        <section class="period-layout">
            <article class="module-card period-form-card">
                <div class="module-head">
                    <span>CUP</span>
                    <div>
                        <h2>Configurar periodo</h2>
                        <p id="periodSchemaNotice">Consultando estructura del backend...</p>
                    </div>
                </div>

                <form id="periodForm" class="portal-form">
                    <input type="hidden" name="id">
                    <div class="form-grid">
                        <label>Nombre
                            <input name="nombre" placeholder="Periodo CUP 2026-I">
                        </label>
                        <label>Semestre
                            <select name="semestre" required>
                                <option value="1">1</option>
                                <option value="2">2</option>
                            </select>
                        </label>
                        <label>Año
                            <input name="anio" type="number" min="2000" max="2100" required placeholder="2026">
                        </label>
                        <label>Estado
                            <select name="estado">
                                <option value="pendiente">Pendiente</option>
                                <option value="activo">Activo</option>
                                <option value="cerrado">Cerrado</option>
                            </select>
                        </label>
                    </div>

                    <div class="period-windows">
                        <h3>Ventana del proceso</h3>
                        <div class="form-grid">
                            <label>Inicio preinscripcion
                                <input name="fecha_inicio_preinscripcion" type="date">
                            </label>
                            <label>Fin preinscripcion
                                <input name="fecha_fin_preinscripcion" type="date">
                            </label>
                        </div>
                        <p class="module-note">Estas mismas fechas se aplican tambien para validar requisitos y registrar pagos.</p>
                    </div>

                    <p id="periodOutput" class="module-note"></p>
                    <div class="distribution-actions">
                        <button class="primary-action" type="submit"><span>Guardar periodo</span></button>
                        <button class="secondary-action" type="button" data-clear-period>Limpiar</button>
                    </div>
                </form>
            </article>

            <aside class="module-card period-rules-card">
                <span class="section-kicker">Flujo aplicado</span>
                <h2>Reglas del periodo</h2>
                <div class="period-rule-list">
                    <div>
                        <strong>Preinscripcion</strong>
                        <span>Solo se aceptan postulantes dentro de la fecha configurada.</span>
                    </div>
                    <div>
                        <strong>Requisitos fisicos</strong>
                        <span>Usa la misma fecha de inicio y fin de preinscripcion.</span>
                    </div>
                    <div>
                        <strong>Pago de matricula</strong>
                        <span>Usa la misma fecha de inicio y fin de preinscripcion.</span>
                    </div>
                </div>
            </aside>
        </section>

        <article class="module-card is-wide period-list-card">
            <div class="users-list-head">
                <div>
                    <h2>Periodos registrados</h2>
                    <p id="periodCount">Sin datos cargados</p>
                </div>
            </div>
            <div class="table-wrap users-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Periodo</th>
                            <th>Semestre</th>
                            <th>Año</th>
                            <th>Estado</th>
                            <th>Ventana</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="periodTable">
                        <tr><td colspan="6">Cargando periodos...</td></tr>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>
@endsection
