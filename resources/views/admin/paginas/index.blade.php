@extends('layouts.admin', ['titulo' => 'Páginas y menú'])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 text-1100">Páginas y menú</h2>
            <p class="text-700 mb-0">Cada página aparece en el menú del sitio y se arma con las secciones que tú elijas. Las flechas cambian el orden del menú.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.paginas.create') }}"><span class="fa-solid fa-plus me-2"></span>Nueva página</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm fs--1 mb-0 align-middle">
                    <thead>
                    <tr>
                        <th class="ps-0" style="width: 70px">Orden</th>
                        <th>Página</th>
                        <th>Dirección</th>
                        <th class="text-center">Secciones</th>
                        <th>Estado</th>
                        <th class="text-end pe-0"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($principales as $p)
                        @include('admin.paginas.fila', ['p' => $p, 'nivel' => 0])
                        @foreach ($hijas[$p->id] ?? [] as $h)
                            @include('admin.paginas.fila', ['p' => $h, 'nivel' => 1])
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <p class="fs--1 text-600 mt-3"><span class="fa-solid fa-circle-info me-1"></span>Para un submenú, al crear o editar una página elige «Va dentro de». Por ejemplo, «Diseño web» dentro de «Servicios».</p>
@endsection
