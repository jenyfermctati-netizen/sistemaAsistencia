@props(['excel', 'pdf'])

<details class="download-menu">
    <summary class="button button--secondary button--small">
        <x-ui-icon name="download" /> Descargar
    </summary>
    <div class="download-menu__options">
        <a href="{{ $excel }}">Excel (.xlsx)</a>
        <a href="{{ $pdf }}">PDF</a>
    </div>
</details>
