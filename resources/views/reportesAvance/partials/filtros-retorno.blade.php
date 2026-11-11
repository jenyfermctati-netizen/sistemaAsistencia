@foreach(($filtrosRetorno ?? []) as $campo => $valor)
    <input type="hidden" name="retorno[{{ $campo }}]" value="{{ $valor }}">
@endforeach
