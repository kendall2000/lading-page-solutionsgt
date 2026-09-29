{{-- Tarjetas de cifras con variación (diseño dashboard/crm.html). Cada una: icono, color, texto, valor, var [texto, color], nota. --}}
<div class="row g-3 mb-4">
    @foreach ($tarjetas as $t)
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="d-flex flex-center rounded-circle bg-soft-{{ $t['color'] }} me-2" style="width:2.5rem;height:2.5rem;">
                            <span class="text-{{ $t['color'] }}" data-feather="{{ $t['icono'] }}" style="width:18px;height:18px;"></span>
                        </div>
                        <p class="text-700 fs--1 mb-0">{{ $t['texto'] }}</p>
                    </div>
                    <p class="text-{{ $t['color'] }} fs-2 fw-bold mb-1">{{ $t['valor'] }}</p>
                    @if ($t['var'])
                        <span class="badge badge-phoenix badge-phoenix-{{ $t['var'][1] }} fs--2">{{ $t['var'][0] }}</span>
                        <span class="fs--2 text-600 ms-1">vs. periodo anterior</span>
                    @endif
                    @if ($t['nota'])
                        <p class="fs--2 text-600 mb-0 mt-1">{{ $t['nota'] }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
