{{-- Tarjeta de imagen con vista previa inmediata (como Configuración del sistema del restaurante). Parámetros: campo, titulo, ayuda, url, oscuro, aceptar. --}}
<div class="col-12 col-xl-6">
    <div class="border border-300 rounded-3 p-3 h-100 d-flex gap-3">
        <div class="rounded-3 d-flex flex-center overflow-hidden flex-shrink-0 {{ ($oscuro ?? false) ? 'bg-dark' : 'bg-soft' }}" style="width: 6rem; height: 6rem;">
            <img class="img-previa {{ $url ? '' : 'd-none' }}" data-campo="{{ $campo }}" @if ($url) src="{{ $url }}" @endif alt="" style="max-width: 100%; max-height: 100%; object-fit: contain;" />
            @unless ($url)<span class="fas fa-image fs-3 text-300 sin-imagen" data-campo="{{ $campo }}"></span>@endunless
        </div>
        <div class="flex-1 min-w-0">
            <h5 class="mb-1 text-1000">{{ $titulo }}</h5>
            <p class="fs--2 text-600 mb-2">{{ $ayuda }}</p>
            <input class="form-control form-control-sm @error($campo) is-invalid @enderror archivo-imagen" id="{{ $campo }}" name="{{ $campo }}" type="file" data-campo="{{ $campo }}"
                   accept="{{ ($aceptar ?? null) ?: 'image/png,image/jpeg,image/webp' }}" />
            @error($campo)<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if ($url)
                <div class="form-check mt-2 mb-0">
                    <input class="form-check-input" id="quitar_{{ $campo }}" name="quitar_{{ $campo }}" type="checkbox" value="1" />
                    <label class="form-check-label fs--1" for="quitar_{{ $campo }}">Quitar y usar la de fábrica</label>
                </div>
            @endif
        </div>
    </div>
</div>
