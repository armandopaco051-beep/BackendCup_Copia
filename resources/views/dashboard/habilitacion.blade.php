@extends('layouts.app')

@section('title', 'Habilitacion | FICCT')

@section('content')
<main class="portal-shell" data-page="habilitacion">
    @include('dashboard.partials.sidebar', ['active' => 'habilitacion'])

    <section class="portal-main">
        @include('dashboard.partials.topbar', [
            'title' => 'Habilitar postulante',
            'description' => 'CU-09: confirma la habilitacion final despues de requisitos y pago.',
        ])

        <section class="two-column">
            <article class="module-card">
                <form id="enableApplicantForm" class="portal-form">
                    <label>Usuario postulante<input name="username" required placeholder="postulante1"></label>
                    <label>Observacion<textarea name="observacion" rows="3">Postulante habilitado tras validar requisitos y pago</textarea></label>
                    <button class="primary-action" type="submit"><span>Habilitar postulante</span></button>
                </form>
            </article>

            <article class="module-card">
                <form id="enableStatusForm" class="portal-form">
                    <label>Usuario<input name="username" required placeholder="postulante1"></label>
                    <button class="secondary-action" type="submit">Consultar habilitacion</button>
                </form>
            </article>
        </section>

        <pre id="enableOutput" class="module-output"></pre>
    </section>
</main>
@endsection
