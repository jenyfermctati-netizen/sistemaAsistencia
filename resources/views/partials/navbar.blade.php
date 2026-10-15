<header class="navbar">
    <div class="navbar__left">
        <button type="button" id="sidebarToggle" class="navbar__menu-button">
            ☰
        </button>

        <div>
            <h1 class="navbar__title">
                @yield('page-title', 'Sistema de Asistencia')
            </h1>

            @hasSection('page-subtitle')
                <span class="navbar__subtitle">
                    @yield('page-subtitle')
                </span>
            @endif
        </div>
    </div>

    <div class="navbar__right">
        @auth
            <div class="navbar__user-info">
                <strong>{{ auth()->user()->name }}</strong>
                <span>{{ auth()->user()->rol?->nombre ?? 'Sin rol' }}</span>
            </div>

            <div class="navbar__avatar">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="button button--secondary navbar__logout-button" aria-label="Cerrar sesión">
                    <span class="navbar__logout-label">Cerrar sesión</span>
                    <span class="navbar__logout-icon" aria-hidden="true">↪</span>
                </button>
            </form>
        @endauth
    </div>
</header>
