@props(['count', 'label' => 'por revisar'])

@if ($count > 0)
    <span class="sidebar__pending-count" aria-label="{{ $count }} {{ $label }}">{{ $count }}</span>
@endif
