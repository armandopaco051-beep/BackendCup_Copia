@extends('layouts.app')

@section('title', 'Perfil | FICCT')

@section('content')
<main class="portal-shell" data-page="perfil">
    @include('dashboard.partials.sidebar', ['active' => 'perfil'])

    <section class="portal-main">
        @include('dashboard.partials.topbar', [
            'title' => 'Perfil de usuario',
            'description' => 'Informacion de la cuenta autenticada en el portal.',
        ])

        <article id="profileSummary" class="module-card profile-panel">
            <div>
                <span class="section-kicker">Cuenta actual</span>
                <h2 id="profileName">Cargando perfil...</h2>
                <p id="profileRole">Esperando respuesta del servidor.</p>
            </div>
            <div id="profileFields" class="profile-fields"></div>
        </article>
    </section>
</main>
@endsection
