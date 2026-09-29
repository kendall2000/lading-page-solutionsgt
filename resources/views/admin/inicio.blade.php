@extends('layouts.admin', ['titulo' => 'Inicio'])

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-5">
        <div>
            <h2 class="mb-1 text-1100">Hola, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</h2>
            <p class="text-700 mb-0">Resumen de tu sitio {{ \App\Support\Sitio::nombre() }}.</p>
        </div>
        <a class="btn btn-phoenix-primary" href="{{ route('inicio') }}" target="_blank" rel="noopener"><span class="fa-solid fa-arrow-up-right-from-square me-2"></span>Ver sitio</a>
    </div>

    @php
        $tarjetas = [
            ['Mensajes nuevos', $totales['nuevos'], 'mail', 'primary', route('admin.mensajes.index', ['estado' => 'nuevo'])],
            ['Sistemas', $totales['sistemas'], 'grid', 'info', route('admin.sistemas.index')],
            ['Clientes', $totales['clientes'], 'users', 'success', route('admin.clientes.index')],
            ['Servicios', $totales['servicios'], 'tag', 'warning', route('admin.servicios.index')],
        ];
    @endphp
    <div class="row g-3 mb-5">
        @foreach ($tarjetas as [$texto, $valor, $icono, $color, $enlace])
            <div class="col-6 col-xl-3">
                <a class="card h-100 text-decoration-none" href="{{ $enlace }}">
                    <div class="card-body d-flex align-items-center">
                        <div class="d-flex flex-center rounded-circle bg-soft-{{ $color }} me-3" style="width:3rem;height:3rem;">
                            <span class="text-{{ $color }}" data-feather="{{ $icono }}"></span>
                        </div>
                        <div>
                            <h3 class="mb-0 text-1000">{{ $valor }}</h3>
                            <p class="text-700 fs--1 mb-0">{{ $texto }}</p>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="mb-1">Mensajes recibidos</h4>
                    <p class="text-700 fs--1 mb-3">Últimos 30 días</p>
                    <div id="graficaMensajes" style="min-height: 280px;"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="mb-3">Sistemas más vistos</h4>
                    @forelse ($masVistos as $s)
                        <div class="d-flex justify-content-between align-items-center py-2 {{ $loop->last ? '' : 'border-bottom border-200' }}">
                            <a class="text-900 fw-semi-bold" href="{{ route('admin.sistemas.edit', $s) }}">{{ $s->nombre }}</a>
                            <span class="badge badge-phoenix badge-phoenix-secondary">{{ number_format($s->visitas) }} visitas</span>
                        </div>
                    @empty
                        <p class="text-700 fs--1">Todavía no hay sistemas.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Últimos mensajes</h4>
                        <a class="fs--1 fw-bold" href="{{ route('admin.mensajes.index') }}">Ver todos</a>
                    </div>
                    @include('admin.mensajes.tabla', ['mensajes' => $ultimos])
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendors')
    <script src="{{ asset('vendors/echarts/echarts.min.js') }}"></script>
@endpush
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var el = document.getElementById('graficaMensajes');
            var grafica = echarts.init(el);
            var estilo = getComputedStyle(document.documentElement);
            var primario = estilo.getPropertyValue('--phoenix-primary').trim() || '#3874ff';
            var texto = estilo.getPropertyValue('--phoenix-gray-600').trim() || '#8a94ad';
            grafica.setOption({
                grid: { left: 30, right: 10, top: 10, bottom: 30 },
                tooltip: { trigger: 'axis' },
                xAxis: { type: 'category', data: @json($grafica['dias']), axisLabel: { color: texto }, axisLine: { show: false }, axisTick: { show: false } },
                yAxis: { type: 'value', minInterval: 1, axisLabel: { color: texto }, splitLine: { lineStyle: { type: 'dashed', opacity: .4 } } },
                series: [{ name: 'Mensajes', type: 'bar', data: @json($grafica['totales']), itemStyle: { color: primario, borderRadius: [4, 4, 0, 0] }, barMaxWidth: 18 }],
            });
            window.addEventListener('resize', function () { grafica.resize(); });
        });
    </script>
@endpush
