@extends('layouts.app')

@section('title', 'Portal de Admision | UAGRM FICCT')

@section('content')
<main class="landing-page" data-page="landing">
    <nav class="landing-nav">
        <a class="landing-logo" href="/">
            <img src="/assets/brand/ficct-escudo.png" alt="Escudo FICCT UAGRM">
            <span>
                <strong>FICCT</strong>
                <small>UAGRM | Admision</small>
            </span>
        </a>
        <div class="landing-links" aria-label="Navegacion principal">
            <a href="#proceso">Proceso</a>
            <a href="#carreras">Carreras</a>
            <a href="#requisitos">Requisitos</a>
            <a href="/login">Ingresar</a>
        </div>
    </nav>

    <section class="landing-hero">
        <div class="landing-hero-media" aria-hidden="true">
            <img src="/assets/login/edificio-uagrm.png" alt="">
        </div>
        <div class="landing-hero-content">
            <span class="section-kicker">Universidad Autonoma Gabriel Rene Moreno</span>
            <h1>Portal de admision para la Facultad FICCT</h1>
            <p>
                Gestiona tu preinscripcion, validacion documental, pago de matricula
                y habilitacion academica en un proceso institucional ordenado.
            </p>
            <div class="landing-actions">
                <a class="landing-primary" href="/login">Ingresar al portal</a>
                <a class="landing-secondary" href="#proceso">Ver proceso</a>
            </div>
        </div>
    </section>

    <section id="proceso" class="landing-section">
        <div class="section-head">
            <span class="section-kicker">Admision CUP</span>
            <h2>Un flujo claro para el postulante</h2>
            <p>El sistema acompana cada etapa desde el registro inicial hasta la habilitacion final.</p>
        </div>
        <div class="process-grid">
            <article>
                <span>01</span>
                <h3>Preinscripcion</h3>
                <p>El bachiller registra sus datos personales y selecciona la carrera de interes.</p>
            </article>
            <article>
                <span>02</span>
                <h3>Requisitos fisicos</h3>
                <p>Ventanilla valida CI, titulo de bachiller y libretas obligatorias.</p>
            </article>
            <article>
                <span>03</span>
                <h3>Pago de matricula</h3>
                <p>Se registra el pago unico de 700 Bs. mediante integracion con Stripe.</p>
            </article>
            <article>
                <span>04</span>
                <h3>Habilitacion</h3>
                <p>El sistema confirma que requisitos y pago esten completos para habilitar al postulante.</p>
            </article>
        </div>
    </section>

    <section id="carreras" class="landing-band">
        <div>
            <span class="section-kicker">Facultad FICCT</span>
            <h2>Formacion orientada a tecnologia</h2>
            <p>
                La facultad impulsa la formacion en computacion, sistemas,
                redes, telecomunicaciones e innovacion aplicada.
            </p>
        </div>
        <div class="career-list">
            <span>Ingenieria Informatica</span>
            <span>Ingenieria en Sistemas</span>
            <span>Redes y Telecomunicaciones</span>
            <span>Ciencias de la Computacion</span>
        </div>
    </section>

    <section id="requisitos" class="landing-section">
        <div class="section-head">
            <span class="section-kicker">Documentacion</span>
            <h2>Requisitos para continuar el proceso</h2>
        </div>
        <div class="requirements-list">
            <div>
                <strong>Cedula de identidad</strong>
                <span>Documento vigente del postulante.</span>
            </div>
            <div>
                <strong>Titulo de bachiller</strong>
                <span>Registro o codigo del titulo presentado.</span>
            </div>
            <div>
                <strong>Libretas escolares</strong>
                <span>Documentacion fisica obligatoria para ventanilla.</span>
            </div>
            <div>
                <strong>Pago de matricula</strong>
                <span>Monto unico de 700 Bs. validado por el sistema.</span>
            </div>
        </div>
    </section>
</main>
@endsection
