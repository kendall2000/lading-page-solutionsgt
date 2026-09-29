<tr>
    <td class="ps-0 text-nowrap">
        @unless ($p->es_inicio)
            @foreach (['arriba' => 'up', 'abajo' => 'down'] as $dir => $flecha)
                <form class="d-inline" method="POST" action="{{ route('admin.paginas.mover', [$p, $dir]) }}">
                    @csrf
                    <button class="btn btn-link p-0 px-1 text-600" type="submit" title="Mover {{ $dir }}"><span class="fa-solid fa-arrow-{{ $flecha }}"></span></button>
                </form>
            @endforeach
        @endunless
    </td>
    <td>
        <div class="d-flex align-items-center {{ $nivel ? 'ps-4' : '' }}">
            @if ($nivel)<span class="fa-solid fa-turn-up fa-rotate-90 text-400 me-2"></span>@endif
            <a class="fw-bold text-1000" href="{{ route('admin.paginas.edit', $p) }}">{{ $p->titulo }}</a>
            @if ($p->es_inicio)<span class="badge badge-phoenix badge-phoenix-primary ms-2">Inicio</span>@endif
            @if ($p->titulo_menu && $p->titulo_menu !== $p->titulo)<span class="text-600 ms-2">(menú: {{ $p->titulo_menu }})</span>@endif
        </div>
    </td>
    <td class="text-700">/{{ $p->es_inicio ? '' : $p->slug }}</td>
    <td class="text-center">{{ $p->secciones_count }}</td>
    <td>
        <span class="badge badge-phoenix badge-phoenix-{{ $p->visible ? 'success' : 'secondary' }}">{{ $p->visible ? 'Publicada' : 'Oculta' }}</span>
        @unless ($p->en_menu)<span class="badge badge-phoenix badge-phoenix-warning">Fuera del menú</span>@endunless
    </td>
    <td class="text-end pe-0 text-nowrap">
        @if ($p->visible)
            <a class="btn btn-phoenix-secondary btn-sm" href="{{ $p->enlace() }}" target="_blank" rel="noopener" title="Ver en el sitio"><span class="fa-solid fa-eye"></span></a>
        @endif
        @if (! $nivel && ! $p->es_inicio)
            <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('admin.paginas.create', ['padre' => $p->id]) }}" title="Agregar subpágina"><span class="fa-solid fa-plus"></span></a>
        @endif
        <a class="btn btn-phoenix-primary btn-sm" href="{{ route('admin.paginas.edit', $p) }}">Editar</a>
    </td>
</tr>
