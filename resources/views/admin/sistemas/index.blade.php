@extends('layouts.admin', ['titulo' => 'Software'])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Software</h2>
            <p class="text-700 mb-0">Los sistemas de tu catálogo: categoría, si son gratis o premium, y si aceptan demostración o prueba por días.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.sistemas.create') }}"><span class="fa-solid fa-plus me-2"></span>Nuevo sistema</a>
    </div>

    <div class="card">
        <div class="card-body">
            @if ($sistemas->isEmpty())
                <p class="text-700 mb-0">Todavía no hay sistemas. Crea el primero.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm fs--1 mb-0 align-middle">
                        <thead>
                        <tr>
                            <th class="ps-0" style="width: 60px">Orden</th>
                            <th>Sistema</th>
                            <th>Categoría</th>
                            <th>Tipo</th>
                            <th class="text-center">Capturas</th>
                            <th class="text-center">Manuales</th>
                            <th class="text-center">Visitas</th>
                            <th>Estado</th>
                            <th class="text-end pe-0"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($sistemas as $s)
                            <tr>
                                <td class="ps-0">{{ $s->orden }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="sgt-icono-admin me-3 text-primary fs-1"><span class="{{ $s->icono }}"></span></span>
                                        <div>
                                            <a class="fw-bold text-1000" href="{{ route('admin.sistemas.edit', $s) }}">{{ $s->nombre }}</a>
                                            <div class="text-700 fs--2">{{ \Illuminate\Support\Str::limit($s->resumen, 90) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $s->categoria?->nombre ?? '—' }}</td>
                                <td class="text-nowrap">
                                    @php [$textoMod, $colorMod] = $s->modalidadInfo(); @endphp
                                    <span class="badge badge-phoenix badge-phoenix-{{ $colorMod }}">{{ $textoMod }}</span>
                                    @if ($s->acepta_prueba)<span class="badge badge-phoenix badge-phoenix-success">Prueba {{ $s->dias_prueba }} d</span>@endif
                                    @if ($s->acepta_demo)<span class="badge badge-phoenix badge-phoenix-info">Demo</span>@endif
                                </td>
                                <td class="text-center">{{ $s->imagenes_count }}</td>
                                <td class="text-center">{{ $s->manuales_count }}</td>
                                <td class="text-center">{{ number_format($s->visitas) }}</td>
                                <td>
                                    @if ($s->visible)
                                        <span class="badge badge-phoenix badge-phoenix-success">Visible</span>
                                    @else
                                        <span class="badge badge-phoenix badge-phoenix-secondary">Oculto</span>
                                    @endif
                                    @if ($s->destacado)<span class="badge badge-phoenix badge-phoenix-warning">Destacado</span>@endif
                                </td>
                                <td class="text-end pe-0 text-nowrap">
                                    @if ($s->visible)
                                        <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('sistema', $s->slug) }}" target="_blank" rel="noopener" title="Ver en el sitio"><span class="fa-solid fa-eye"></span></a>
                                    @endif
                                    <a class="btn btn-phoenix-primary btn-sm" href="{{ route('admin.sistemas.edit', $s) }}">Editar</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Categorías del catálogo --}}
    <div class="card mt-4" id="categorias">
        <div class="card-body">
            <h4 class="mb-1">Categorías</h4>
            <p class="text-700 fs--1 mb-3">Agrupan tus sistemas en el catálogo (p. ej. Restaurantes, Comercio, Organizaciones). Al borrar una, sus sistemas quedan sin categoría.</p>
            <div class="row g-2 mb-3">
                @foreach ($categorias as $cat)
                    <div class="col-lg-6">
                        <form class="d-flex gap-2 align-items-center" method="POST" action="{{ route('admin.categorias.update', $cat) }}">
                            @csrf
                            @method('PUT')
                            <span class="{{ $cat->icono ?: 'fa-solid fa-folder' }} text-primary" style="width: 1.25rem"></span>
                            <input class="form-control form-control-sm" name="nombre" value="{{ $cat->nombre }}" required maxlength="80" aria-label="Nombre" />
                            <input class="form-control form-control-sm" name="icono" value="{{ $cat->icono }}" maxlength="60" placeholder="fa-solid fa-…" style="max-width: 10rem" aria-label="Ícono" />
                            <input class="form-control form-control-sm" name="orden" type="number" min="0" value="{{ $cat->orden }}" style="max-width: 4.5rem" aria-label="Orden" />
                            <span class="badge badge-phoenix badge-phoenix-secondary" title="Sistemas">{{ $cat->sistemas_count }}</span>
                            <button class="btn btn-phoenix-primary btn-sm" type="submit" title="Guardar"><span class="fa-solid fa-check"></span></button>
                            <button class="btn btn-phoenix-danger btn-sm" type="submit" form="borrarCat{{ $cat->id }}" title="Eliminar"><span class="fa-solid fa-trash"></span></button>
                        </form>
                        <form id="borrarCat{{ $cat->id }}" method="POST" action="{{ route('admin.categorias.destroy', $cat) }}" onsubmit="return confirm('¿Eliminar esta categoría?')">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                @endforeach
            </div>
            <form class="d-flex flex-wrap gap-2" method="POST" action="{{ route('admin.categorias.store') }}">
                @csrf
                <input class="form-control form-control-sm" name="nombre" required maxlength="80" placeholder="Nueva categoría" style="max-width: 16rem" aria-label="Nueva categoría" />
                <input class="form-control form-control-sm" name="icono" maxlength="60" placeholder="Ícono: fa-solid fa-store" style="max-width: 14rem" aria-label="Ícono" />
                <button class="btn btn-primary btn-sm" type="submit"><span class="fa-solid fa-plus me-1"></span>Agregar</button>
            </form>
        </div>
    </div>
@endsection
