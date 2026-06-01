@php
    $active = $active ?? 'dashboard';
    $groups = [
        'General' => [
            ['key' => 'dashboard', 'href' => '/dashboard', 'label' => 'Dashboard'],
        ],
        'Seguridad' => [
            ['key' => 'usuarios', 'href' => '/dashboard/usuarios', 'label' => 'Usuarios'],
            ['key' => 'roles', 'href' => '/dashboard/roles-permisos', 'label' => 'Roles y permisos'],
            ['key' => 'bitacora', 'href' => '#', 'label' => 'Bitacora'],
            ['key' => 'password', 'href' => '/dashboard/password', 'label' => 'Contrasenas'],
        ],
        'Admisiones' => [
            ['key' => 'preinscripciones', 'href' => '/dashboard/preinscripciones', 'label' => 'Preinscripciones'],
            ['key' => 'requisitos', 'href' => '/dashboard/requisitos', 'label' => 'Requisitos fisicos'],
            ['key' => 'pagos', 'href' => '/dashboard/pagos', 'label' => 'Pago de matricula'],
            ['key' => 'habilitacion', 'href' => '/dashboard/habilitacion', 'label' => 'Habilitacion'],
        ],
        'Cuenta' => [
            ['key' => 'perfil', 'href' => '/dashboard/perfil', 'label' => 'Perfil'],
        ],
    ];
@endphp

<aside class="portal-sidebar">
    <a class="portal-brand" href="/">
        <img src="/assets/brand/ficct-escudo.png" alt="Escudo FICCT UAGRM">
        <span>
            <strong>CUP · UAGRM</strong>
            <small>Curso Preuniversitario</small>
        </span>
    </a>

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
</aside>
