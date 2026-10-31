@extends('layouts.app')

@section('title', $pageTitle.' | Control de Asistencia')
@section('page-title', 'Inicio')
@section('page-subtitle', $pageSubtitle)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js" defer></script>
@endpush

@section('content')
    <div class="page-header dashboard-hero">
        <div class="dashboard-hero__content">
            <span class="dashboard-hero__eyebrow">{{ $heroEyebrow }}</span>
            <h2 class="page-header__title">{{ $heroTitle }}</h2>
            <p class="page-header__description">{{ $heroDescription }}</p>
        </div>

        <div class="page-header__actions dashboard-hero__meta">
            <span class="dashboard-scope">
                <span class="dashboard-scope__dot" aria-hidden="true"></span>
                {{ $scopeLabel }}
            </span>
            <span class="dashboard-date">{{ now()->locale('es')->translatedFormat('d M Y') }}</span>
        </div>
    </div>

    <div class="dashboard-page dashboard-modern" id="dashboardModern">
        @include($dashboardView)
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/pages/dashboard.js') }}" defer></script>
@endpush
