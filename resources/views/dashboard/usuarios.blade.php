@extends('layouts.app')

@section('title', 'Gestion de usuarios | FICCT')

@section('content')
<main class="portal-shell" data-page="usuarios">
    @include('dashboard.partials.sidebar', ['active' => 'usuarios'])

    <section class="portal-main users-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Seguridad</span>
                <h1>Gestion de usuarios</h1>
                <p>Crear, editar y administrar cuentas del sistema CUP.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <a class="primary-action users-new-button" href="#createUserPanel">
                <span>Nuevo usuario</span>
            </a>
        </header>

        <article class="module-card is-wide users-list-card">
            <div class="users-list-head">
                <div>
                    <h2>Listado</h2>
                    <p id="usersCount">Sin datos cargados</p>
                </div>
                <button class="secondary-action" type="button" data-load-users>Actualizar</button>
            </div>

            <label class="users-search">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m16 16 4 4"></path>
                </svg>
                <input id="userSearch" type="search" placeholder="Buscar por nombre, usuario o correo...">
            </label>

            <div class="table-wrap users-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="usersTable">
                        <tr><td colspan="5">Cargando usuarios...</td></tr>
                    </tbody>
                </table>
            </div>
        </article>

        <section id="createUserPanel" class="users-forms-grid">
            <article class="module-card">
                <div class="module-head">
                    <span>CU-03</span>
                    <div>
                        <h2 id="userFormTitle">Nuevo usuario</h2>
                        <p>Registra administrativos o docentes.</p>
                    </div>
                </div>

                <form id="createUserForm" class="portal-form">
                    <input type="hidden" name="form_mode" value="create">
                    <div class="form-grid">
                        <label>Usuario<input name="username" required maxlength="500" pattern="[A-Za-z0-9_.-]{3,500}" placeholder="docente1"></label>
                        <label data-user-field="password">Contrasena<input name="password" type="password" required minlength="6" placeholder="123456"></label>
                        <label>Tipo
                            <select name="tipo" required>
                                <option value="administrativo">Administrativo</option>
                                <option value="docente">Docente</option>
                            </select>
                        </label>
                        <label>Nombre<input name="nombre" required placeholder="Nombre completo"></label>
                        <label>Correo<input name="correo" type="email" required placeholder="correo@uagrm.edu.bo"></label>
                        <label>Telefono<input name="telefono" maxlength="10" pattern="[0-9]{7,10}" placeholder="70000000"></label>
                        <label>Ciudad<input name="ciudad" placeholder="Santa Cruz"></label>
                        <label data-user-field="docente">Titulo profesional<input name="titulo_profesional" placeholder="Lic. en Informatica"></label>
                        <label data-user-field="docente">Registro profesional<input name="nro_registro_profesional" placeholder="REG-001"></label>
                        <label data-user-field="docente">Estado profesional
                            <select name="estado_profesional">
                                <option value="pendiente_revision">Pendiente revision</option>
                                <option value="habilitado">Habilitado</option>
                                <option value="observado">Observado</option>
                                <option value="rechazado">Rechazado</option>
                            </select>
                        </label>
                        <label data-user-field="docente">Max. grupos por periodo<input name="max_grupos_periodo" type="number" min="1" max="20" value="3" placeholder="3"></label>
                        <label data-user-field="docente">Max. horas por semana<input name="max_horas_semana" type="number" min="1" max="60" step="0.25" value="30" placeholder="30"></label>
                        <label data-user-field="docente">Especializacion<input name="especializacion" placeholder="Sistemas"></label>
                        <label data-user-field="docente">Maestria<input name="maestria" placeholder="Educacion Superior"></label>
                        <label data-user-field="docente">Observacion profesional<textarea name="observacion_profesional" rows="3" placeholder="Documentacion pendiente o nota de revision"></textarea></label>
                    </div>
                    <div class="distribution-actions">
                        <button class="primary-action" type="submit"><span>Crear usuario</span></button>
                        <button class="secondary-action" type="button" data-clear-user-form>Limpiar</button>
                    </div>
                </form>
            </article>

            <article class="module-card">
                <div class="module-head">
                    <span>Rol</span>
                    <div>
                        <h2>Asignar rol</h2>
                        <p>Actualiza el rol de una cuenta existente.</p>
                    </div>
                </div>

                <form id="assignRoleForm" class="portal-form">
                    <label>Usuario<input name="username" required maxlength="500" pattern="[A-Za-z0-9_.-]{3,500}" placeholder="docente1"></label>
                    <label>ID rol<input name="codigo_rol" type="number" min="1" required placeholder="2"></label>
                    <button class="secondary-action" type="submit">Asignar rol</button>
                </form>
            </article>
        </section>
    </section>
</main>
@endsection
