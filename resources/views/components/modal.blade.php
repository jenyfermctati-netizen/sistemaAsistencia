@props([
    'id',
    'title' => '',
    'size' => 'medium'
])

<div
    id="{{ $id }}"
    class="modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}Title"
    aria-hidden="true">

    <div
        class="modal__overlay"
        aria-hidden="true"
        data-modal-close>
    </div>


    <div
        class="modal__content modal__content--{{ $size }}"
        tabindex="-1">

        <div class="modal__header">

            <h2 id="{{ $id }}Title">
                {{ $title }}
            </h2>

            <button
                type="button"
                class="modal__close"
                aria-label="Cerrar {{ $title ?: 'ventana' }}"
                data-modal-close>
                <x-ui-icon name="close" />
            </button>

        </div>


        <div class="modal__body">

            {{ $slot }}

        </div>

    </div>

</div>
