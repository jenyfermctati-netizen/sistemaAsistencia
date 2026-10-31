<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión - Control de Asistencias</title>
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-natural.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-itrafic.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ux-system.css') }}">
</head>

<body class="guest">
    <main class="login-page">
        <div class="login-shell">
            <section class="login-hero login-hero--login" aria-label="Sistema de Control de Asistencias">
                <div class="login-hero__brandline">
                    <span class="login-hero__mark" aria-hidden="true">CA</span>
                    <span class="login-hero__brandcopy">
                        <strong>Control de Asistencia</strong>
                        <small>Gestión de personal</small>
                    </span>
                </div>

                <div class="login-hero__copy">
                    <span class="login-hero__eyebrow">Sistema de gestión</span>
                    <h1>Tu equipo, siempre al día.</h1>
                    <p>Asistencia, horarios y avances en un solo lugar.</p>

                    <div class="login-hero__highlights" aria-label="Funciones principales">
                        <div class="login-hero__highlight">
                            <span>01</span>
                            <div>
                                <strong>Asistencia</strong>
                                <small>Registro diario</small>
                            </div>
                        </div>
                        <div class="login-hero__highlight">
                            <span>02</span>
                            <div>
                                <strong>Horarios</strong>
                                <small>Jornadas claras</small>
                            </div>
                        </div>
                        <div class="login-hero__highlight">
                            <span>03</span>
                            <div>
                                <strong>Reportes</strong>
                                <small>Seguimiento ágil</small>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="login-panel">
                <div class="login-card">
                    <img class="login-card__logo" src="{{ asset('images/auth/connext-logo.png') }}"
                        alt="Connext Ingeniería, Tecnología y Desarrollo">

                    <div class="login-card__status">
                        <span aria-hidden="true"></span>
                        Acceso para personal autorizado
                    </div>

                    <header class="login-card__header">
                        <span class="login-card__eyebrow">Portal de colaboradores</span>
                        <h2>Bienvenido de nuevo</h2>
                        <p>Inicia sesión con tu cuenta para continuar.</p>
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
                                <span>Recordarme</span>
                            </label>
                            <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                        </div>

                        <button type="submit" class="login-submit">
                            <span>Iniciar sesión</span>
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 12h14M14 7l5 5-5 5" />
                            </svg>
                        </button>
                    </form>

                    <div class="login-divider" aria-hidden="true">
                        <span>Acceso seguro</span>
                    </div>

                    <div class="login-security">
                        <span class="login-security__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 2.5 19 5v5.5c0 4.7-2.8 8.7-7 11-4.2-2.3-7-6.3-7-11V5l7-2.5Z" />
                                <path d="m9 12 2 2 4-4" />
                            </svg>
                        </span>
                        <div>
                            <strong>Sistema de Control de Asistencias</strong>
                            <span>Connext Ingeniería, Tecnología y Desarrollo</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
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
