@extends('layouts.app')

@section('title', 'Dashboard CUP | FICCT')

@section('content')
<main class="cup-dashboard" data-page="dashboard">
    @include('dashboard.partials.sidebar', ['active' => 'dashboard'])
    <button class="sidebar-scrim" type="button" data-sidebar-overlay aria-label="Cerrar menu"></button>

    <header class="cup-topbar">
        <button class="cup-icon-button" type="button" data-toggle-sidebar aria-label="Abrir menu">
            <span></span>
        </button>

        <label class="cup-search" aria-label="Buscar">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m16 16 4 4"></path>
            </svg>
            <input type="search" placeholder="Buscar postulante, CI, modulo...">
        </label>

        <div class="cup-user-actions">
            <button class="cup-ghost-button" type="button" aria-label="Notificaciones">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                    <path d="M13.7 21a2 2 0 0 1-3.4 0"></path>
                </svg>
            </button>
            <a class="cup-avatar" href="/dashboard/perfil" aria-label="Perfil">AD</a>
            <button class="cup-ghost-button" type="button" aria-label="Cerrar sesion" data-logout>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <path d="m16 17 5-5-5-5"></path>
                    <path d="M21 12H9"></path>
                </svg>
            </button>
        </div>
    </header>

    <section class="cup-content">
        <div class="cup-title-row">
            <div>
                <span class="cup-kicker">Universidad Autonoma Gabriel Rene Moreno</span>
                <h1>Dashboard del Curso Preuniversitario</h1>
                <p id="dashboardUser" class="cup-session">Cargando sesion...</p>
            </div>
            <span id="dashboardPeriod" class="cup-period">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M8 2v4"></path>
                    <path d="M16 2v4"></path>
                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                    <path d="M3 10h18"></path>
                </svg>
                Periodo CUP
            </span>
        </div>

        <section class="cup-stats" aria-label="Indicadores principales">
            <article>
                <div>
                    <span>Total inscritos</span>
                    <strong id="metricInscritos">...</strong>
                    <small>Preinscripciones confirmadas</small>
                </div>
                <div class="cup-stat-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
            </article>
            <article>
                <div>
                    <span>Total aprobados</span>
                    <strong id="metricAprobados">...</strong>
                    <small>Promedio general igual o mayor a 60</small>
                </div>
                <div class="cup-stat-icon is-gold">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M9 11h6"></path>
                        <path d="M9 15h6"></path>
                        <path d="M9 7h1"></path>
                        <rect x="5" y="3" width="14" height="18" rx="2"></rect>
                    </svg>
                </div>
            </article>
            <article>
                <div>
                    <span>Total reprobados</span>
                    <strong id="metricReprobados">...</strong>
                    <small>Promedio general menor a 60</small>
                </div>
                <div class="cup-stat-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="6" width="18" height="12" rx="2"></rect>
                        <path d="M3 10h18"></path>
                    </svg>
                </div>
            </article>
            <article>
                <div>
                    <span>Grupos habilitados</span>
                    <strong id="metricGruposHabilitados">...</strong>
                    <small>Grupos académicos activos</small>
                </div>
                <div class="cup-stat-icon is-gold">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M8 21h8"></path>
                        <path d="M12 17v4"></path>
                        <path d="M7 4h10v6a5 5 0 0 1-10 0V4Z"></path>
                        <path d="M5 7H3a3 3 0 0 0 3 3h1"></path>
                        <path d="M19 7h2a3 3 0 0 1-3 3h-1"></path>
                    </svg>
                </div>
            </article>
        </section>

        <section class="cup-workspace">
            <article class="cup-panel">
                <div class="cup-panel-head">
                    <h2>Preinscripciones recientes</h2>
                    <a href="/dashboard/preinscripciones">Ver todo
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M7 17 17 7"></path>
                            <path d="M7 7h10v10"></path>
                        </svg>
                    </a>
                </div>

                <div id="recentPreinscriptions" class="cup-list">
                    <div>
                        <span>
                            <strong>Cargando preinscripciones...</strong>
                            <small>Conectando con la base de datos</small>
                        </span>
                        <em>Pendiente</em>
                    </div>
                </div>
            </article>

            <aside class="cup-panel cup-quick">
                <h2>Accesos rapidos</h2>
                <a href="/dashboard/preinscripciones">
                    <span>
                        <strong>Registrar preinscripcion</strong>
                        <small>Nuevo postulante al CUP</small>
                    </span>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 17 17 7"></path>
                        <path d="M7 7h10v10"></path>
                    </svg>
                </a>
                <a href="/dashboard/usuarios">
                    <span>
                        <strong>Gestionar usuarios</strong>
                        <small>Cuentas, perfiles y roles</small>
                    </span>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 17 17 7"></path>
                        <path d="M7 7h10v10"></path>
                    </svg>
                </a>
                <a href="/dashboard/pagos">
                    <span>
                        <strong>Registrar pagos</strong>
                        <small>Matricula unica de 700 Bs.</small>
                    </span>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 17 17 7"></path>
                        <path d="M7 7h10v10"></path>
                    </svg>
                </a>
                <a href="/dashboard/habilitacion">
                    <span>
                        <strong>Publicar resultados</strong>
                        <small>Habilitacion final</small>
                    </span>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 17 17 7"></path>
                        <path d="M7 7h10v10"></path>
                    </svg>
                </a>
                <a href="/dashboard/requisitos">
                    <span>
                        <strong>Validar requisitos</strong>
                        <small>Documentos en ventanilla</small>
                    </span>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 17 17 7"></path>
                        <path d="M7 7h10v10"></path>
                    </svg>
                </a>
            </aside>
        </section>
    </section>
</main>
@endsection
