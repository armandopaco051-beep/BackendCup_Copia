@extends('layouts.app')

@section('title', 'Preinscripciones | FICCT')

@section('content')
<main class="portal-shell" data-page="preinscripciones">
    @include('dashboard.partials.sidebar', ['active' => 'preinscripciones'])

    <section class="portal-main">
        @include('dashboard.partials.topbar', [
            'title' => 'Registrar preinscripcion',
            'description' => 'CU-06: captura los datos personales del bachiller para iniciar admision.',
        ])

        <article class="module-card is-wide">
            <form id="preinscriptionForm" class="portal-form">
                <div class="form-grid">
                    <label>Usuario<input name="username" required placeholder="postulante1"></label>
                    <label>Contrasena<input name="password" type="password" required minlength="6" placeholder="123456"></label>
                    <label>Correo<input name="correo" type="email" required placeholder="correo@email.com"></label>
                    <label>CI<input name="ci" required placeholder="12345678"></label>
                    <label>Nombre<input name="nombre" required placeholder="Juan Perez"></label>
                    <label>Telefono<input name="telefono" required maxlength="10" placeholder="70000000"></label>
                    <label>Ciudad<input name="ciudad" required placeholder="Santa Cruz"></label>
                    <label>Colegio<input name="colegio_procedencia" required placeholder="Colegio Nacional"></label>
                    <label>Direccion<input name="direccion" required placeholder="Av. Siempre Viva"></label>
                    <label>Fecha nacimiento<input name="fecha_nacimiento" type="date" required></label>
                    <label>Genero<input name="genero" required placeholder="Masculino"></label>
                    <label>Cod. titulo<input name="cod_titulo_bachiller" required placeholder="TIT-001"></label>
                    <label>ID carrera<input name="id_carrera" placeholder="SIS"></label>
                    <label>Descripcion<input name="descripcion" placeholder="Primera opcion"></label>
                </div>
                <button class="primary-action" type="submit"><span>Registrar preinscripcion</span></button>
            </form>
            <pre id="preinscriptionOutput" class="module-output"></pre>
        </article>
    </section>
</main>
@endsection
