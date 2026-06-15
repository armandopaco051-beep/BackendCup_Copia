@php
    $active = $active ?? 'dashboard';
    $tipo = auth()->user()?->tipo ?? 'administrativo';

    $groups = match ($tipo) {
        'docente' => [
            'Docente' => [
                ['key' => 'docente-dashboard', 'href' => '/dashboard/docente', 'label' => 'Panel docente'],
                ['key' => 'docente-horario', 'href' => '/dashboard/docente#mi-horario-docente', 'label' => 'Mi horario'],
                ['key' => 'calificaciones', 'href' => '/dashboard/calificaciones', 'label' => 'Registrar calificaciones'],
                ['key' => 'asistencias', 'href' => '/dashboard/asistencias', 'label' => 'Registrar asistencia'],
            ],
            'Cuenta' => [
                ['key' => 'perfil', 'href' => '/dashboard/perfil', 'label' => 'Perfil'],
            ],
        ],
        'postulante' => [
            'Postulante' => [
                ['key' => 'postulante-dashboard', 'href' => '/dashboard/postulante', 'label' => 'Mi panel'],
            ],
            'Proceso CUP' => [
                ['key' => 'postulante-preinscripcion', 'href' => '/dashboard/postulante#mi-preinscripcion', 'label' => 'Mi preinscripcion'],
                ['key' => 'postulante-requisitos', 'href' => '/dashboard/postulante#mis-requisitos', 'label' => 'Mis requisitos'],
                ['key' => 'postulante-pago', 'href' => '/dashboard/postulante#mi-pago', 'label' => 'Mi pago'],
                ['key' => 'postulante-grupo', 'href' => '/dashboard/postulante#mi-grupo', 'label' => 'Mi grupo y horario'],
                ['key' => 'postulante-notas', 'href' => '/dashboard/postulante#mis-calificaciones', 'label' => 'Mis calificaciones'],
            ],
            'Cuenta' => [
                ['key' => 'perfil', 'href' => '/dashboard/perfil', 'label' => 'Perfil'],
            ],
        ],
        default => [
            'General' => [
                ['key' => 'dashboard', 'href' => '/dashboard', 'label' => 'Dashboard'],
            ],
            'Seguridad' => [
                ['key' => 'usuarios', 'href' => '/dashboard/usuarios', 'label' => 'Usuarios'],
                ['key' => 'roles', 'href' => '/dashboard/roles-permisos', 'label' => 'Roles y permisos'],
                ['key' => 'bitacora', 'href' => '/dashboard/bitacora', 'label' => 'Bitacora'],
                ['key' => 'password', 'href' => '/dashboard/password', 'label' => 'Contrasenas'],
            ],
            'Admisiones' => [
                ['key' => 'periodo', 'href' => '/dashboard/periodo-academico', 'label' => 'Periodo academico'],
                ['key' => 'preinscripciones', 'href' => '/dashboard/preinscripciones', 'label' => 'Preinscripciones'],
                ['key' => 'requisitos', 'href' => '/dashboard/requisitos', 'label' => 'Requisitos fisicos'],
                ['key' => 'pagos', 'href' => '/dashboard/pagos', 'label' => 'Pago de matricula'],
                ['key' => 'habilitacion', 'href' => '/dashboard/habilitacion', 'label' => 'Habilitacion'],
            ],
            'Academico' => [
                ['key' => 'catalogos', 'href' => '/dashboard/catalogos-academicos', 'label' => 'Carreras y materias'],
                ['key' => 'docentes-materias', 'href' => '/dashboard/docentes-materias', 'label' => 'Asignar materias a docentes'],
                ['key' => 'docentes-horarios', 'href' => '/dashboard/docentes-horarios', 'label' => 'Asignar docentes a horarios'],
                ['key' => 'distribucion', 'href' => '/dashboard/distribucion-grupos', 'label' => 'Distribucion grupos'],
                ['key' => 'horarios-grupos', 'href' => '/dashboard/horarios-grupos', 'label' => 'Horarios de grupos'],
                ['key' => 'postulantes-grupos', 'href' => '/dashboard/postulantes-grupos', 'label' => 'Asignar estudiantes a grupos'],
                ['key' => 'asignacion-carreras', 'href' => '/dashboard/asignacion-carreras', 'label' => 'Asignacion carreras'],
                ['key' => 'aulas', 'href' => '/dashboard/aulas', 'label' => 'Gestion de aulas'],
                ['key' => 'calificaciones', 'href' => '/dashboard/calificaciones', 'label' => 'Calificaciones'],
                ['key' => 'ponderaciones-notas', 'href' => '/dashboard/ponderaciones-notas', 'label' => 'Ponderaciones notas'],
                ['key' => 'asistencias', 'href' => '/dashboard/asistencias', 'label' => 'Asistencia'],
            ],
            'Reportes' => [
                ['key' => 'reportes', 'href' => '/dashboard/reportes', 'label' => 'Reportes dinamicos'],
            ],
            'Cuenta' => [
                ['key' => 'perfil', 'href' => '/dashboard/perfil', 'label' => 'Perfil'],
            ],
        ],
    };
@endphp

<button class="portal-sidebar-scrim" type="button" data-portal-sidebar-overlay aria-label="Cerrar menu"></button>

<button class="portal-menu-toggle" type="button" data-portal-sidebar-toggle aria-label="Abrir menu" aria-expanded="true">
    <span></span>
</button>

<aside class="portal-sidebar">
    <div class="portal-sidebar-head">
        <a class="portal-brand" href="/">
            <img src="/assets/brand/ficct-escudo.png" alt="Escudo FICCT UAGRM">
            <span>
                <strong>CUP - UAGRM</strong>
                <small>Curso Preuniversitario</small>
            </span>
        </a>

        <button class="portal-sidebar-close" type="button" data-portal-sidebar-close aria-label="Cerrar menu">
            <span></span>
        </button>
    </div>

    <nav class="portal-nav" aria-label="Modulos del portal">
        @foreach ($groups as $group => $items)
            <div class="portal-nav-group">
                <span>{{ $group }}</span>
                @foreach ($items as $item)
                    <a class="{{ $active === $item['key'] ? 'is-active' : '' }} {{ $item['href'] === '#' ? 'is-disabled' : '' }}"
                        href="{{ $item['href'] }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="portal-sidebar-footer">
        <a class="{{ $active === 'perfil' ? 'is-active' : '' }}" href="/dashboard/perfil">Perfil</a>
        <button type="button" data-logout>Cerrar sesion</button>
    </div>
</aside>
