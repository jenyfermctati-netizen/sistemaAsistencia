<aside class="sidebar" id="sidebar">

    <div class="sidebar__header">
        <div class="sidebar__logo">CA</div>
        <div class="sidebar__brand">
            <strong>Control</strong>
            <span>Sistema de asistencia</span>
        </div>
    </div>

    <nav class="sidebar__menu">

        <div class="sidebar__section">
            <a
                href="{{ route('dashboard') }}"
                class="sidebar__item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
            >
                <span class="sidebar__icon">IN</span>
                <span class="sidebar__label">Inicio</span>
            </a>
        </div>

        @auth
            @if(auth()->user()->rol?->nombre === 'ADMINISTRADOR')

                <div class="sidebar__section">
                    <div class="sidebar__title">Administración</div>

                    <a
                        href="{{ route('areas.index') }}"
                        class="sidebar__item {{ request()->routeIs('areas.*') ? 'active' : '' }}"
                    >
                        <span class="sidebar__icon">AR</span>
                        <span class="sidebar__label">Áreas</span>
                    </a>

                    <a
                        href="{{ route('trabajadores.index') }}"
                        class="sidebar__item {{ request()->routeIs('trabajadores.index') ? 'active' : '' }}"
                    >
                        <span class="sidebar__icon">TR</span>
                        <span class="sidebar__label">Trabajadores</span>
                    </a>

                    <a
                        href="{{ route('trabajadores.horarios') }}"
                        class="sidebar__item {{ request()->routeIs('trabajadores.horarios*') ? 'active' : '' }}"
                    >
                        <span class="sidebar__icon">HO</span>
                        <span class="sidebar__label">Horarios</span>
                    </a>

                    <a
                        href="{{ route('asignaciones-campo.index') }}"
                        class="sidebar__item {{ request()->routeIs('asignaciones-campo.*') ? 'active' : '' }}"
                    >
                        <span class="sidebar__icon">TC</span>
                        <span class="sidebar__label">Trabajo en campo</span>
                    </a>
                </div>

                <div class="sidebar__section">
                    <div class="sidebar__title">Control de asistencia</div>

                    <a
                        href="{{ route('marcaciones.index') }}"
                        class="sidebar__item {{ request()->routeIs('marcaciones.*') ? 'active' : '' }}"
                    >
                        <span class="sidebar__icon">MA</span>
                        <span class="sidebar__label">Marcaciones</span>
                    </a>

                    <a
                        href="{{ route('asistencias.index') }}"
                        class="sidebar__item {{ request()->routeIs('asistencias.*') ? 'active' : '' }}"
                    >
                        <span class="sidebar__icon">AS</span>
                        <span class="sidebar__label">Asistencias</span>
                    </a>

                    <a
                        href="{{ route('feriados.index') }}"
                        class="sidebar__item {{ request()->routeIs('feriados.*') ? 'active' : '' }}"
                    >
                        <span class="sidebar__icon">FE</span>
                        <span class="sidebar__label">Feriados</span>
                    </a>
                </div>

                <div class="sidebar__section">
                    <div class="sidebar__title">Gestión</div>

                    @if(Route::has('solicitudes.index'))
                        <a
                            href="{{ route('solicitudes.index') }}"
                            class="sidebar__item {{ request()->routeIs('solicitudes.*') ? 'active' : '' }}"
                        >
                            <span class="sidebar__icon">SO</span>
                            <span class="sidebar__label">Solicitudes</span>
                        </a>
                    @endif

                    @if(Route::has('vacaciones.index'))
                        <a
                            href="{{ route('vacaciones.index') }}"
                            class="sidebar__item {{ request()->routeIs('vacaciones.*') ? 'active' : '' }}"
                        >
                            <span class="sidebar__icon">VA</span>
                            <span class="sidebar__label">Vacaciones</span>
                        </a>
                    @endif

                    @if(Route::has('reportesAvance.index'))
                        <a
                            href="{{ route('reportesAvance.index') }}"
                            class="sidebar__item {{ request()->routeIs('reportesAvance.*') ? 'active' : '' }}"
                        >
                            <span class="sidebar__icon">RA</span>
                            <span class="sidebar__label">Reportes de avance</span>
                        </a>
                    @endif
                </div>

            @endif
        @endauth

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