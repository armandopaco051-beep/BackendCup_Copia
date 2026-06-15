@extends('layouts.app')

@section('title', 'Ponderaciones de notas | FICCT')

@section('content')
<main class="portal-shell" data-page="ponderaciones-notas">
    @include('dashboard.partials.sidebar', ['active' => 'ponderaciones-notas'])

    <section class="portal-main">
        <header class="users-header">
            <div>
                <span class="section-kicker">Academico</span>
                <h1>Configurar ponderaciones de notas</h1>
                <p>CU-31: define los porcentajes usados para calcular el promedio final.</p>
                <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
            </div>
            <button class="secondary-action" type="button" data-load-grade-weights>Actualizar</button>
        </header>

        <section class="teacher-subject-layout">
            <article class="module-card form-panel">
                <div class="module-head">
                    <span>CU-31</span>
                    <div>
                        <h2>Nueva ponderacion</h2>
                        <p>La suma de nota 1, nota 2 y nota 3 debe ser 100%.</p>
                    </div>
                </div>

                <form id="gradeWeightForm" class="portal-form">
                    <label>Nombre<input name="nombre" maxlength="100" value="Ponderacion CUP"></label>
                    <div class="form-grid">
                        <label>Nota 1 (%)<input name="nota1_porcentaje" type="number" min="0" max="100" step="0.01" value="30" required></label>
                        <label>Nota 2 (%)<input name="nota2_porcentaje" type="number" min="0" max="100" step="0.01" value="30" required></label>
                        <label>Nota 3 (%)<input name="nota3_porcentaje" type="number" min="0" max="100" step="0.01" value="40" required></label>
                    </div>
                    <label class="checkbox-line">
                        <input name="recalcular" type="checkbox" value="1" checked>
                        <span>Recalcular calificaciones existentes al guardar</span>
                    </label>
                    <div class="distribution-actions">
                        <button class="primary-action" type="submit"><span>Guardar ponderacion</span></button>
                        <button class="secondary-action" type="button" data-recalculate-grades>Recalcular ahora</button>
                    </div>
                    <p id="gradeWeightOutput" class="module-note"></p>
                </form>
            </article>

            <article class="module-card teacher-subject-detail">
                <span class="section-kicker">Activa</span>
                <h2 id="gradeWeightActiveTitle">Cargando...</h2>
                <p id="gradeWeightActiveMeta">Nota 1: 30%, Nota 2: 30%, Nota 3: 40%.</p>
                <div id="gradeWeightActiveTags" class="teacher-subject-tags">
                    <span class="status-pill is-admitted">Total 100%</span>
                </div>
            </article>
        </section>

        <article class="module-card is-wide">
            <div class="users-list-head">
                <div>
                    <h2>Historial de ponderaciones</h2>
                    <p id="gradeWeightCount">Sin datos cargados</p>
                </div>
            </div>

            <div class="table-wrap users-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Nota 1</th>
                            <th>Nota 2</th>
                            <th>Nota 3</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody id="gradeWeightTable">
                        <tr><td colspan="7">Cargando ponderaciones...</td></tr>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>
@endsection
