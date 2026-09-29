<aside class="sidebar" id="sidebar">

    <div class="sidebar__header">

        <div class="sidebar__logo">
            CA
        </div>

        <div class="sidebar__brand">

            <strong>
                Control
            </strong>

            <span>
                Asistencia
            </span>

        </div>

    </div>


    <nav class="sidebar__menu">

        <a
            href="{{ route('dashboard') }}"
            class="sidebar__item
            {{ request()->routeIs('dashboard') ? 'active' : '' }}">

            <span class="sidebar__icon">
                IN
            </span>

            Inicio

        </a>


        @auth

            @if(auth()->user()->rol?->nombre === 'ADMINISTRADOR')

                <div class="sidebar__title">
                    Administración
                </div>

                @if(Route::has('areas.index'))
                    <a
                        href="{{ route('areas.index') }}"
                        class="sidebar__item
                        {{ request()->routeIs('areas.*') ? 'active' : '' }}">

                        <span class="sidebar__icon">
                            AR
                        </span>

                        Áreas

                    </a>
                @endif


                @if(Route::has('trabajadores.index'))
                    <a
                        href="{{ route('trabajadores.index') }}"
                        class="sidebar__item
                        {{ request()->routeIs('trabajadores.*') ? 'active' : '' }}">

                        <span class="sidebar__icon">
                            TR
                        </span>

                        Trabajadores

                    </a>
                @endif


                @if(Route::has('horarios.index'))
                    <a
                        href="{{ route('horarios.index') }}"
                        class="sidebar__item">

                        <span class="sidebar__icon">
                            HO
                        </span>

                        Horarios

                    </a>
                @endif


                @if(Route::has('marcaciones.index'))
                    <a
                        href="{{ route('marcaciones.index') }}"
                        class="sidebar__item">

                        <span class="sidebar__icon">
                            MA
                        </span>

                        Marcaciones

                    </a>
                @endif


                @if(Route::has('asistencias.index'))
                    <a
                        href="{{ route('asistencias.index') }}"
                        class="sidebar__item">

                        <span class="sidebar__icon">
                            AS
                        </span>

                        Asistencias

                    </a>
                @endif


                @if(Route::has('campo.index'))
                    <a
                        href="{{ route('campo.index') }}"
                        class="sidebar__item">

                        <span class="sidebar__icon">
                            CA
                        </span>

                        Trabajo en campo

                    </a>
                @endif


                @if(Route::has('solicitudes.index'))
                    <a
                        href="{{ route('solicitudes.index') }}"
                        class="sidebar__item">

                        <span class="sidebar__icon">
                            SO
                        </span>

                        Solicitudes

                    </a>
                @endif


                @if(Route::has('vacaciones.index'))
                    <a
                        href="{{ route('vacaciones.index') }}"
                        class="sidebar__item">

                        <span class="sidebar__icon">
                            VA
                        </span>

                        Vacaciones

                    </a>
                @endif


                @if(Route::has('reportesAvance.index'))
                    <a
                        href="{{ route('reportesAvance.index') }}"
                        class="sidebar__item">

                        <span class="sidebar__icon">
                            RA
                        </span>

                        Reportes de avance

                    </a>
                @endif


                @if(Route::has('feriados.index'))
                    <a
                        href="{{ route('feriados.index') }}"
                        class="sidebar__item">

                        <span class="sidebar__icon">
                            FE
                        </span>

                        Feriados

                    </a>
                @endif

            @endif

        @endauth

    </nav>


    <div class="sidebar__footer">

        <small>
            Sistema de Control de Asistencia
        </small>

    </div>

</aside>


<div
    id="sidebarOverlay"
    class="sidebar-overlay">
</div>