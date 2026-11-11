{{-- Insignia del estado FEL de un documento tributario ($d). --}}
@switch($d->estado)
    @case('certificado')
        <span class="badge badge-phoenix badge-phoenix-success"><span class="fa-solid fa-circle-check me-1"></span>Certificada</span>
        @break
    @case('anulado')
        <span class="badge badge-phoenix badge-phoenix-secondary"><span class="fa-solid fa-ban me-1"></span>Anulada</span>
        @break
    @case('error')
        <span class="badge badge-phoenix badge-phoenix-danger"><span class="fa-solid fa-triangle-exclamation me-1"></span>Error SAT</span>
        @break
    @case('pendiente')
        <span class="badge badge-phoenix badge-phoenix-warning"><span class="fa-solid fa-clock me-1"></span>Pendiente</span>
        @break
    @default
        <span class="badge badge-phoenix badge-phoenix-info">Emitida</span>
@endswitch
