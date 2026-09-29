<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token"
          content="{{ csrf_token() }}">

    <title>
        @yield('title', 'Sistema de Control de Asistencia')
    </title>

    <link rel="stylesheet"
          href="{{ asset('css/app.css') }}">

    @stack('styles')

</head>

<body class="guest">

<main class="guest__content">

    @yield('content')

</main>

<script src="{{ asset('js/modal.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>

@stack('scripts')

</body>

</html>