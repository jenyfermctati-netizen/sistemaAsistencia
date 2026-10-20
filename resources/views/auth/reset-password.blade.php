<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Restablecer contraseña - Control de Asistencias</title>
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-natural.css') }}">
</head>

<body class="guest">
    <main class="login-page">
        <div class="login-shell">
            <section class="login-hero" aria-label="Sistema de Control de Asistencias">
                <div class="login-hero__brand">
                    <img src="{{ asset('images/auth/connext-logo.png') }}" alt="Connext Ingeniería, Tecnología y Desarrollo">
                </div>

                <div class="login-hero__copy">
                    <span class="login-hero__eyebrow">Protección de cuenta</span>
                    <h1>Crea una nueva contraseña</h1>
                    <p>Usa una combinación que no hayas utilizado antes y evita compartirla.</p>
                </div>
            </section>

            <section class="login-panel">
                <div class="login-card">
                    <img class="login-card__logo" src="{{ asset('images/auth/connext-logo.png') }}"
                        alt="Connext Ingeniería, Tecnología y Desarrollo">

                    <header class="login-card__header">
                        <span class="login-card__eyebrow">Recuperación de acceso</span>
                        <h2>Restablecer contraseña</h2>
                        <p>Completa los datos para proteger nuevamente tu cuenta.</p>
                    </header>

                    @if ($errors->any())
                        <div class="login-alert" role="alert">
                            <span class="login-alert__icon" aria-hidden="true">!</span>
                            <div>
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.store') }}" class="login-form">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="login-field">
                            <label for="email">Correo electrónico</label>
                            <div class="login-field__control">
                                <span class="login-field__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M4 6.5h16v11H4v-11Z" />
                                        <path d="m5 7.5 7 5 7-5" />
                                    </svg>
                                </span>
                                <input id="email" type="email" name="email" value="{{ old('email', $email) }}"
                                    autocomplete="email" required autofocus>
                            </div>
                        </div>

                        <div class="login-field">
                            <label for="password">Nueva contraseña</label>
                            <div class="login-field__control">
                                <span class="login-field__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <rect x="5" y="10" width="14" height="10" rx="2" />
                                        <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2" />
                                    </svg>
                                </span>
                                <input id="password" type="password" name="password" autocomplete="new-password" required>
                            </div>
                        </div>

                        <div class="login-field">
                            <label for="password_confirmation">Confirmar nueva contraseña</label>
                            <div class="login-field__control">
                                <span class="login-field__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <rect x="5" y="10" width="14" height="10" rx="2" />
                                        <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2" />
                                    </svg>
                                </span>
                                <input id="password_confirmation" type="password" name="password_confirmation"
                                    autocomplete="new-password" required>
                            </div>
                        </div>

                        <p class="login-form__help">Mínimo 8 caracteres, con letras y números.</p>

                        <button type="submit" class="login-submit">
                            <span>Guardar nueva contraseña</span>
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 12h14M14 7l5 5-5 5" />
                            </svg>
                        </button>
                    </form>

                    <div class="login-card__actions">
                        <a class="login-back-link" href="{{ route('login') }}">← Volver al inicio de sesión</a>
                    </div>
                </div>
            </section>
        </div>
    </main>
</body>

</html>
