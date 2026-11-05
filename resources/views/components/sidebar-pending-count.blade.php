@props(['count'])

@if ($count > 0)
    <span class="sidebar__pending-count" aria-label="{{ $count }} por revisar">{{ $count }}</span>
@endif
