@extends('layouts.app')

@section('title', 'Ayuda | Control de Asistencia')
@section('page-title', 'Ayuda')
@section('page-subtitle', 'Guías rápidas para completar tus tareas')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/help.css') }}">
@endpush

@section('content')
    @php($esSuperAdminAyuda = auth()->user()?->isSuperAdmin())
    @php($esAdminAyuda = auth()->user()?->roleCode() === 'ADMINISTRADOR')
    <div class="help-page">
        <section class="help-intro">
            <span class="help-intro__icon" aria-hidden="true">?</span>
            <div><span class="help-eyebrow">CENTRO DE AYUDA</span><h2>Encuentra el siguiente paso</h2><p>Elige una guía o entra directamente al módulo que necesitas.</p></div>
        </section>

        <section class="help-grid" aria-label="Guías y accesos rápidos">
            @if($esSuperAdminAyuda)
                <article class="help-card"><span class="help-card__step">01 · EMPRESAS</span><h3>Crear y consultar</h3><p>Registra empresas y revisa su información.</p><a class="help-action" href="{{ route('superadmin.empresas.index') }}">Ir a empresas <span aria-hidden="true">→</span></a></article>
                <article class="help-card"><span class="help-card__step">02 · ADMINISTRADORES</span><h3>Gestionar accesos</h3><p>Elige una empresa para administrar sus cuentas.</p><a class="help-action" href="{{ route('superadmin.administradores.index') }}">Ver empresas <span aria-hidden="true">→</span></a></article>
                <article class="help-card"><span class="help-card__step">03 · AUDITORÍA</span><h3>Revisar ingresos</h3><p>Consulta el total de ingresos por administrador.</p><a class="help-action" href="{{ route('superadmin.auditoria.index') }}">Ver ingresos <span aria-hidden="true">→</span></a></article>
            @else
                <article class="help-card"><span class="help-card__step">01 · JORNADA</span><h3>{{ $esAdminAyuda || auth()->user()?->roleCode() === 'GERENTE' ? 'Asistencia' : 'Mi jornada' }}</h3><p>{{ $esAdminAyuda || auth()->user()?->roleCode() === 'GERENTE' ? 'Consulta marcaciones, tardanzas y el resumen del día desde Asistencias.' : 'Consulta tu resumen de asistencia y el estado de tu jornada desde Inicio.' }}</p><a class="help-action" href="{{ $esAdminAyuda || auth()->user()?->roleCode() === 'GERENTE' ? route('asistencias.index') : route('dashboard') }}">{{ $esAdminAyuda || auth()->user()?->roleCode() === 'GERENTE' ? 'Ver asistencias' : 'Ver mi jornada' }} <span aria-hidden="true">→</span></a></article>
                <article class="help-card"><span class="help-card__step">02 · ACTIVIDADES</span><h3>Avances y pendientes</h3><p>Registra avances y revisa tareas, observaciones o registros pendientes.</p><a class="help-action" href="{{ route('reportesAvance.index') }}">Ir a avances <span aria-hidden="true">→</span></a></article>
                <article class="help-card"><span class="help-card__step">03 · GESTIONES</span><h3>Solicitudes</h3><p>Presenta una solicitud y vuelve a esta sección para consultar su estado.</p><a class="help-action" href="{{ route('solicitudes.index') }}">Ver solicitudes <span aria-hidden="true">→</span></a></article>
                @if($esAdminAyuda)
                    <article class="help-card"><span class="help-card__step">04 · EQUIPO</span><h3>Personal y áreas</h3><p>Administra trabajadores, áreas, horarios y datos operativos de tu empresa.</p><a class="help-action" href="{{ route('trabajadores.index') }}">Gestionar personal <span aria-hidden="true">→</span></a></article>
                @endif
            @endif
        </section>

        <section class="help-contact">
            <span class="help-contact__icon" aria-hidden="true">i</span>
            <div><h3>¿No encuentras lo que buscas?</h3><p>Solicita ayuda al administrador de tu empresa para revisar permisos o recibir orientación.</p></div>
        </section>
    </div>
@endsection
