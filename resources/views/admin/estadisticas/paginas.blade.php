{{-- Páginas más vistas con barra proporcional. $corto: sin columnas de detalle. --}}
@if ($paginas->isEmpty())
    <p class="text-700 fs--1 mb-0">Todavía no hay visitas en este periodo. Se empiezan a contar desde que se instaló el contador.</p>
@else
    @php $maximo = max(1, $paginas->max('vistas')); @endphp
    <div class="table-responsive">
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-0">Página</th>
                    @unless ($corto)<th>Tipo</th>@endunless
                    <th class="text-end">Vistas</th>
                    <th class="text-end pe-0">Visitantes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($paginas as $p)
                    <tr>
                        <td class="ps-0" style="min-width: 12rem;">
                            <a class="fw-semi-bold text-900" href="{{ url($p->ruta) }}" target="_blank" rel="noopener">{{ $p->titulo ?: $p->ruta }}</a>
                            <div class="progress mt-1" style="height: 4px;"><div class="progress-bar" style="width: {{ round($p->vistas / $maximo * 100) }}%"></div></div>
                            @unless ($corto)<div class="text-600 fs--2 mt-1">{{ $p->ruta }}</div>@endunless
                        </td>
                        @unless ($corto)<td>{{ ['pagina' => 'Página', 'sistema' => 'Sistema', 'manual' => 'Manual'][$p->tipo] ?? $p->tipo }}</td>@endunless
                        <td class="text-end">{{ number_format($p->vistas) }}</td>
                        <td class="text-end pe-0">{{ number_format($p->visitantes) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
