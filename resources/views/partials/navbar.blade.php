<header class="navbar">
    <div class="navbar__left">
        <button type="button" id="sidebarToggle" class="navbar__menu-button" aria-label="Abrir menú principal"
            aria-controls="sidebar" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 7h16M4 12h16M4 17h16" />
            </svg>
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
            @php
                $usuarioNavbar = auth()->user();
                $trabajadorNavbar = $usuarioNavbar->trabajador;
                $rolNavbar = $usuarioNavbar->rol?->nombre ?? 'Sin rol';
            @endphp

            <div class="profile-menu" data-profile-menu>
                <button type="button" class="profile-menu__trigger" data-profile-menu-trigger
                    aria-label="Abrir menú de {{ $usuarioNavbar->name }}"
                    aria-expanded="false" aria-controls="profileMenuPanel">
                    <span class="navbar__user-info">
                        <strong>{{ $usuarioNavbar->name }}</strong>
                        <span>{{ $rolNavbar }}</span>
                    </span>

                    <span class="navbar__avatar" aria-hidden="true">
                        {{ strtoupper(substr($usuarioNavbar->name, 0, 1)) }}
                    </span>

                    <svg class="profile-menu__chevron" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="m6 8 4 4 4-4" />
                    </svg>
                </button>

                <div id="profileMenuPanel" class="profile-menu__panel" data-profile-menu-panel hidden>
                    <div class="profile-menu__header">
                        <span class="profile-menu__avatar" aria-hidden="true">
                            {{ strtoupper(substr($usuarioNavbar->name, 0, 1)) }}
                        </span>
                        <div>
                            <span class="profile-menu__eyebrow">Mi perfil</span>
                            <strong>{{ $usuarioNavbar->name }}</strong>
                        </div>
                    </div>

                    <dl class="profile-menu__details">
                        <div>
                            <dt>Rol</dt>
                            <dd>{{ $rolNavbar }}</dd>
                        </div>
                        <div>
                            <dt>Nombre</dt>
                            <dd>{{ $usuarioNavbar->name }}</dd>
                        </div>
                        <div>
                            <dt>DNI</dt>
                            <dd>{{ $trabajadorNavbar?->dni ?: 'No registrado' }}</dd>
                        </div>
                        <div>
                            <dt>Celular</dt>
                            <dd>{{ $trabajadorNavbar?->telefono ?: 'No registrado' }}</dd>
                        </div>
                    </dl>

                    <div class="profile-menu__actions">
                        <a href="{{ route('password.edit') }}" class="profile-menu__action">
                            <span class="profile-menu__action-icon" aria-hidden="true"><x-ui-icon name="lock" /></span>
                            <span>Cambiar contraseña</span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="profile-menu__action profile-menu__action--logout">
                                <span class="profile-menu__action-icon" aria-hidden="true"><x-ui-icon name="logout" /></span>
                                <span>Cerrar sesión</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endauth
    </div>
</header>
