<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión - Control de Asistencias</title>
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-natural.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-itrafic.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ux-system.css') }}">
    <link rel="stylesheet" href="{{ asset('css/design-studio.css') }}">
</head>

<body class="guest login-standalone">
    <div class="login-backdrop" aria-hidden="true"><span class="login-backdrop__circle"></span><span class="login-backdrop__stripe"></span><span class="login-backdrop__blue"></span><span class="login-backdrop__dots login-backdrop__dots--top"></span><span class="login-backdrop__dots login-backdrop__dots--bottom"></span></div>
    <main class="login-page login-page--standalone">
        <section class="login-card" aria-labelledby="login-title">
            <header class="login-card__header">
                <h1 id="login-title">Bienvenido de nuevo</h1>
                <p>Ingresa tus credenciales para acceder a la plataforma.</p>
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

            <form method="POST" action="{{ route('login.process') }}" class="login-form">
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
                            placeholder="correo@empresa.com" autocomplete="username" required autofocus>
                    </div>
                </div>

                <div class="login-field">
                    <label for="password">Contraseña</label>
                    <div class="login-field__control">
                        <span class="login-field__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <rect x="5" y="10" width="14" height="10" rx="2" />
                                <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2" />
                            </svg>
                        </span>
                        <input id="password" type="password" name="password" placeholder="Ingresa tu contraseña"
                            autocomplete="current-password" required>
                        <button type="button" class="password-toggle" id="passwordToggle"
                            aria-label="Mostrar contraseña" aria-pressed="false">
                            <svg class="password-toggle__show" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                                <circle cx="12" cy="12" r="2.5" />
                            </svg>
                            <svg class="password-toggle__hide" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m3 3 18 18M10.6 6.2c.5-.1.9-.2 1.4-.2 6 0 9.5 6 9.5 6a17 17 0 0 1-2.2 2.8M6.1 6.2C3.8 7.7 2.5 12 2.5 12s3.5 6 9.5 6c1.3 0 2.5-.3 3.5-.7M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="login-options">
                    <label class="remember-option">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        <span>Recordar mi sesión</span>
                    </label>
                    <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="login-submit">
                    <span>Ingresar al sistema</span>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 12h14M14 7l5 5-5 5" />
                    </svg>
                </button>
            </form>

        </section>
        <footer class="login-caption">Sistema de control de asistencia</footer>
    </main>

    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('passwordToggle');

        passwordToggle?.addEventListener('click', () => {
            const isVisible = passwordInput.type === 'text';
            passwordInput.type = isVisible ? 'password' : 'text';
            passwordToggle.setAttribute('aria-pressed', String(!isVisible));
            passwordToggle.setAttribute('aria-label', isVisible ? 'Mostrar contraseña' : 'Ocultar contraseña');
        });
    </script>
</body>

</html>
