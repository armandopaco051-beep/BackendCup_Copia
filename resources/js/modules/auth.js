const authSlides = [
    {
        eyebrow: 'Inscribete en linea, simple y seguro',
        title: 'Construye tu camino',
        text: 'Gestiona tu postulacion academica desde un portal institucional claro y confiable.',
    },
    {
        eyebrow: 'Postulate a una carrera que cambiara tu vida',
        title: 'Conocimiento que transforma',
        text: 'Accede a una experiencia academica orientada a ciencia, tecnologia e innovacion.',
    },
    {
        eyebrow: 'Universidad Autonoma Gabriel Rene Moreno',
        title: 'Bienvenido a FICCT',
        text: 'Inicia tu proceso de admision en una facultad orientada a tecnologia, investigacion e innovacion.',
    },
];

const registerSlides = [
    {
        eyebrow: 'Preinscripcion CUP en linea',
        title: 'Comienza tu admision',
        text: 'Registra tus datos personales y elige hasta dos carreras habilitadas por la facultad.',
    },
    {
        eyebrow: 'Universidad Autonoma Gabriel Rene Moreno',
        title: 'Postula a FICCT',
        text: 'Tu solicitud quedara pendiente hasta la validacion de requisitos fisicos, pago y habilitacion final.',
    },
    {
        eyebrow: 'Proceso institucional seguro',
        title: 'Recibe tu acceso',
        text: 'Cuando tu postulacion sea habilitada, el sistema podra generar tus credenciales de postulante.',
    },
];

const qs = (selector) => document.querySelector(selector);
const qsa = (selector) => Array.from(document.querySelectorAll(selector));

export function initAuth() {
    initAuthCarousel();
    initLoginForm();
}

function initAuthCarousel() {
    const slides = qsa('.auth-slide');
    const dots = qsa('.auth-dots button');
    const eyebrow = qs('#slideEyebrow');
    const title = qs('#slideTitle');
    const text = qs('#slideText');
    const slideContent = document.querySelector('[data-page="registro-postulante"]') ? registerSlides : authSlides;

    if (!slides.length || !dots.length || !eyebrow || !title || !text) {
        return;
    }

    let current = 0;
    let timer;

    const setSlide = (index) => {
        current = index;

        slides.forEach((slide, slideIndex) => {
            slide.classList.toggle('is-active', slideIndex === current);
        });

        dots.forEach((dot, dotIndex) => {
            dot.classList.toggle('is-active', dotIndex === current);
        });

        eyebrow.textContent = slideContent[current].eyebrow;
        title.textContent = slideContent[current].title;
        text.textContent = slideContent[current].text;
    };

    const startCarousel = () => {
        timer = window.setInterval(() => {
            setSlide((current + 1) % slides.length);
        }, 5200);
    };

    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            window.clearInterval(timer);
            setSlide(Number(dot.dataset.dot));
            startCarousel();
        });
    });

    startCarousel();
}

function initLoginForm() {
    const form = qs('#loginForm');
    const button = qs('#loginButton');
    const password = qs('#password');
    const togglePassword = qs('[data-toggle-password]');

    if (!form || !button || !password) {
        return;
    }

    togglePassword?.addEventListener('click', () => {
        const hidden = password.type === 'password';
        password.type = hidden ? 'text' : 'password';
        togglePassword.textContent = hidden ? 'Ocultar' : 'Mostrar';
        togglePassword.setAttribute('aria-label', hidden ? 'Ocultar contrasena' : 'Mostrar contrasena');
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const username = qs('#username')?.value.trim() || '';
        const passwordValue = password.value.trim();

        if (!validateLogin(username, passwordValue)) {
            return;
        }

        setLoginButton(button, true);

        try {
            const response = await fetch('/api/auth/login', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ username, password: passwordValue }),
            });

            const data = await parseJsonResponse(response);

            if (!response.ok) {
                showValidationErrors(data.errors);
                showLoginAlert(data.message || 'No se pudo iniciar sesion. Verifica tus credenciales.');
                return;
            }

            showLoginAlert('Sesion iniciada correctamente. Redirigiendo...', 'success');
            window.setTimeout(() => {
                window.location.href = '/dashboard';
            }, 450);
        } catch {
            showLoginAlert('No se pudo conectar con el servidor. Revisa que Laravel este ejecutandose.');
        } finally {
            setLoginButton(button, false);
        }
    });
}

function validateLogin(username, password) {
    let valid = true;

    setFieldError('username', '');
    setFieldError('password', '');
    showLoginAlert('');

    if (!username) {
        setFieldError('username', 'El usuario es obligatorio.');
        valid = false;
    }

    if (!password) {
        setFieldError('password', 'La contrasena es obligatoria.');
        valid = false;
    } else if (password.length < 6) {
        setFieldError('password', 'La contrasena debe tener al menos 6 caracteres.');
        valid = false;
    }

    return valid;
}

function setFieldError(field, message) {
    const target = document.querySelector(`[data-error-for="${field}"]`);

    if (target) {
        target.textContent = message || '';
    }
}

function showValidationErrors(errors = {}) {
    Object.entries(errors).forEach(([field, message]) => {
        setFieldError(field, Array.isArray(message) ? message[0] : String(message));
    });
}

function showLoginAlert(message, type = 'error') {
    const alert = qs('#loginAlert');

    if (!alert) {
        return;
    }

    alert.hidden = !message;
    alert.textContent = message || '';
    alert.style.background = type === 'success' ? 'var(--success-bg)' : 'var(--danger-bg)';
    alert.style.color = type === 'success' ? 'var(--success)' : 'var(--danger)';
    alert.style.borderColor = type === 'success' ? '#bcebd0' : '#ffd0cc';
}

async function parseJsonResponse(response) {
    const text = await response.text();

    if (!text) {
        return {};
    }

    try {
        return JSON.parse(text);
    } catch {
        return {
            message: response.ok
                ? 'La respuesta del servidor no esta en formato JSON.'
                : 'El servidor respondio con un error. Revisa que Laravel este ejecutandose.',
        };
    }
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function setLoginButton(button, loading) {
    button.disabled = loading;

    const label = button.querySelector('span');

    if (label) {
        label.textContent = loading ? 'Validando...' : 'Ingresar';
    }
}
