{{-- Campo de imagen con vista previa y opción de quitar. Parámetros: campo, titulo, ayuda, url. --}}
<div class="mb-3">
    <label class="form-label" for="{{ $campo }}">{{ $titulo }}</label>
    <div class="d-flex align-items-center gap-3">
        <div class="border border-300 rounded-2 d-flex flex-center bg-light flex-shrink-0" style="width: 96px; height: 72px; overflow: hidden;">
            @if ($url)
                <img src="{{ $url }}" alt="" style="max-width: 100%; max-height: 100%; object-fit: contain;" />
            @else
                <span class="fa-regular fa-image text-400 fs-2"></span>
            @endif
        </div>
        <div class="flex-1">
            <input class="form-control form-control-sm" id="{{ $campo }}" name="{{ $campo }}" type="file" accept="image/png,image/jpeg,image/webp{{ $campo === 'favicon' ? ',image/x-icon' : '' }}" />
            @if ($ayuda ?? null)<div class="form-text">{{ $ayuda }}</div>@endif
            @if ($url)
                <div class="form-check mt-1 mb-0">
                    <input class="form-check-input" id="quitar_{{ $campo }}" name="quitar_{{ $campo }}" type="checkbox" value="1" />
                    <label class="form-check-label fs--1 text-700" for="quitar_{{ $campo }}">Quitar imagen</label>
                </div>
            @endif
        </div>
    </div>
</div>
