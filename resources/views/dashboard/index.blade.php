@extends('layouts.app')

@section('title', $pageTitle.' | Control de Asistencia')
@section('page-title', 'Inicio')
@section('page-subtitle', $pageSubtitle)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard.css') }}">
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
            <span class="dashboard-date">{{ now()->translatedFormat('d M Y') }}</span>
        </div>
    </div>

    <div class="dashboard-page">
        @include($dashboardView)
    </div>
@endsection
