@extends('layouts.app')

@section('title', 'Habilitacion | FICCT')

@section('content')
<main class="portal-shell" data-page="habilitacion">
    @include('dashboard.partials.sidebar', ['active' => 'habilitacion'])

    <section class="portal-main habilitation-page">
        <header class="users-header">
            <div>
                <span class="section-kicker">Admisiones</span>
                <h1>Habilitar postulante</h1>
                <p>Postulantes que cumplen requisitos y pago para rendir el CUP.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <button class="secondary-action" type="button" data-load-habilitations>Actualizar candidatos</button>
        </header>

        <section class="habilitation-stats">
            <article>
                <span>Total candidatos</span>
                <strong id="habilitationTotal">0</strong>
            </article>
            <article>
                <span>Listos para habilitar</span>
                <strong id="habilitationReady">0</strong>
            </article>
            <article>
                <span>Habilitados</span>
                <strong id="habilitationEnabled">0</strong>
            </article>
            <article>
                <span>En revision</span>
                <strong id="habilitationReview">0</strong>
            </article>
        </section>

        <label class="users-search habilitation-search">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m16 16 4 4"></path>
            </svg>
            <input id="habilitationSearch" type="search" placeholder="Buscar por folio, nombre, CI o carrera...">
        </label>

        <section class="habilitation-workspace">
            <article class="module-card habilitation-table-card">
                <div class="users-list-head">
                    <div>
                        <h2>Candidatos a habilitacion</h2>
                        <p id="habilitationCount">Sin datos cargados</p>
                    </div>
                    <span id="habilitationReadyBadge" class="status-pill is-admitted">0 listos</span>
                </div>

                <div class="table-wrap users-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Postulante</th>
                                <th>Requisitos</th>
                                <th>Pago</th>
                                <th>Estado</th>
                                <th>Accion</th>
                            </tr>
                        </thead>
                        <tbody id="habilitationTable">
                            <tr><td colspan="6">Cargando candidatos...</td></tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <aside class="module-card habilitation-detail">
                <span class="section-kicker">Detalle</span>
                <h2 id="habilitationDetailName">Selecciona un candidato</h2>
                <p id="habilitationDetailMeta">Revisa sus validaciones antes de habilitar.</p>

                <div class="habilitation-checks">
                    <div>
                        <span>Requisitos fisicos</span>
                        <strong id="habilitationDetailRequirements">Pendiente</strong>
                    </div>
                    <div>
                        <span>Pago matricula</span>
                        <strong id="habilitationDetailPayment">Pendiente</strong>
                    </div>
                    <div>
                        <span>Estado actual</span>
                        <strong id="habilitationDetailStatus">En revision</strong>
                    </div>
                </div>

                <form id="enableApplicantForm" class="portal-form">
                    <input type="hidden" name="username" id="habilitationUsername">
                    <label>Observacion
                        <textarea name="observacion" rows="4">Postulante habilitado tras validar requisitos y pago</textarea>
                    </label>
                    <p id="enableOutput" class="form-alert" hidden></p>
                    <button class="primary-action" type="submit"><span>Habilitar postulante</span></button>
                </form>
            </aside>
        </section>
    </section>
</main>
@endsection
