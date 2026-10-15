
<aside class="sidebar" id="sidebar">

    @php
        $usuarioActual = auth()->user();

        $rolUsuario = strtoupper(
            trim($usuarioActual?->rol?->nombre ?? '')
        );

        $tipoVinculo = strtoupper(
            trim($usuarioActual?->trabajador?->tipo_vinculo ?? '')
        );

        $esAdministrador = $rolUsuario === 'ADMINISTRADOR';
        $esTrabajador = $rolUsuario === 'TRABAJADOR';
        $esGerente = $rolUsuario === 'GERENTE';

        $puedeVerVacaciones = $esAdministrador ||
            ($esTrabajador && $tipoVinculo === 'CONTRATADO');
    @endphp

    {{-- ENCABEZADO --}}
    <div class="sidebar__header">

        <div class="sidebar__logo">CA</div>

        <div class="sidebar__brand">
            <strong>Control</strong>
            <span>Sistema de asistencia</span>
        </div>

    </div>

    <nav class="sidebar__menu">

        {{-- ============================= --}}
        {{-- INICIO --}}
        {{-- ============================= --}}

        <div class="sidebar__section">

            <a href="{{ route('dashboard') }}"
               class="sidebar__item {{
                   request()->routeIs('dashboard')
                   ? 'active' : ''
               }}">

                <span class="sidebar__icon">IN</span>
                <span class="sidebar__label">Inicio</span>

            </a>

        </div>

        {{-- ============================= --}}
        {{-- ADMINISTRADOR --}}
        {{-- ============================= --}}

        @if($esAdministrador)

            <div class="sidebar__section">

                <div class="sidebar__title">
                    Administración
                </div>

                <a href="{{ route('areas.index') }}"
                   class="sidebar__item {{
                       request()->routeIs('areas.*')
                       ? 'active' : ''
                   }}">
                    <span class="sidebar__icon">AR</span>
                    <span class="sidebar__label">Áreas</span>
                </a>

                <a href="{{ route('trabajadores.index') }}"
                   class="sidebar__item {{
                       request()->routeIs('trabajadores.index')
                       ? 'active' : ''
                   }}">
                    <span class="sidebar__icon">TR</span>
                    <span class="sidebar__label">Trabajadores</span>
                </a>

                <a href="{{ route('trabajadores.horarios') }}"
                   class="sidebar__item {{
                       request()->routeIs('trabajadores.horarios*')
                       ? 'active' : ''
                   }}">
                    <span class="sidebar__icon">HO</span>
                    <span class="sidebar__label">Horarios</span>
                </a>

                <a href="{{ route('asignaciones-campo.index') }}"
                   class="sidebar__item {{
                       request()->routeIs('asignaciones-campo.*')
                       ? 'active' : ''
                   }}">
                    <span class="sidebar__icon">TC</span>
                    <span class="sidebar__label">Trabajo en campo</span>
                </a>

            </div>

            <div class="sidebar__section">

                <div class="sidebar__title">
                    Control de asistencia
                </div>

                <a href="{{ route('marcaciones.index') }}"
                   class="sidebar__item {{
                       request()->routeIs('marcaciones.*')
                       ? 'active' : ''
                   }}">
                    <span class="sidebar__icon">MA</span>
                    <span class="sidebar__label">Marcaciones</span>
                </a>

                <a href="{{ route('asistencias.index') }}"
                   class="sidebar__item {{
                       request()->routeIs('asistencias.*')
                       ? 'active' : ''
                   }}">
                    <span class="sidebar__icon">AS</span>
                    <span class="sidebar__label">Asistencias</span>
                </a>

                <a href="{{ route('feriados.index') }}"
                   class="sidebar__item {{
                       request()->routeIs('feriados.*')
                       ? 'active' : ''
                   }}">
                    <span class="sidebar__icon">FE</span>
                    <span class="sidebar__label">Feriados</span>
                </a>

            </div>

        @endif

        {{-- ============================= --}}
        {{-- GESTIÓN: ADMINISTRADOR --}}
        {{-- ============================= --}}

        @if($esAdministrador)

            <div class="sidebar__section">

                <div class="sidebar__title">
                    Gestión
                </div>

                <a href="{{ route('solicitudes.index') }}"
                   class="sidebar__item {{
                       request()->routeIs('solicitudes.*')
                       ? 'active' : ''
                   }}">
                    <span class="sidebar__icon">SO</span>
                    <span class="sidebar__label">Solicitudes</span>
                </a>

                @if($puedeVerVacaciones)

                    <a href="{{ route('vacaciones.index') }}"
                       class="sidebar__item {{
                           request()->routeIs('vacaciones.*')
                           ? 'active' : ''
                       }}">
                        <span class="sidebar__icon">VA</span>
                        <span class="sidebar__label">Vacaciones</span>
                    </a>

                @endif

                <a href="{{ route('reportesAvance.index') }}"
                   class="sidebar__item {{
                       request()->routeIs('reportesAvance.*')
                       ? 'active' : ''
                   }}">
                    <span class="sidebar__icon">RA</span>
                    <span class="sidebar__label">
                        Reportes de avance
                    </span>
                </a>

            </div>

        @endif

        {{-- ============================= --}}
        {{-- GESTIÓN: TRABAJADOR --}}
        {{-- ============================= --}}

        @if($esTrabajador)

            <div class="sidebar__section">

                <div class="sidebar__title">
                    Mi gestión
                </div>

                <a href="{{ route('solicitudes.index') }}"
                   class="sidebar__item {{
                       request()->routeIs('solicitudes.*')
                       ? 'active' : ''
                   }}">
                    <span class="sidebar__icon">SO</span>
                    <span class="sidebar__label">
                        Mis solicitudes
                    </span>
                </a>

                @if($puedeVerVacaciones)

                    <a href="{{ route('vacaciones.index') }}"
                       class="sidebar__item {{
                           request()->routeIs('vacaciones.*')
                           ? 'active' : ''
                       }}">
                        <span class="sidebar__icon">VA</span>
                        <span class="sidebar__label">
                            Mis vacaciones
                        </span>
                    </a>

                @endif

                <a href="{{ route('reportesAvance.index') }}"
                   class="sidebar__item {{
                       request()->routeIs('reportesAvance.*')
                       ? 'active' : ''
                   }}">
                    <span class="sidebar__icon">RA</span>
                    <span class="sidebar__label">
                        Mis reportes de avance
                    </span>
                </a>

            </div>

        @endif

        {{-- ============================= --}}
        {{-- GERENTE --}}
        {{-- ============================= --}}

        @if($esGerente)

            <div class="sidebar__section">

                <div class="sidebar__title">
                    Gerencia
                </div>

                @if(Route::has('gerencia.dashboard'))

                    <a href="{{ route('gerencia.dashboard') }}"
                       class="sidebar__item {{
                           request()->routeIs('gerencia.dashboard')
                           ? 'active' : ''
                       }}">
                        <span class="sidebar__icon">RG</span>
                        <span class="sidebar__label">
                            Resumen general
                        </span>
                    </a>

                @endif

            </div>

            <div class="sidebar__section">

                <div class="sidebar__title">
                    Supervisión
                </div>

                <a href="{{ route('asistencias.index') }}"
                   class="sidebar__item {{
                       request()->routeIs('asistencias.*')
                       ? 'active' : ''
                   }}">
                    <span class="sidebar__icon">AS</span>
                    <span class="sidebar__label">
                        Asistencias y horas
                    </span>
                </a>

                <a href="{{ route('reportesAvance.index', [
                    'pestana' => 'avances'
                ]) }}"
                   class="sidebar__item {{
                       request()->routeIs('reportesAvance.*')
                       && request('pestana', 'avances') === 'avances'
                       ? 'active' : ''
                   }}">
                    <span class="sidebar__icon">RA</span>
                    <span class="sidebar__label">
                        Reportes diarios
                    </span>
                </a>

                <a href="{{ route('solicitudes.index') }}"
                   class="sidebar__item {{
                       request()->routeIs('solicitudes.*')
                       ? 'active' : ''
                   }}">
                    <span class="sidebar__icon">SO</span>
                    <span class="sidebar__label">
                        Permisos y solicitudes
                    </span>
                </a>

                <a href="{{ route('reportesAvance.index', [
                    'pestana' => 'pendientes'
                ]) }}"
                   class="sidebar__item {{
                       request()->routeIs('reportesAvance.*')
                       && request('pestana') === 'pendientes'
                       ? 'active' : ''
                   }}">
                    <span class="sidebar__icon">AC</span>
                    <span class="sidebar__label">
                        Actividades y pendientes
                    </span>
                </a>

            </div>

        @endif

    </nav>

    {{-- ============================= --}}
    {{-- PIE DEL SIDEBAR --}}
    {{-- ============================= --}}

    <div class="sidebar__footer">

        <div class="sidebar__footer-icon">
            CA
        </div>

        <div>
            <strong>Control de Asistencia</strong>
            <span>Gestión interna</span>
        </div>

    </div>

</aside>

<div id="sidebarOverlay" class="sidebar-overlay"></div>
