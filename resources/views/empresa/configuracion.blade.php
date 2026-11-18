@extends('layouts.app')
@section('title', 'Configuración de empresa')
@section('page-title', 'Configuración de empresa')
@section('page-subtitle', 'Datos, apariencia y administración de tu empresa')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/configuracion-empresa.css') }}?v={{ filemtime(public_path('css/pages/configuracion-empresa.css')) }}">
@endpush
@push('scripts')
    <script src="{{ asset('js/pages/configuracion-empresa.js') }}" defer></script>
@endpush
@section('content')
<div class="company-settings" data-company-settings>
    <header class="cs-identity">
        <div class="cs-monogram" aria-hidden="true">{{ mb_strtoupper(mb_substr($empresa->nombre_comercial ?: $empresa->razon_social, 0, 1)) }}</div>
        <div class="cs-identity-copy"><span class="cs-eyebrow">TU EMPRESA</span><h2>{{ $empresa->nombre_comercial ?: $empresa->razon_social }}</h2><p>{{ $empresa->razon_social }} · RUC {{ $empresa->ruc }}</p></div>
        <span class="cs-status"><i aria-hidden="true"></i>Empresa activa</span>
    </header>
    <form method="POST" action="{{ route('empresa.configuracion.update') }}">
        @csrf @method('PUT')
        <div class="cs-columns">
            <section class="cs-card" aria-labelledby="companyDetailsTitle">
                <div class="cs-card-heading"><span class="cs-icon"><x-nav-icon name="areas" /></span><div><h3 id="companyDetailsTitle">Información de la empresa</h3><p>Mantén actualizados los datos de contacto.</p></div></div>
                <div class="cs-fields">
                    <label class="cs-field cs-wide" for="companyName"><span>Nombre comercial</span><input id="companyName" name="nombre_comercial" value="{{ old('nombre_comercial', $empresa->nombre_comercial) }}" maxlength="200" placeholder="Nombre con el que conocen a tu empresa" autocomplete="organization" data-company-name>@error('nombre_comercial')<small class="cs-error">{{ $message }}</small>@enderror</label>
                    <label class="cs-field" for="companyEmail"><span>Correo de contacto</span><input id="companyEmail" type="email" name="email" value="{{ old('email', $empresa->email) }}" maxlength="255" placeholder="contacto@empresa.com" autocomplete="email">@error('email')<small class="cs-error">{{ $message }}</small>@enderror</label>
                    <label class="cs-field" for="companyPhone"><span>Teléfono</span><input id="companyPhone" type="tel" name="telefono" value="{{ old('telefono', $empresa->telefono) }}" maxlength="20" placeholder="Número de contacto" autocomplete="tel">@error('telefono')<small class="cs-error">{{ $message }}</small>@enderror</label>
                    <label class="cs-field cs-wide" for="companyAddress"><span>Dirección</span><input id="companyAddress" name="direccion" value="{{ old('direccion', $empresa->direccion) }}" maxlength="500" placeholder="Av., calle, número y distrito" autocomplete="street-address">@error('direccion')<small class="cs-error">{{ $message }}</small>@enderror</label>
                    <label class="cs-field cs-wide" for="companyTimezone"><span>Zona horaria <small>Obligatorio</small></span><select id="companyTimezone" name="zona_horaria" required aria-describedby="companyTimezoneHelp">
                        @foreach(DateTimeZone::listIdentifiers() as $zona)
                            <option value="{{ $zona }}" @selected(old('zona_horaria', $empresa->zona_horaria) === $zona)>{{ $zona === 'America/Lima' ? 'Lima, Perú (UTC−05:00)' : str_replace('_', ' ', $zona) }}</option>
                        @endforeach
                    </select><small id="companyTimezoneHelp">Referencia horaria configurada para tu empresa.</small>@error('zona_horaria')<small class="cs-error">{{ $message }}</small>@enderror</label>
                </div>
                <p class="cs-footnote">Para cambiar la razón social o el RUC, contacta al administrador de la plataforma.</p>
            </section>
            <section class="cs-card" aria-labelledby="companyAppearanceTitle">
                <div class="cs-card-heading"><span class="cs-icon"><x-ui-icon name="eye" /></span><div><h3 id="companyAppearanceTitle">Apariencia</h3><p>Un color que identifique a tu empresa.</p></div></div>
                <div class="cs-appearance">
                    <div class="cs-preview" data-company-preview data-fallback-name="{{ $empresa->razon_social }}">
                        <div class="cs-preview-top"><i aria-hidden="true"></i><strong data-company-preview-name>{{ old('nombre_comercial', $empresa->nombre_comercial) ?: $empresa->razon_social }}</strong><span aria-hidden="true">•••</span></div>
                        <div class="cs-preview-body"><span>VISTA PREVIA</span><strong>Todo tu equipo, conectado.</strong><p>Así se verán los acentos de tu espacio.</p><div class="cs-preview-button">Botón de ejemplo <span aria-hidden="true">→</span></div></div>
                    </div>
                    <fieldset class="cs-palette"><legend>Colores sugeridos</legend>
                        @foreach(\App\Models\Empresa::COLORES_TURQUESA as $color => $nombre)
                            <button type="button" data-company-swatch="{{ $color }}" style="--swatch: {{ $color }}" aria-label="{{ $nombre }}" aria-pressed="{{ strcasecmp(old('color_turquesa', $empresa->color_turquesa ?: '#00A9C7'), $color) === 0 ? 'true' : 'false' }}" title="{{ $nombre }}"><span aria-hidden="true">✓</span></button>
                        @endforeach
                    </fieldset>
                    <div class="cs-color-fields">
                        <label class="cs-field" for="companyColor"><span>Personalizar</span><input id="companyColor" type="color" value="{{ preg_match('/^#[A-Fa-f0-9]{6}$/', old('color_turquesa', $empresa->color_turquesa ?: '#00A9C7')) ? old('color_turquesa', $empresa->color_turquesa ?: '#00A9C7') : '#00A9C7' }}" data-company-color></label>
                        <label class="cs-field" for="companyHex"><span>Código de color</span><input id="companyHex" name="color_turquesa" value="{{ old('color_turquesa', $empresa->color_turquesa ?: '#00A9C7') }}" pattern="#[A-Fa-f0-9]{6}" maxlength="7" placeholder="#00A9C7" spellcheck="false" required data-company-hex></label>
                    </div>
                    @error('color_turquesa')<small class="cs-error">{{ $message }}</small>@enderror
                    <p class="cs-hint">Se aplicará a los botones y acentos de la interfaz al guardar.</p>
                </div>
            </section>
        </div>
        <footer class="cs-savebar"><p>Los cambios se aplican a toda tu empresa.</p><button class="button button--primary" type="submit">Guardar cambios <span aria-hidden="true">→</span></button></footer>
    </form>
    <section class="cs-card cs-access" aria-labelledby="companyAccessTitle">
        <div class="cs-card-heading"><span class="cs-icon"><x-nav-icon name="users" /></span><div><h3 id="companyAccessTitle">Administración de la cuenta</h3><p>Tu empresa tiene un único administrador.</p></div><a class="cs-audit" href="{{ route('empresa.auditoria') }}">Ver historial ↗</a></div>
        <div class="cs-owner"><span class="cs-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><div><strong>{{ auth()->user()->name }}</strong><span>{{ auth()->user()->email }}</span></div><span class="cs-owner-label">Administrador actual</span></div>
        <details class="cs-transfer" @if($errors->has('usuario_id') || $errors->has('password')) open @endif>
            <summary><div><strong>Transferir administración</strong><span>Designa a otra persona como responsable de la empresa.</span></div><span class="cs-chevron" aria-hidden="true">⌄</span></summary>
            <div class="cs-transfer-content">
                <p class="cs-notice">El nuevo responsable tendrá acceso completo. Tu cuenta {{ auth()->user()->trabajador_id ? 'pasará al rol Trabajador' : 'quedará inactiva porque no está vinculada a una ficha de trabajador' }} y se cerrará tu sesión. El historial se conserva.</p>
                @if($usuarios->isNotEmpty())
                    <form method="POST" action="{{ route('empresa.administrador.transferir') }}">@csrf
                        <div class="cs-fields">
                            <label class="cs-field" for="newCompanyAdmin"><span>Nuevo administrador</span><select id="newCompanyAdmin" name="usuario_id" required><option value="">Seleccionar usuario activo</option>@foreach($usuarios as $usuario)<option value="{{ $usuario->id }}" @selected(old('usuario_id') == $usuario->id)>{{ $usuario->name }} · {{ $usuario->email }}</option>@endforeach</select>@error('usuario_id')<small class="cs-error">{{ $message }}</small>@enderror</label>
                            <label class="cs-field" for="adminPassword"><span>Tu contraseña actual</span><input id="adminPassword" type="password" name="password" autocomplete="current-password" placeholder="Confirma tu identidad" required>@error('password')<small class="cs-error">{{ $message }}</small>@enderror</label>
                        </div>
                        <div class="cs-transfer-footer"><button class="button button--danger" type="submit">Transferir y cerrar mi sesión</button></div>
                    </form>
                @else
                    <p class="cs-hint">Necesitas otra cuenta activa para transferir la administración. Puedes crearla desde <a href="{{ route('trabajadores.index') }}">Personal</a>.</p>
                @endif
            </div>
        </details>
    </section>
</div>
@endsection
