<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Asistencia')</title>

    {{-- Base, temas globales y adaptaciones. El CSS específico va al final. --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-natural.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ui-modern.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme-itrafic.css') }}">
    @stack('styles')
    @if(request()->routeIs('superadmin.*'))
        <link rel="stylesheet" href="{{ asset('css/pages/superadmin-polish.css') }}?v={{ filemtime(public_path('css/pages/superadmin-polish.css')) }}">
    @endif
    <link rel="stylesheet" href="{{ asset('css/ux-system.css') }}">
    @if(auth()->user()?->roleCode() === 'TRABAJADOR')
        <link rel="stylesheet" href="{{ asset('css/worker-interface.css') }}?v={{ filemtime(public_path('css/worker-interface.css')) }}">
    @endif
    @php
        $usuarioTema = auth()->user();
        // El superadministrador conserva la identidad global; el tema se limita
        // a usuarios que pertenecen a la empresa correspondiente.
        $empresaTema = $usuarioTema && ! $usuarioTema->isSuperAdmin()
            ? $usuarioTema->empresa
            : null;
        $colorEmpresa = $empresaTema instanceof \App\Models\Empresa
            ? $empresaTema->color_turquesa
            : null;
        $colorContraste = '#ffffff';
        if ($colorEmpresa && preg_match('/^#[A-Fa-f0-9]{6}$/', $colorEmpresa)) {
            [$rojo, $verde, $azul] = array_map('hexdec', str_split(substr($colorEmpresa, 1), 2));
            $luminancia = (0.2126 * $rojo + 0.7152 * $verde + 0.0722 * $azul) / 255;
            $colorContraste = $luminancia > 0.58 ? '#183844' : '#ffffff';
        }
    @endphp
    @if($colorEmpresa)
        <link rel="stylesheet" href="{{ asset('css/company-theme.css') }}">
    @endif
</head>
<body class="{{ auth()->user()?->roleCode() === 'TRABAJADOR' ? 'worker-interface' : '' }}" @if($colorEmpresa) data-empresa-theme="{{ $colorEmpresa }}" style="--company-color: {{ $colorEmpresa }}; --company-contrast: {{ $colorContraste }}; --primary: {{ $colorEmpresa }}; --primary-hover: color-mix(in srgb, {{ $colorEmpresa }} 82%, black); --primary-dark: color-mix(in srgb, {{ $colorEmpresa }} 62%, black); --primary-light: color-mix(in srgb, {{ $colorEmpresa }} 17%, white); --primary-mid: color-mix(in srgb, {{ $colorEmpresa }} 62%, white); --primary-soft: color-mix(in srgb, {{ $colorEmpresa }} 8%, white); --primary-border: color-mix(in srgb, {{ $colorEmpresa }} 30%, white); --accent: {{ $colorEmpresa }}; --accent-hover: var(--primary-hover); --accent-soft: var(--primary-light); --palette-smoke-blue: {{ $colorEmpresa }}; --canva-blue: {{ $colorEmpresa }}; --canva-gradient: linear-gradient(120deg, {{ $colorEmpresa }}, var(--primary-hover)); --ux-primary: {{ $colorEmpresa }}; --ux-primary-hover: var(--primary-hover); --ux-accent: {{ $colorEmpresa }}; --background: color-mix(in srgb, {{ $colorEmpresa }} 4%, #f7fafb); --surface-secondary: var(--primary-soft); --surface-hover: var(--primary-light); --border: var(--primary-border); --border-strong: color-mix(in srgb, {{ $colorEmpresa }} 42%, white); --company-sidebar-a: color-mix(in srgb, {{ $colorEmpresa }} 37%, #102027); --company-sidebar-b: color-mix(in srgb, {{ $colorEmpresa }} 31%, #0d1c22); --company-sidebar-c: color-mix(in srgb, {{ $colorEmpresa }} 26%, #09171d);" @endif>
    <a class="skip-link" href="#mainContent">Ir al contenido principal</a>
    <div class="app">
        @include('partials.sidebar')
        <div class="app__content">
            @include('partials.navbar')
            <main class="main-content" id="mainContent" tabindex="-1">
                <x-alert />
                @yield('content')
            </main>
            @include('partials.footer')
        </div>
    </div>
    <script src="{{ asset('js/modal.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/live-forms.js') }}"></script>
    <script src="{{ asset('js/ux-system.js') }}"></script>
    @stack('scripts')
</body>
</html>
