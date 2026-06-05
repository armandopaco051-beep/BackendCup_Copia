<header class="portal-topbar">
    <div>
        <span class="section-kicker">{{ $kicker ?? 'Sistema de admision UAGRM' }}</span>
        <h1>{{ $title }}</h1>
        <p>{{ $description }}</p>
        <small id="dashboardUser" class="session-chip">Cargando sesion...</small>
    </div>
    <button class="secondary-action" type="button" data-logout>Cerrar sesion</button>
</header>
