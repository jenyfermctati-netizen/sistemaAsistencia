@if(session('success'))

    <div class="alert alert--success">

        <span>
            {{ session('success') }}
        </span>

        <button
            type="button"
            class="alert__close">

            ×

        </button>

    </div>

@endif


@if(session('error'))

    <div class="alert alert--danger">

        <span>
            {{ session('error') }}
        </span>

        <button
            type="button"
            class="alert__close">

            ×

        </button>

    </div>

@endif


@if(session('warning'))

    <div class="alert alert--warning">

        <span>
            {{ session('warning') }}
        </span>

        <button
            type="button"
            class="alert__close">

            ×

        </button>

    </div>

@endif


@if($errors->any())

    <div class="alert alert--danger">

        <div>

            <strong>
                Se encontraron errores:
            </strong>

            <ul>

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

        <button
            type="button"
            class="alert__close">

            ×

        </button>

    </div>

@endif