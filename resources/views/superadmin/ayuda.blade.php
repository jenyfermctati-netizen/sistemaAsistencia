@extends('layouts.app')
@section('title', 'Ayuda | CONNEXT')
@section('page-title', 'Ayuda y primeros pasos')
@section('page-subtitle', 'Una guía para administrar la plataforma')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/superadmin.css') }}">@endpush
@section('content')
<div class="sa-page">
    <section class="sa-panel"><h3>¿Dónde encuentro cada cosa?</h3>
        <p><strong>Inicio:</strong> indicadores y gráficos generales.</p>
        <p><strong>Empresas:</strong> registra una empresa o abre su ficha para consultar la información, administrar responsables y configurar colores, acceso y dispositivos.</p>
        <p><strong>Auditoría:</strong> consulta el estado actual de las empresas y el historial de cambios, con fecha y usuario responsable.</p>
        <a class="sa-button" href="{{ route('superadmin.empresas.create') }}"><x-ui-icon name="plus" /> Crear empresa</a>
    </section>
    @include('superadmin.partials.guia')
</div>
@endsection
