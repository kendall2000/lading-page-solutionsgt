{{-- Acceso de prueba vigente: dirección, usuario, contraseña (oculta hasta tocar «Mostrar») y días que quedan. --}}
@php
    $diasPrueba = $prueba->vence_el ? max(0, (int) now()->startOfDay()->diffInDays($prueba->vence_el->copy()->startOfDay(), false)) : null;
@endphp
<div class="card h-100 border border-warning-300">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <span class="badge badge-phoenix badge-phoenix-warning mb-2">Prueba</span>
                <h5 class="mb-0">{{ $prueba->sistema?->nombre ?? 'Sistema' }}</h5>
            </div>
            @if ($diasPrueba !== null)
                <div class="text-end">
                    <p class="fs-2 fw-bold mb-0 {{ $diasPrueba <= 2 ? 'text-danger' : 'text-warning' }}">{{ $diasPrueba }}</p>
                    <p class="fs--2 text-700 mb-0">{{ $diasPrueba === 1 ? 'día' : 'días' }} restantes</p>
                </div>
            @endif
        </div>
        <dl class="row fs--1 mb-3">
            <dt class="col-4 text-700 fw-normal">Usuario</dt>
            <dd class="col-8 text-1000 fw-semi-bold text-break">{{ $prueba->usuario_prueba }}</dd>
            <dt class="col-4 text-700 fw-normal">Contraseña</dt>
            <dd class="col-8 mb-0">
                <span class="fw-semi-bold text-1000 d-none" id="clave{{ $prueba->id }}">{{ $prueba->clave_prueba }}</span>
                <span class="text-600" id="oculta{{ $prueba->id }}">••••••••</span>
                <button class="btn btn-link btn-sm p-0 ms-2" type="button" onclick="document.getElementById('clave{{ $prueba->id }}').classList.toggle('d-none');document.getElementById('oculta{{ $prueba->id }}').classList.toggle('d-none');this.textContent = this.textContent === 'Mostrar' ? 'Ocultar' : 'Mostrar';">Mostrar</button>
            </dd>
            @if ($prueba->vence_el)
                <dt class="col-4 text-700 fw-normal mt-2">Vence</dt>
                <dd class="col-8 mt-2 mb-0">{{ $prueba->vence_el->translatedFormat('j \d\e F Y') }}</dd>
            @endif
        </dl>
        @if ($prueba->url_acceso)
            <a class="btn btn-warning btn-sm w-100" href="{{ $prueba->url_acceso }}" target="_blank" rel="noopener noreferrer"><span class="fa-solid fa-arrow-up-right-from-square me-2"></span>Entrar al sistema de prueba</a>
        @endif
    </div>
</div>
