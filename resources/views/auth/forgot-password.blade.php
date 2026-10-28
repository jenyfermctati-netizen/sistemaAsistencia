<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Recuperar contraseña - Control de Asistencias</title>
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-natural.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-itrafic.css') }}">
</head>

<body class="guest">
    <main class="login-page">
        <div class="login-shell">
            <section class="login-hero" aria-label="Sistema de Control de Asistencias">
                <div class="login-hero__brand">
                    <img src="{{ asset('images/auth/connext-logo.png') }}" alt="Connext Ingeniería, Tecnología y Desarrollo">
                </div>

                <div class="login-hero__copy">
                    <span class="login-hero__eyebrow">Recuperación segura</span>
                    <h1>Vuelve a acceder a tu cuenta</h1>
                    <p>Te enviaremos un enlace de uso único al correo asociado con tu usuario.</p>
                </div>
            </section>

            <section class="login-panel">
                <div class="login-card">
                    <img class="login-card__logo" src="{{ asset('images/auth/connext-logo.png') }}"
                        alt="Connext Ingeniería, Tecnología y Desarrollo">

                    <header class="login-card__header">
                        <span class="login-card__eyebrow">Ayuda de acceso</span>
                        <h2>¿Olvidaste tu contraseña?</h2>
                        <p>Ingresa tu correo institucional para recibir las instrucciones.</p>
                    </header>

                    @if (session('status'))
                        <div class="login-alert login-alert--success" role="status">
                            <span class="login-alert__icon" aria-hidden="true">✓</span>
                            <div><p>{{ session('status') }}</p></div>
                        </div>
                    @endif

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

                    <form method="POST" action="{{ route('password.email') }}" class="login-form">
                        @csrf

                        <div class="login-field">
                            <label for="email">Correo electrónico</label>
                            <div class="login-field__control">
                                <span class="login-field__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path d="M4 6.5h16v11H4v-11Z" />
                                        <path d="m5 7.5 7 5 7-5" />
                                    </svg>
                                </span>
                                <input id="email" type="email" name="email" value="{{ old('email') }}"
                                    placeholder="correo@empresa.com" autocomplete="email" required autofocus>
                            </div>
                        </div>

                        <button type="submit" class="login-submit">
                            <span>Enviar enlace</span>
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
