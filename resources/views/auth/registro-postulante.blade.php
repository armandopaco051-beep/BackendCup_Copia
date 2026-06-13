@extends('layouts.app')

@section('title', 'Registro de postulante | Portal de Admision FICCT')

@section('content')
<main class="auth-shell register-shell" data-page="registro-postulante">
    <section class="auth-visual" aria-label="Registro de postulante FICCT">
        <div class="auth-slide is-active" data-slide="0" style="background-image: url('/assets/login/edificio-uagrm.png')"></div>
        <div class="auth-slide" data-slide="1" style="background-image: url('/assets/login/graduacion.png')"></div>
        <div class="auth-slide" data-slide="2" style="background-image: url('/assets/login/biblioteca.png')"></div>
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
            <p id="slideEyebrow">Preinscripcion CUP</p>
            <h1 id="slideTitle">Inicia tu postulacion</h1>
            <span id="slideText">Registra tus datos para iniciar el proceso de admision al Curso Preuniversitario.</span>
        </div>

        <div class="auth-dots" role="tablist" aria-label="Imagenes destacadas">
            <button class="is-active" type="button" data-dot="0" aria-label="Facultad"></button>
            <button type="button" data-dot="1" aria-label="Graduacion"></button>
            <button type="button" data-dot="2" aria-label="Biblioteca"></button>
        </div>
    </section>

    <section class="auth-panel register-panel">
        <form id="preinscriptionForm" class="login-form register-form">
            <div class="form-heading">
                <span class="section-kicker">Registro publico</span>
                <h2>Registro de postulante</h2>
                <p>Completa tus datos. Tu usuario y password temporal se enviaran por correo cuando seas habilitado.</p>
            </div>

            <section class="public-lookup">
                <div>
                    <strong>Consultar preinscripcion</strong>
                    <span>Ingresa tu carnet para ver o corregir una preinscripcion ya pagada.</span>
                </div>
                <div id="publicLookupForm" class="public-lookup-form">
                    <input id="publicLookupCi" type="search" placeholder="Carnet de identidad">
                    <button class="secondary-action" type="button" data-public-lookup>Buscar</button>
                </div>
                <p id="publicLookupOutput" class="form-alert register-output"></p>
            </section>

            <div class="register-grid">
                <label class="field-group">
                    <span>Correo</span>
                    <div class="field-control">
                        <input name="correo" type="email" required maxlength="100" placeholder="correo@email.com">
                    </div>
                </label>

                <label class="field-group">
                    <span>CI</span>
                    <div class="field-control">
                        <input name="ci" required maxlength="100" pattern="[0-9A-Za-z-]{5,100}" placeholder="12345678">
                    </div>
                </label>

                <label class="field-group">
                    <span>Nombre completo</span>
                    <div class="field-control">
                        <input name="nombre" required maxlength="100" placeholder="Juan Perez">
                    </div>
                </label>

                <label class="field-group">
                    <span>Telefono</span>
                    <div class="field-control">
                        <input name="telefono" required maxlength="10" pattern="[0-9]{7,10}" placeholder="70000000">
                    </div>
                </label>

                <label class="field-group">
                    <span>Ciudad</span>
                    <div class="field-control">
                        <input name="ciudad" required maxlength="100" placeholder="Santa Cruz">
                    </div>
                </label>

                <label class="field-group">
                    <span>Colegio</span>
                    <div class="field-control">
                        <input name="colegio_procedencia" required placeholder="Colegio Nacional">
                    </div>
                </label>

                <label class="field-group register-grid-wide">
                    <span>Direccion</span>
                    <div class="field-control">
                        <input name="direccion" required placeholder="Av. Siempre Viva">
                    </div>
                </label>

                <label class="field-group">
                    <span>Fecha nacimiento</span>
                    <div class="field-control">
                        <input name="fecha_nacimiento" type="date" required>
                    </div>
                </label>

                <label class="field-group">
                    <span>Genero</span>
                    <div class="field-control">
                        <input name="genero" required maxlength="100" placeholder="Masculino">
                    </div>
                </label>

                <label class="field-group">
                    <span>Cod. titulo bachiller</span>
                    <div class="field-control">
                        <input name="cod_titulo_bachiller" required placeholder="TIT-001">
                    </div>
                </label>

                <label class="field-group">
                    <span>Primera carrera</span>
                    <div class="field-control">
                        <select name="carrera_principal" id="careerFirstSelect" required>
                            <option value="">Cargando carreras...</option>
                        </select>
                    </div>
                </label>

                <label class="field-group">
                    <span>Segunda carrera</span>
                    <div class="field-control">
                        <select name="carrera_secundaria" id="careerSecondSelect">
                            <option value="">Opcional</option>
                        </select>
                    </div>
                </label>
            </div>

            <p id="preinscriptionOutput" class="form-alert register-output"></p>

            <button class="primary-action" type="submit">
                <span>Registrar preinscripcion</span>
            </button>

            <div class="form-footer">
                <span>Ya tienes usuario?</span>
                <a href="/login">Inicia sesion</a>
            </div>
        </form>

        <section id="publicPaymentGateway" class="public-payment" hidden>
            <div class="public-payment-summary">
                <span class="section-kicker">Portal de pagos</span>
                <h2>Matricula Academica</h2>
                <p>Complete el pago de matricula para continuar con su proceso de admision. El pago se procesa de forma segura mediante Stripe.</p>

                <article class="public-payment-ticket">
                    <div>
                        <span>Concepto</span>
                        <strong>Matricula CUP</strong>
                    </div>
                    <div>
                        <span>ID tramite</span>
                        <strong id="publicPaymentFolio">PRE-000000</strong>
                    </div>
                    <footer>
                        <span>Total a pagar</span>
                        <strong id="publicPaymentAmount">700.00 Bs</strong>
                    </footer>
                </article>

                <small>Pago protegido con cifrado SSL y validacion bancaria mediante Stripe.</small>
            </div>

            <form id="publicPaymentForm" class="public-payment-card">
                <div class="public-payment-card-head">Pago seguro via Stripe</div>

                <label class="stripe-holder-field">Titular de la tarjeta
                    <input id="cardholderName" name="cardholder_name" required placeholder="Nombre como aparece en la tarjeta">
                </label>

                <div class="stripe-payment-box">
                    <div id="payment-element" class="stripe-payment-element"></div>
                </div>

                <p id="publicPaymentOutput" class="form-alert" hidden></p>

                <a id="publicPaymentDownload" class="secondary-action public-payment-download" href="#" target="_blank" rel="noopener" hidden>
                    Descargar formulario
                </a>

                <button id="publicPaymentButton" class="primary-action" type="submit">
                    <span>Pagar 700.00 Bs</span>
                </button>

                <small>Su informacion esta encriptada y protegida bajo estandares bancarios.</small>
            </form>
        </section>
    </section>
</main>
@endsection
