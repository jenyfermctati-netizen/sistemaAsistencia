<div class="sa-color-picker" data-color-picker>
    <div class="sa-color-picker__heading"><strong>Color turquesa</strong><span>Elige un tono o escribe un código HEX.</span></div>
    <div class="sa-color-swatches" aria-label="Paleta turquesa">
        @foreach(\App\Models\Empresa::COLORES_TURQUESA as $color => $nombre)
            <button type="button" class="sa-color-swatch" data-color-choice="{{ $color }}" style="--swatch: {{ $color }}" aria-label="{{ $nombre }}" title="{{ $nombre }}"></button>
        @endforeach
    </div>
    <label class="sa-color-picker__native"><span>Selector</span><input type="color" value="{{ $value }}" data-color-native></label>
    <label class="sa-color-picker__hex"><span>Código HEX</span><input type="text" name="color_turquesa" value="{{ $value }}" pattern="#[A-Fa-f0-9]{6}" maxlength="7" placeholder="#00A9C7" data-color-hex required></label>
</div>
