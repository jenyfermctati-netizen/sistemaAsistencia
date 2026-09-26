<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión - Control de Asistencia</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>

<body class="bg-light">

<div class="container">

    <div class="row justify-content-center align-items-center min-vh-100">

        <div class="col-md-5 col-lg-4">

            <div class="card shadow border-0">

                <div class="card-body p-4">

                    {{-- ENCABEZADO --}}

                    <div class="text-center mb-4">

                        <h3 class="fw-bold">
                            Control de Asistencia
                        </h3>

                        <p class="text-muted mb-0">
                            Inicie sesión para continuar
                        </p>

                    </div>


                    {{-- ERRORES --}}

                    @if($errors->any())

                        <div class="alert alert-danger">

                            @foreach($errors->all() as $error)

                                <div>
                                    {{ $error }}
                                </div>

                            @endforeach

                        </div>

                    @endif


                    {{-- FORMULARIO --}}

                    <form
                        method="POST"
                        action="{{ route('login.process') }}">

                        @csrf


                        {{-- CORREO --}}

                        <div class="mb-3">

                            <label class="form-label">
                                Correo electrónico
                            </label>

                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control"
                                placeholder="admin@empresa.com"
                                required
                                autofocus>

                        </div>


                        {{-- CONTRASEÑA --}}

                        <div class="mb-4">

                            <label class="form-label">
                                Contraseña
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="Ingrese su contraseña"
                                required>

                        </div>


                        {{-- BOTÓN --}}

                        <div class="d-grid">

                            <button
                                type="submit"
                                class="btn btn-primary">

                                Iniciar sesión

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>