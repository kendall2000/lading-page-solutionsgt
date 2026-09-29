<?php

namespace App\Http\Controllers;

use App\Models\CategoriaSistema;
use App\Models\Cliente;
use App\Models\Direccion;
use App\Models\Manual;
use App\Models\MensajeContacto;
use App\Models\Pagina;
use App\Models\Servicio;
use App\Models\Sistema;
use App\Rules\PocosEnlaces;
use App\Services\Correos;
use App\Support\Antispam;
use App\Support\Sitio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Sitio público: páginas armadas con secciones, detalle de sistemas y manuales, y formulario de solicitudes. */
class SitioController extends Controller
{
    public function inicio(): View
    {
        $pagina = Pagina::query()->publicas()->where('es_inicio', true)->first()
            ?? Pagina::query()->publicas()->orderBy('orden')->first();
        abort_unless($pagina, 404);

        return $this->mostrar($pagina);
    }

    public function pagina(Pagina $pagina): View|RedirectResponse
    {
        abort_unless($pagina->visible, 404);
        if ($pagina->es_inicio) {
            return redirect()->route('inicio');
        }

        return $this->mostrar($pagina);
    }

    public function sistema(Sistema $sistema): View
    {
        abort_unless($sistema->visible, 404);
        $sistema->increment('visitas');

        return view('publico.sistema', [
            'cfg' => Sitio::config(),
            'sistema' => $sistema->load(['imagenes', 'categoria', 'manuales' => fn ($q) => $q->where('visible', true)]),
            'clientes' => $sistema->clientes()->where('mostrar_testimonio', true)->whereNotNull('testimonio')->get(),
            'otros' => Sistema::query()->publicos()->whereKeyNot($sistema->id)
                ->orderByRaw('categoria_id = ? desc', [$sistema->categoria_id ?? 0])->limit(3)->get(),
        ]);
    }

    public function manual(Manual $manual): View|RedirectResponse
    {
        abort_unless($manual->visible, 404);
        // Manual «solo para clientes»: hay que entrar con una cuenta que tenga ese sistema vigente.
        if ($manual->solo_clientes) {
            $cuenta = Auth::guard('cliente')->user();
            if (! $cuenta) {
                return redirect()->guest(route('cuenta.entrar'))->with('status', 'Este manual es exclusivo para clientes. Entra con tu cuenta para verlo.');
            }
            abort_unless($cuenta->puedeVerManual($manual), 403, 'Este manual es solo para clientes con ese sistema contratado.');
        }
        $manual->increment('visitas');

        return view('publico.manual', [
            'cfg' => Sitio::config(),
            'manual' => $manual->load('sistema'),
            'otros' => Manual::query()->publicos()->whereKeyNot($manual->id)
                ->when($manual->sistema_id, fn ($q) => $q->where('sistema_id', $manual->sistema_id))->limit(8)->get(),
        ]);
    }

    public function contacto(Request $request, Correos $correos): RedirectResponse
    {
        $volver = fn () => redirect()->to(url()->previous().'#'.($request->input('ancla') === 'solicitar' ? 'solicitar' : 'contacto'));

        // Campo trampa: los robots lo llenan, las personas no lo ven.
        if (filled($request->input('empresa_web'))) {
            return $volver()->with('contacto_ok', true);
        }

        $request->mergeIfMissing(['tipo' => 'contacto']);
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(array_keys(MensajeContacto::TIPOS))],
            'nombre' => ['required', 'string', 'max:120', new PocosEnlaces(0)],
            'empresa' => ['nullable', 'string', 'max:150', new PocosEnlaces(0)],
            'correo' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'sistema_id' => ['nullable', 'required_if:tipo,demo,prueba', 'exists:sistemas,id'],
            // Un mensaje general necesita texto; si es sobre un sistema, se completa solo.
            'mensaje' => ['nullable', Rule::requiredIf(fn () => $request->input('tipo') === 'contacto' && ! $request->filled('sistema_id')), 'string', 'max:3000', new PocosEnlaces],
        ], [
            'sistema_id.required_if' => 'Elige el sistema que quieres conocer.',
            'mensaje.required' => 'Escribe tu mensaje.',
        ], ['nombre' => 'nombre', 'correo' => 'correo', 'mensaje' => 'mensaje', 'telefono' => 'teléfono']);

        // Enviado sin pasar por el formulario o en menos segundos de lo que tarda una persona.
        if (Antispam::muyRapido($request->input('llegada'))) {
            throw ValidationException::withMessages(['formulario' => Antispam::MENSAJE_RAPIDO]);
        }

        $sistema = isset($datos['sistema_id']) ? Sistema::query()->find($datos['sistema_id']) : null;
        // Una prueba solo se pide si el sistema la ofrece; si no, queda como demostración.
        if ($datos['tipo'] === 'prueba' && ! $sistema?->ofrecePrueba()) {
            $datos['tipo'] = 'demo';
        }
        // Un sistema en desarrollo no tiene demo: la solicitud queda como «avísenme cuando esté listo».
        if ($sistema?->proximamente && $datos['tipo'] !== 'contacto') {
            $datos['tipo'] = 'contacto';
        }
        if (blank($datos['mensaje'] ?? null)) {
            $datos['mensaje'] = match (true) {
                $datos['tipo'] === 'prueba' => "Quiero probar {$sistema->nombre} por {$sistema->dias_prueba} días.",
                $datos['tipo'] === 'demo' => "Quiero una demostración de {$sistema?->nombre}.",
                (bool) $sistema?->proximamente => "Avísenme cuando {$sistema->nombre} esté disponible.",
                default => "Quiero información de {$sistema?->nombre}.",
            };
        }

        // El mismo mensaje del mismo correo hace poco (doble clic, recarga o robot): no se repite ni se avisa otra vez.
        $repetido = MensajeContacto::query()->where('correo', $datos['correo'])->where('mensaje', $datos['mensaje'])
            ->where('created_at', '>=', now()->subMinutes(10))->exists();
        if ($repetido) {
            return $volver()->with('contacto_ok', $datos['tipo']);
        }

        // Cliente con sesión y correo confirmado: la solicitud queda en su cuenta.
        $cuenta = Auth::guard('cliente')->user();
        $cuentaId = $cuenta?->verificada() && $cuenta->activa ? $cuenta->id : null;

        $mensaje = MensajeContacto::query()->create($datos + ['ip' => $request->ip(), 'cuenta_id' => $cuentaId]);

        // Correos con plantilla (Panel → Correos): aviso a «Avisos a» y confirmación al visitante.
        // Si el servidor está apagado o falla, la solicitud igual queda en el panel y el intento en la bitácora.
        $correos->avisar('nuevo_mensaje', [
            'tipo' => $mensaje->tipoInfo()[2], 'nombre' => $mensaje->nombre, 'empresa' => $mensaje->empresa ?: '—',
            'correo' => $mensaje->correo, 'telefono' => $mensaje->telefono ?: '—', 'sistema_interes' => $sistema?->nombre ?? '—',
            'mensaje' => $mensaje->mensaje, 'enlace' => route('admin.mensajes.show', $mensaje),
        ], $mensaje->correo);
        $correos->enviar('confirmacion_contacto', $mensaje->correo, [
            'nombre' => $mensaje->nombre, 'mensaje' => $mensaje->mensaje, 'enlace' => route('inicio'),
        ]);

        return $volver()->with('contacto_ok', $mensaje->tipo);
    }

    private function mostrar(Pagina $pagina): View
    {
        $secciones = $pagina->secciones()->where('visible', true)
            ->with(['elementos' => fn ($q) => $q->where('visible', true)])->get();

        return view('publico.pagina', [
            'cfg' => Sitio::config(),
            'pagina' => $pagina,
            'secciones' => $secciones,
            'datos' => $this->datos($secciones->pluck('tipo')->unique()),
            'titulo' => $pagina->es_inicio ? null : $pagina->titulo,
            'descripcion' => $pagina->meta_descripcion,
            'imagenOg' => $pagina->urlEncabezado(),
        ]);
    }

    /** Solo se consulta lo que usan las secciones de la página. */
    private function datos(Collection $tipos): array
    {
        $usa = fn (string ...$t) => $tipos->intersect($t)->isNotEmpty();
        $clientes = $usa('testimonios', 'clientes') ? Cliente::query()->orderBy('orden')->orderBy('empresa')->get() : collect();

        return [
            'sistemas' => $usa('sistemas', 'contacto') ? Sistema::query()->publicos()->with('categoria')->get() : collect(),
            'categorias' => $usa('sistemas') ? CategoriaSistema::query()->orderBy('orden')->orderBy('nombre')->get() : collect(),
            'servicios' => $usa('planes') ? Servicio::query()->publicos()->get() : collect(),
            'testimonios' => $clientes->where('mostrar_testimonio', true)->filter(fn ($c) => filled($c->testimonio))->values(),
            'logos' => $clientes->where('mostrar_logo', true)->filter(fn ($c) => $c->logo)->values(),
            'direcciones' => $usa('contacto') ? Direccion::query()->publicas()->get() : collect(),
            'manuales' => $usa('manuales') ? Manual::query()->publicos()->with('sistema')->get() : collect(),
        ];
    }
}
