@extends('layouts.app')

@section('title', 'Restablecer contrasena | FICCT')

@section('content')
<main class="portal-shell" data-page="password">
    @include('dashboard.partials.sidebar', ['active' => 'password'])

    <section class="portal-main">
        @include('dashboard.partials.topbar', [
            'title' => 'Restablecer contrasena',
            'description' => 'CU-05: actualiza la contrasena de una cuenta registrada.',
        ])

        <article class="module-card form-panel">
            <form id="resetPasswordForm" class="portal-form">
                <label>Usuario<input name="username" required maxlength="500" pattern="[A-Za-z0-9_.-]{3,500}" placeholder="docente1"></label>
                <label>Nueva contrasena<input name="password" type="password" required minlength="6"></label>
                <label>Confirmacion<input name="password_confirmation" type="password" required minlength="6"></label>
                <button class="primary-action" type="submit"><span>Restablecer</span></button>
            </form>
        </article>
    </section>
</main>
@endsection
