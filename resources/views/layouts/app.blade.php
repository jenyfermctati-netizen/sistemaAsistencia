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
</head>
<body>
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
    @stack('scripts')
</body>
</html>