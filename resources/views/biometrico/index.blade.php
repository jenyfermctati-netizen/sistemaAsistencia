@extends('layouts.app')

@section('title', 'Dispositivo biométrico')

@section('content')
    <div class="biometrico-page">
        <div class="page-header">
            <div>
                <h1>Dispositivo biométrico</h1>
                <p>Estado de comunicación del sistema de control de asistencia.</p>
            </div>
        </div>

        @forelse($dispositivos as $dispositivo)
            @php
                $conectado = $dispositivo->esta_conectado;
            @endphp

            <section class="biometrico-card">
                <div class="biometrico-card__header">
                    <div>
                        <span class="biometrico-device-icon">SF</span>

                        <div>
                            <h2>{{ $dispositivo->nombre }}</h2>
                            <p>{{ $dispositivo->modelo ?? 'Dispositivo biométrico' }}</p>
                        </div>
                    </div>

                    <span class="estado-badge {{ $conectado ? 'conectado' : 'desconectado' }}">
                        <span class="estado-dot"></span>
                        {{ $conectado ? 'Conectado' : 'Sin conexión' }}
                    </span>
                </div>

                <div class="biometrico-grid">
                    <div class="biometrico-data">
                        <span>Serial</span>
                        <strong>{{ $dispositivo->serial }}</strong>
                    </div>

                    <div class="biometrico-data">
                        <span>Última IP</span>
                        <strong>{{ $dispositivo->ultima_ip ?? '—' }}</strong>
                    </div>

                    <div class="biometrico-data">
                        <span>Última comunicación</span>
                        <strong>
                            {{ $dispositivo->ultima_conexion ? $dispositivo->ultima_conexion->format('d/m/Y H:i:s') : 'Sin comunicación' }}
                        </strong>
                    </div>

                    <div class="biometrico-data">
                        <span>Última marcación</span>
                        <strong>
                            {{ $dispositivo->ultima_marcacion ? $dispositivo->ultima_marcacion->format('d/m/Y H:i:s') : 'Sin marcaciones' }}
                        </strong>
                    </div>
                </div>

                <div class="biometrico-footer">
                    @if ($conectado)
                        <span class="biometrico-ok">
                            El dispositivo se está comunicando correctamente con el servidor.
                        </span>
                    @else
                        <span class="biometrico-warning">
                            No se ha recibido comunicación del dispositivo durante los últimos 2 minutos.
                        </span>
                    @endif
                </div>
            </section>
            <section class="panel biometric-sync">
    <div class="biometric-sync__header">
        <div>
            <h2>Sincronización de trabajadores</h2>
            <p>Consulta y compara los usuarios existentes en el SenseFace sin eliminarlos ni modificarlos.</p>
        </div>

        @if($dispositivos->first())
            <div class="biometric-sync__actions">
                <form method="POST" action="{{ route('biometrico.usuarios.consultar',$dispositivos->first()) }}">
                    @csrf
                    <button class="button" type="submit">Consultar SenseFace</button>
                </form>

                <form method="POST" action="{{ route('biometrico.usuarios.reconciliar',$dispositivos->first()) }}">
                    @csrf
                    <button class="button button--secondary" type="submit">Comparar trabajadores</button>
                </form>
            </div>
        @endif
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>PIN</th>
                    <th>Usuario en SenseFace</th>
                    <th>Trabajador en sistema</th>
                    <th>Estado</th>
                    <th>Última verificación</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $usuario)
                    <tr>
                        <td>{{ $usuario->pin }}</td>
                        <td>{{ $usuario->nombre_dispositivo ?: '—' }}</td>
                        <td>
                            @if($usuario->trabajador)
                                {{ $usuario->trabajador->nombres }} {{ $usuario->trabajador->apellidos }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <span class="badge">
                                {{ str_replace('_',' ',$usuario->estado) }}
                            </span>
                        </td>
                        <td>
                            {{ $usuario->ultima_verificacion?->format('d/m/Y H:i:s') ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Todavía no se ha consultado el inventario del SenseFace.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
        @empty
            <div class="panel empty-state">
                <h3>No se ha detectado ningún dispositivo</h3>
                <p>
                    El dispositivo aparecerá automáticamente cuando se comunique
                    nuevamente con el servidor.
                </p>
            </div>
        @endforelse

        @if ($ultimaMarcacion)
            <section class="panel ultima-marcacion">
                <div>
                    <span>Última marcación recibida</span>

                    <strong>
                        {{ $ultimaMarcacion->trabajador?->nombres }}
                        {{ $ultimaMarcacion->trabajador?->apellidos }}
                    </strong>
                </div>

                <div>
                    <span>Fecha y hora</span>
                    <strong>{{ $ultimaMarcacion->fecha_hora->format('d/m/Y H:i:s') }}</strong>
                </div>

                <div>
                    <span>Tipo</span>
                    <strong>{{ $ultimaMarcacion->tipo }}</strong>
                </div>
            </section>
        @endif
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/biometrico.css') }}">
@endpush
@push('scripts')
    <script>
        setTimeout(() => window.location.reload(), 30000);
    </script>
@endpush
