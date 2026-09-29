@extends('publico.cuenta.plantilla', ['tituloPortal' => 'Conversaciones'])

@section('portal')
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card h-100"><div class="card-body">
                <h4 class="mb-3">Tus conversaciones</h4>
                <p class="fs--1 text-700">Todas quedan guardadas aquí, también las cerradas, aunque chatees desde otro equipo.</p>
                @forelse ($conversaciones as $c)
                    @include('publico.cuenta.partes.conversacion')
                @empty
                    <p class="text-700 mb-0">Todavía no has chateado con nosotros.</p>
                @endforelse
                <div class="mt-3">{{ $conversaciones->links() }}</div>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card" id="nueva"><div class="card-body">
                <h4 class="mb-2">Escribirnos</h4>
                @if ($chatActivo)
                    <p class="fs--1 text-700">Empieza una conversación nueva: te respondemos aquí y en el chat del sitio. Si no estamos conectados, te avisamos por correo.</p>
                    <form method="POST" action="{{ route('cuenta.conversaciones.nueva') }}">
                        @csrf
                        <textarea class="form-control mb-3" name="mensaje" rows="4" maxlength="2000" required placeholder="¿En qué te ayudamos?">{{ old('mensaje') }}</textarea>
                        <button class="btn btn-primary w-100" type="submit"><span class="fa-solid fa-paper-plane me-2"></span>Enviar</button>
                    </form>
                @else
                    <p class="fs--1 text-700 mb-3">El chat está apagado en este momento. Escríbenos por el formulario de contacto y te respondemos por correo.</p>
                    <a class="btn btn-phoenix-primary w-100" href="{{ \App\Support\Sitio::enlaceContacto() }}">Ir a contacto</a>
                @endif
            </div></div>
        </div>
    </div>
@endsection
