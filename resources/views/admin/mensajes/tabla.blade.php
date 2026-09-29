@if ($mensajes->isEmpty())
    <p class="text-700 fs--1 mb-0">No hay mensajes.</p>
@else
    <div class="table-responsive">
        <table class="table table-sm fs--1 mb-0 align-middle">
            <thead>
            <tr>
                <th class="ps-0">Fecha</th>
                <th>Tipo</th>
                <th>Nombre</th>
                <th>Contacto</th>
                <th>Sistema</th>
                <th>Estado</th>
                <th class="text-end pe-0"></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($mensajes as $m)
                @php [$textoEstado, $colorEstado] = \App\Models\MensajeContacto::ESTADOS[$m->estado] ?? [$m->estado, 'secondary']; @endphp
                <tr class="{{ $m->estado === 'nuevo' ? 'fw-bold' : '' }}">
                    <td class="ps-0 text-nowrap">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        @php [$textoTipo, $colorTipo] = $m->tipoInfo(); @endphp
                        <span class="badge badge-phoenix badge-phoenix-{{ $colorTipo }}">{{ $textoTipo }}</span>
                        @if ($m->credenciales_enviadas_en)<span class="fa-solid fa-key text-success ms-1" title="Credenciales enviadas"></span>@endif
                    </td>
                    <td>
                        {{ $m->nombre }}
                        @if ($m->empresa)<div class="text-700 fw-normal fs--2">{{ $m->empresa }}</div>@endif
                    </td>
                    <td>
                        <a href="mailto:{{ $m->correo }}">{{ $m->correo }}</a>
                        @if ($m->telefono)<div class="fw-normal fs--2">{{ $m->telefono }}</div>@endif
                    </td>
                    <td>{{ $m->sistema?->nombre ?? '—' }}</td>
                    <td><span class="badge badge-phoenix badge-phoenix-{{ $colorEstado }}">{{ $textoEstado }}</span></td>
                    <td class="text-end pe-0"><a class="btn btn-phoenix-secondary btn-sm" href="{{ route('admin.mensajes.show', $m) }}">Abrir</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
