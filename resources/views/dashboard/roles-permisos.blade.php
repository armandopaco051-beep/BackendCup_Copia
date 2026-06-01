@extends('layouts.app')

@section('title', 'Roles y permisos | FICCT')

@section('content')
<main class="portal-shell" data-page="roles">
    @include('dashboard.partials.sidebar', ['active' => 'roles'])

    <section class="portal-main roles-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Seguridad</span>
                <h1>Roles y permisos</h1>
                <p>Define los accesos por rol para cada modulo del sistema.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <a class="primary-action users-new-button" href="#createRolePanel">
                <span>Nuevo rol</span>
            </a>
        </header>

        <section id="rolesCards" class="roles-card-grid" aria-label="Roles registrados">
            <article class="role-summary-card">
                <span class="role-shield">□</span>
                <strong>Cargando roles...</strong>
                <small>Conectando con la base</small>
            </article>
        </section>

        <article class="module-card is-wide permission-matrix-card">
            <div class="permission-head">
                <div>
                    <h2 id="permissionMatrixTitle">Matriz de permisos</h2>
                    <p>Marca los permisos asignados a este rol.</p>
                </div>
                <button class="secondary-action" type="button" data-load-security>Actualizar</button>
            </div>

            <form id="permissionMatrixForm" class="permission-matrix">
                <input type="hidden" name="rol_id" id="matrixRoleId">
                <div id="permissionMatrixRows" class="permission-rows">
                    <div class="permission-row">
                        <strong>Permisos</strong>
                        <span>Cargando permisos reales...</span>
                    </div>
                </div>
                <div class="permission-actions">
                    <button class="secondary-action" type="reset">Cancelar</button>
                    <button class="primary-action permission-save" type="submit"><span>Guardar cambios</span></button>
                </div>
            </form>
        </article>

        <section id="createRolePanel" class="users-forms-grid">
            <article class="module-card">
                <div class="module-head">
                    <span>Rol</span>
                    <div>
                        <h2>Crear rol</h2>
                        <p>Registra un nuevo rol de seguridad.</p>
                    </div>
                </div>
                <form id="createRoleForm" class="portal-form">
                    <label>Nombre<input name="nombre" required placeholder="coordinador cup"></label>
                    <button class="primary-action" type="submit"><span>Guardar rol</span></button>
                </form>
            </article>

            <article class="module-card">
                <div class="module-head">
                    <span>Permiso</span>
                    <div>
                        <h2>Crear permiso</h2>
                        <p>Agrega una nueva accion controlable por rol.</p>
                    </div>
                </div>
                <form id="createPermissionForm" class="portal-form">
                    <label>Nombre<input name="nombre" required placeholder="aprobar_postulante"></label>
                    <button class="primary-action" type="submit"><span>Guardar permiso</span></button>
                </form>
            </article>
        </section>

        <pre id="securityOutput" class="module-output"></pre>
    </section>
</main>
@endsection
