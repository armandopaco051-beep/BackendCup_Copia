@extends('layouts.app')

@section('title', 'Bitacora CUP | FICCT')

@section('content')
<main class="portal-shell" data-page="bitacora">
    @include('dashboard.partials.sidebar', ['active' => 'bitacora'])

    <section class="portal-main users-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Seguridad</span>
                <h1>Bitacora del sistema</h1>
                <p>Historial de movimientos realizados por los usuarios dentro del portal.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <button class="secondary-action" type="button" data-load-bitacora>Actualizar</button>
        </header>

        <article class="module-card is-wide users-list-card">
            <div class="users-list-head">
                <div>
                    <h2>Movimientos registrados</h2>
                    <p id="bitacoraCount">Sin datos cargados</p>
                </div>
            </div>

            <form id="bitacoraFilters" class="portal-form compact-form">
                <label>Buscar
                    <input name="buscar" type="search" maxlength="200" placeholder="Usuario, accion, modulo o ruta...">
                </label>
                <label>Modulo
                    <input name="modulo" maxlength="120" placeholder="admisiones">
                </label>
                <label>Accion
                    <input name="accion" maxlength="120" placeholder="aprobar_requisitos">
                </label>
                <label>Limite
                    <input name="limite" type="number" min="1" max="200" value="80">
                </label>
                <button class="secondary-action" type="submit">Filtrar</button>
            </form>

            <div class="table-wrap users-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Accion</th>
                            <th>Modulo</th>
                            <th>Ruta</th>
                            <th>Datos</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody id="bitacoraTable">
                        <tr><td colspan="7">Cargando bitacora...</td></tr>
                    </tbody>
                </table>
            </div>
            <p id="bitacoraOutput" class="module-note"></p>
        </article>
    </section>
</main>
@endsection
