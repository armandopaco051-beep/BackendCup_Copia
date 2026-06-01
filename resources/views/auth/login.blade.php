@extends('layouts.app')

@section('title', 'Iniciar sesion | Portal de Admision FICCT')

@section('content')
<main class="auth-shell" data-page="login">
    <section class="auth-visual" aria-label="Portal de admision FICCT">
        <div class="auth-slide is-active" data-slide="0" style="background-image: url('/assets/login/graduacion.png')"></div>
        <div class="auth-slide" data-slide="1" style="background-image: url('/assets/login/biblioteca.png')"></div>
        <div class="auth-slide" data-slide="2" style="background-image: url('/assets/login/edificio-uagrm.png')"></div>
        <div class="auth-overlay"></div>

        <div class="auth-brand">
            <div class="brand-mark" aria-hidden="true">
                <img src="/assets/brand/ficct-escudo.png" alt="">
            </div>
            <div>
                <strong>UAGRM | FICCT</strong>
                <span>Portal de Admision</span>
            </div>
        </div>

        <div class="auth-copy" aria-live="polite">
            <p id="slideEyebrow">Inscribete en linea, simple y seguro</p>
            <h1 id="slideTitle">Construye tu camino</h1>
            <span id="slideText">Gestiona tu postulacion academica desde un portal institucional claro y confiable.</span>
        </div>

        <div class="auth-dots" role="tablist" aria-label="Imagenes destacadas">
            <button class="is-active" type="button" data-dot="0" aria-label="Graduacion"></button>
            <button type="button" data-dot="1" aria-label="Biblioteca"></button>
            <button type="button" data-dot="2" aria-label="Facultad"></button>
        </div>
    </section>

    <section class="auth-panel">
        <form id="loginForm" class="login-form" novalidate>
            <div class="form-heading">
                <span class="section-kicker">Acceso institucional</span>
                <h2>Iniciar sesion</h2>
                <p>Ingresa al portal con tu usuario asignado por la facultad.</p>
            </div>

            <div id="loginAlert" class="form-alert" hidden></div>

            <label class="field-group" for="username">
                <span>Usuario</span>
                <div class="field-control">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20 21a8 8 0 0 0-16 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <circle cx="12" cy="7" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/>
                    </svg>
                    <input id="username" name="username" type="text" autocomplete="username" placeholder="Ej. admin" required>
                </div>
                <small data-error-for="username"></small>
            </label>

            <label class="field-group" for="password">
                <span>Contrasena</span>
                <div class="field-control">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="5" y="10" width="14" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M8 10V7a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Tu contrasena" required>
                    <button class="password-toggle" type="button" data-toggle-password aria-label="Mostrar contrasena">Mostrar</button>
                </div>
                <small data-error-for="password"></small>
            </label>

            <button id="loginButton" class="primary-action" type="submit">
                <span>Ingresar</span>
            </button>

            <div class="form-footer">
                <span>No tienes cuenta?</span>
                <a href="/login" aria-disabled="true">Registrate como postulante</a>
            </div>
        </form>
    </section>
</main>
@endsection
