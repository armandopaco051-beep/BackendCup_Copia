@extends('layouts.app')

@section('title', 'Preinscripciones CUP | FICCT')

@section('content')
<main class="portal-shell" data-page="preinscripciones">
    @include('dashboard.partials.sidebar', ['active' => 'preinscripciones'])

    <section class="portal-main preinscriptions-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Admisiones</span>
                <h1>Preinscripciones CUP</h1>
                <p>Registro de postulantes al Curso Preuniversitario.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <a class="primary-action users-new-button" href="#preinscriptionFormPanel">
                <span>Nueva preinscripcion</span>
            </a>
        </header>

        <article class="module-card is-wide preinscriptions-list-card">
            <div class="users-list-head">
                <div>
                    <h2>Listado de postulantes</h2>
                    <p id="preinscriptionsCount">Sin datos cargados</p>
                </div>
                <button class="secondary-action" type="button" data-load-preinscriptions>Actualizar</button>
            </div>

            <label class="users-search">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m16 16 4 4"></path>
                </svg>
                <input id="preinscriptionSearch" type="search" placeholder="Buscar por nombre, CI o folio...">
            </label>

            <div class="table-wrap users-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>CI</th>
                            <th>Postulante</th>
                            <th>Carrera</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="preinscriptionsTable">
                        <tr><td colspan="7">Cargando postulantes...</td></tr>
                    </tbody>
                </table>
            </div>
        </article>

        <article id="preinscriptionFormPanel" class="module-card is-wide">
            <div class="module-head">
                <span>CU-06</span>
                <div>
                    <h2>Nueva preinscripcion</h2>
                    <p>Captura los datos personales del bachiller para iniciar admision. El usuario y password temporal se enviaran por correo cuando sea habilitado.</p>
                </div>
            </div>

            <form id="preinscriptionForm" class="portal-form">
                <div class="form-grid">
                    <label>Correo<input name="correo" type="email" required placeholder="correo@email.com"></label>
                    <label>CI<input name="ci" required maxlength="100" pattern="[0-9A-Za-z-]{5,100}" placeholder="12345678"></label>
                    <label>Nombre<input name="nombre" required placeholder="Juan Perez"></label>
                    <label>Telefono<input name="telefono" required maxlength="10" pattern="[0-9]{7,10}" placeholder="70000000"></label>
                    <label>Ciudad<input name="ciudad" required placeholder="Santa Cruz"></label>
                    <label>Colegio<input name="colegio_procedencia" required placeholder="Colegio Nacional"></label>
                    <label>Direccion<input name="direccion" required placeholder="Av. Siempre Viva"></label>
                    <label>Fecha nacimiento<input name="fecha_nacimiento" type="date" required></label>
                    <label>Genero<input name="genero" required placeholder="Masculino"></label>
                    <label>Cod. titulo<input name="cod_titulo_bachiller" required placeholder="TIT-001"></label>
                    <label>Primera carrera
                        <select name="carrera_principal" id="careerFirstSelect" required>
                            <option value="">Cargando carreras...</option>
                        </select>
                    </label>
                    <label>Segunda carrera
                        <select name="carrera_secundaria" id="careerSecondSelect">
                            <option value="">Opcional</option>
                        </select>
                    </label>
                </div>
                <p id="preinscriptionOutput" class="module-note"></p>
                <button class="primary-action" type="submit"><span>Registrar preinscripcion</span></button>
            </form>
        </article>
    </section>
</main>
@endsection
