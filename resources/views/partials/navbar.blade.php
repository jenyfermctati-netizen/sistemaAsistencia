<header class="navbar">
    <div class="navbar__left">
        <button type="button" id="sidebarToggle" class="navbar__menu-button" aria-label="Mostrar u ocultar menú"
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
            <div class="empresa-nav">
                <a href="{{ route('empresas.index') }}">{{ app(\App\Tenancy\EmpresaActual::class)->empresa()?->nombre ?? 'Mis empresas' }}</a>
                @if(auth()->user()->rol?->nombre === 'ADMINISTRADOR')
                    <a href="{{ route('empresas.usuarios') }}">Administrar accesos</a>
                @endif
            </div>
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
                            <dd class="profile-phone-value">
                                <span>{{ $trabajadorNavbar?->telefono ?: 'No registrado' }}</span>
                                @if($trabajadorNavbar && $trabajadorNavbar->estado === 'ACTIVO')
                                    <button type="button" class="profile-phone-edit" aria-label="Editar teléfono" title="Editar teléfono" aria-expanded="false" aria-controls="profilePhoneForm" onclick="const form = document.getElementById('profilePhoneForm'); form.hidden = !form.hidden; this.setAttribute('aria-expanded', String(!form.hidden)); if (!form.hidden) document.getElementById('profilePhone').focus();"><x-ui-icon name="edit" /></button>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if($trabajadorNavbar && $trabajadorNavbar->estado === 'ACTIVO')
                        <form method="POST" action="{{ route('perfil.telefono.update') }}" class="profile-phone-form" id="profilePhoneForm" hidden>
                            @csrf
                            @method('PATCH')
                            <label for="profilePhone">Editar teléfono</label>
                            <div>
                                <input id="profilePhone" name="telefono" type="tel" inputmode="tel" autocomplete="tel"
                                    value="{{ old('telefono', $trabajadorNavbar->telefono) }}" placeholder="Ej. 987654321"
                                    pattern="\+?[0-9]{7,15}" maxlength="16" required aria-describedby="profilePhoneHelp">
                                <button type="submit" aria-label="Guardar teléfono" title="Guardar teléfono"><x-ui-icon name="check" /></button>
                            </div>
                            <small id="profilePhoneHelp">Solo dígitos; puedes incluir + y el código de país.</small>
                        </form>
                    @endif

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
