@extends('layouts.app')

@section('title', 'Cambiar contraseña')
@section('page-title', 'Mi cuenta')
@section('page-subtitle', 'Actualiza tus credenciales de acceso')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/password.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <div>
            <h2 class="page-header__title">Cambiar contraseña</h2>
            <p class="page-header__description">
                Confirma tu contraseña actual y elige una nueva para mantener segura tu cuenta.
            </p>
        </div>
    </div>

    <section class="password-card" aria-labelledby="password-card-title">
        <div class="password-card__heading">
            <span class="password-card__icon" aria-hidden="true">⚿</span>
            <div>
                <h3 id="password-card-title">Seguridad de la cuenta</h3>
                <p>Tu sesión actual permanecerá abierta después del cambio.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="form password-form">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="current_password" class="form-label">Contraseña actual <span class="form-required">*</span></label>
                <input id="current_password" type="password" name="current_password"
                    class="form-control @error('current_password') is-invalid @enderror"
                    autocomplete="current-password" required autofocus>
                @error('current_password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Nueva contraseña <span class="form-required">*</span></label>
                <input id="password" type="password" name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    autocomplete="new-password" minlength="8" required>
                <p class="form-help">Debe tener al menos 8 caracteres e incluir letras y números.</p>
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirmar nueva contraseña <span class="form-required">*</span></label>
                <input id="password_confirmation" type="password" name="password_confirmation"
                    class="form-control" autocomplete="new-password" minlength="8" required>
            </div>

            <div class="password-form__actions">
                <a href="{{ url()->previous() }}" class="button button--secondary">Cancelar</a>
                <button type="submit" class="button button--primary">Cambiar contraseña</button>
            </div>
        </form>
    </section>
@endsection
