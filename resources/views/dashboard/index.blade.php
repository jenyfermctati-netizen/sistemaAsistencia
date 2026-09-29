@extends('layouts.app')

@section('title', 'Dashboard')

@section('page-title', 'Dashboard')

@section('page-subtitle', 'Resumen general del sistema')

@section('content')

<div class="page-header">

    <div>

        <h2 class="page-header__title">
            Bienvenido,
            {{ auth()->user()->name }}
        </h2>

        <p class="page-header__description">
            Panel principal del sistema de control de asistencia.
        </p>

    </div>

</div>


<div class="dashboard-grid">

    <div class="dashboard-card">

        <span class="dashboard-card__label">
            Rol
        </span>

        <strong class="dashboard-card__value">
            {{ auth()->user()->rol?->nombre ?? 'Sin rol' }}
        </strong>

    </div>


    <div class="dashboard-card">

        <span class="dashboard-card__label">
            Estado
        </span>

        <strong class="dashboard-card__value">

            {{ auth()->user()->estado
                ? 'Activo'
                : 'Inactivo'
            }}

        </strong>

    </div>


    <div class="dashboard-card">

        <span class="dashboard-card__label">
            Último acceso
        </span>

        <strong class="dashboard-card__value dashboard-card__value--small">

            {{ auth()->user()->ultimo_acceso
                ? auth()->user()->ultimo_acceso->format('d/m/Y H:i')
                : 'Primer acceso'
            }}

        </strong>

    </div>

</div>

@endsection