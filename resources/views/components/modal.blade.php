@props([
    'id',
    'title' => '',
    'size' => 'medium'
])

<div
    id="{{ $id }}"
    class="modal"
    aria-hidden="true">

    <div
        class="modal__overlay"
        data-modal-close>
    </div>


    <div
        class="modal__content modal__content--{{ $size }}">

        <div class="modal__header">

            <h2>
                {{ $title }}
            </h2>

            <button
                type="button"
                class="modal__close"
                data-modal-close>

                ×

            </button>

        </div>


        <div class="modal__body">

            {{ $slot }}

        </div>

    </div>

</div>