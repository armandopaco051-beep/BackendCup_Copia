@extends('layouts.app')

@section('title', 'Requisitos fisicos | FICCT')

@section('content')
<main class="portal-shell" data-page="requisitos">
    @include('dashboard.partials.sidebar', ['active' => 'requisitos'])

    <section class="portal-main">
        @include('dashboard.partials.topbar', [
            'title' => 'Validar requisitos fisicos',
            'description' => 'CU-07: registra la entrega de CI, titulo y libretas del postulante.',
        ])

        <article class="module-card form-panel">
            <form id="requirementsForm" class="portal-form">
                <label>Usuario postulante<input name="username" required placeholder="postulante1"></label>
                <label class="checkbox-line"><input name="ci_entregado" type="checkbox"> CI entregado</label>
                <label class="checkbox-line"><input name="titulo_entregado" type="checkbox"> Titulo entregado</label>
                <label class="checkbox-line"><input name="libretas_entregadas" type="checkbox"> Libretas entregadas</label>
                <label>Validado por<input name="validado_por" placeholder="admin"></label>
                <label>Observacion<textarea name="observacion" rows="3" placeholder="Detalle de ventanilla"></textarea></label>
                <button class="primary-action" type="submit"><span>Guardar requisitos</span></button>
            </form>
            <pre id="requirementsOutput" class="module-output"></pre>
        </article>
    </section>
</main>
@endsection
