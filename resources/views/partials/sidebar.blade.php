<aside class="sidebar" id="sidebar">
    @php
        $usuarioActual = auth()->user();
        $rolUsuario = strtoupper(trim($usuarioActual?->rol?->nombre ?? ''));
        $tipoVinculo = strtoupper(trim($usuarioActual?->trabajador?->tipo_vinculo ?? ''));
        $esAdministrador = $rolUsuario === 'ADMINISTRADOR';
        $esTrabajador = $rolUsuario === 'TRABAJADOR';
        $esGerente = $rolUsuario === 'GERENTE';
        $esEncargadoArea = $esTrabajador
            && (bool) $usuarioActual?->trabajador?->esEncargadoDeSuArea();
        $puedeVerVacaciones = $esAdministrador || ($esTrabajador && $tipoVinculo === 'CONTRATADO');
    @endphp

    <div class="sidebar__header">
        <div class="sidebar__logo">CA</div>
        <div class="sidebar__brand">
            <strong>Control</strong>
            <span>Sistema de asistencia</span>
        </div>
        <button type="button" class="sidebar__close" data-sidebar-close aria-label="Cerrar menú">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m6 6 12 12M18 6 6 18" />
            </svg>
        </button>
    </div>

    <nav class="sidebar__menu">
        <div class="sidebar__section">
            <a href="{{ route('dashboard') }}" title="Resumen principal"
                class="sidebar__item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                @if (request()->routeIs('dashboard')) aria-current="page" @endif>
                <span class="sidebar__icon"><x-nav-icon name="home" /></span>
                <span class="sidebar__label">Inicio</span>
            </a>
        </div>

        @if ($esAdministrador)
            <div class="sidebar__section">
                <div class="sidebar__title">Administración</div>

                <a href="{{ route('areas.index') }}"
                    class="sidebar__item {{ request()->routeIs('areas.*') ? 'active' : '' }}">
                    <span class="sidebar__icon"><x-nav-icon name="areas" /></span>
                    <span class="sidebar__label">Áreas</span>
                </a>

                <a href="{{ route('trabajadores.index') }}"
                    class="sidebar__item {{ request()->routeIs('trabajadores.index') ? 'active' : '' }}">
                    <span class="sidebar__icon"><x-nav-icon name="users" /></span>
                    <span class="sidebar__label">Personal</span>
                </a>

                <a href="{{ route('trabajadores.horarios') }}"
                    class="sidebar__item {{ request()->routeIs('trabajadores.horarios*') ? 'active' : '' }}">
                    <span class="sidebar__icon"><x-nav-icon name="clock" /></span>
                    <span class="sidebar__label">Horario</span>
                </a>

                <a href="{{ route('asignaciones-campo.index') }}"
                    class="sidebar__item {{ request()->routeIs('asignaciones-campo.*') ? 'active' : '' }}">
                    <span class="sidebar__icon"><x-nav-icon name="briefcase" /></span>
                    <span class="sidebar__label">Trabajo en campo</span>
                </a>
            </div>

            <div class="sidebar__section">
                <div class="sidebar__title">Control de asistencia</div>

                <a href="{{ route('biometrico.index') }}"
                    class="sidebar__item {{ request()->routeIs('biometrico.*') ? 'active' : '' }}">
                    <span class="sidebar__icon"><x-nav-icon name="fingerprint" /></span>
                    <span class="sidebar__label">Biométrico</span>
                </a>

                <a href="{{ route('marcaciones.index') }}"
                    class="sidebar__item {{ request()->routeIs('marcaciones.*') ? 'active' : '' }}">
                    <span class="sidebar__icon"><x-nav-icon name="markings" /></span>
                    <span class="sidebar__label">Marcaciones</span>
                </a>

                <a href="{{ route('asistencias.index') }}"
                    class="sidebar__item {{ request()->routeIs('asistencias.*') ? 'active' : '' }}">
                    <span class="sidebar__icon"><x-nav-icon name="attendance" /></span>
                    <span class="sidebar__label">Resumen diario</span>
                </a>

                <a href="{{ route('feriados.index') }}"
                    class="sidebar__item {{ request()->routeIs('feriados.*') ? 'active' : '' }}">
                    <span class="sidebar__icon"><x-nav-icon name="calendar" /></span>
                    <span class="sidebar__label">Feriados</span>
                </a>
            </div>
        @endif

        @if ($esAdministrador)
            <div class="sidebar__section">
                <div class="sidebar__title">Gestión</div>

                <a href="{{ route('solicitudes.index') }}"
                    class="sidebar__item {{ request()->routeIs('solicitudes.*') ? 'active' : '' }}">
                    <span class="sidebar__icon"><x-nav-icon name="requests" /></span>
                    <span class="sidebar__label">Solicitudes</span>
                </a>

                @if ($puedeVerVacaciones)
                    <a href="{{ route('vacaciones.index') }}"
                        class="sidebar__item {{ request()->routeIs('vacaciones.*') ? 'active' : '' }}">
                        <span class="sidebar__icon"><x-nav-icon name="vacations" /></span>
                        <span class="sidebar__label">Vacaciones</span>
                    </a>
                @endif

                <a href="{{ route('reportesAvance.index') }}"
                    class="sidebar__item {{ request()->routeIs('reportesAvance.*') ? 'active' : '' }}">
                    <span class="sidebar__icon"><x-nav-icon name="progress" /></span>
                    <span class="sidebar__label">Avances y pendientes</span>
                </a>

            </div>
        @endif

        @if ($esTrabajador)
            <div class="sidebar__section">
                <div class="sidebar__title">Mi gestión</div>

                <a href="{{ route('solicitudes.index') }}"
                    class="sidebar__item {{ request()->routeIs('solicitudes.*') ? 'active' : '' }}">
                    <span class="sidebar__icon"><x-nav-icon name="requests" /></span>
                    <span class="sidebar__label">Mis solicitudes</span>
                </a>

                @if ($puedeVerVacaciones)
                    <a href="{{ route('vacaciones.index') }}"
                        class="sidebar__item {{ request()->routeIs('vacaciones.*') ? 'active' : '' }}">
                        <span class="sidebar__icon"><x-nav-icon name="vacations" /></span>
                        <span class="sidebar__label">Mis vacaciones</span>
                    </a>
                @endif

                <a href="{{ route('reportesAvance.index') }}"
                    class="sidebar__item {{ request()->routeIs('reportesAvance.*') && request('vista') !== 'equipo' ? 'active' : '' }}">
                    <span class="sidebar__icon"><x-nav-icon name="progress" /></span>
                    <span class="sidebar__label">Mi avance</span>
                </a>

                @if ($esEncargadoArea)
                    <a href="{{ route('reportesAvance.index', ['vista' => 'equipo', 'pestana' => 'avances']) }}"
                        class="sidebar__item {{ request()->routeIs('reportesAvance.*') && request('vista') === 'equipo' ? 'active' : '' }}">
                        <span class="sidebar__icon"><x-nav-icon name="team" /></span>
                        <span class="sidebar__label">Mi equipo</span>
                    </a>

                @endif
            </div>
        @endif

        @if ($esGerente)
            <div class="sidebar__section">
                <div class="sidebar__title">Gerencia</div>

                @if (Route::has('gerencia.dashboard'))
                    <a href="{{ route('gerencia.dashboard') }}"
                        class="sidebar__item {{ request()->routeIs('gerencia.dashboard') ? 'active' : '' }}">
                        <span class="sidebar__icon"><x-nav-icon name="chart" /></span>
                        <span class="sidebar__label">Resumen general</span>
                    </a>
                @endif
            </div>

            <div class="sidebar__section">
                <div class="sidebar__title">Supervisión</div>

                <a href="{{ route('asistencias.index') }}"
                    class="sidebar__item {{ request()->routeIs('asistencias.*') ? 'active' : '' }}">
                    <span class="sidebar__icon"><x-nav-icon name="attendance" /></span>
                    <span class="sidebar__label">Asistencias</span>
                </a>

                <a href="{{ route('reportesAvance.index', ['pestana' => 'avances']) }}"
                    class="sidebar__item {{ request()->routeIs('reportesAvance.*') && request('pestana', 'avances') === 'avances' ? 'active' : '' }}">
                    <span class="sidebar__icon"><x-nav-icon name="progress" /></span>
                    <span class="sidebar__label">Avances</span>
                </a>

                <a href="{{ route('solicitudes.index') }}"
                    class="sidebar__item {{ request()->routeIs('solicitudes.*') ? 'active' : '' }}">
                    <span class="sidebar__icon"><x-nav-icon name="requests" /></span>
                    <span class="sidebar__label">Solicitudes</span>
                </a>

                <a href="{{ route('reportesAvance.index', ['pestana' => 'pendientes']) }}"
                    class="sidebar__item {{ request()->routeIs('reportesAvance.*') && request('pestana') === 'pendientes' ? 'active' : '' }}">
                    <span class="sidebar__icon"><x-nav-icon name="tasks" /></span>
                    <span class="sidebar__label">Pendientes</span>
                </a>

            </div>
        @endif
    </nav>

    <div class="sidebar__footer">
        <div class="sidebar__footer-icon">CA</div>
        <div>
            <strong>Control de Asistencia</strong>
            <span>Gestión interna</span>
        </div>
    </div>
</aside>

<div id="sidebarOverlay" class="sidebar-overlay"></div>
