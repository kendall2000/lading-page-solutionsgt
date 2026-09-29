<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Sistema;
use App\Support\Imagenes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Clientes: datos internos de contacto y, si se permite, su logo y testimonio en el sitio. */
class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar'));

        return view('admin.clientes.index', [
            'clientes' => Cliente::query()->with('sistema')
                ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w->where('empresa', 'like', "%{$buscar}%")
                    ->orWhere('contacto', 'like', "%{$buscar}%")->orWhere('correo', 'like', "%{$buscar}%")))
                ->orderBy('orden')->orderBy('empresa')->paginate(20)->withQueryString(),
            'buscar' => $buscar,
        ]);
    }

    public function create(): View
    {
        return view('admin.clientes.form', ['cliente' => new Cliente(['mostrar_logo' => true, 'calificacion' => 5]), 'sistemas' => $this->sistemas()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cliente = Cliente::query()->create($this->validar($request));
        Imagenes::guardarCampos($request, $cliente, ['logo', 'foto'], 'clientes');

        return redirect()->route('admin.clientes.index')->with('status', 'Cliente creado.');
    }

    public function edit(Cliente $cliente): View
    {
        return view('admin.clientes.form', ['cliente' => $cliente, 'sistemas' => $this->sistemas()]);
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($this->validar($request));
        Imagenes::guardarCampos($request, $cliente, ['logo', 'foto'], 'clientes');

        return redirect()->route('admin.clientes.index')->with('status', 'Cliente guardado.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $cliente->delete();
        Imagenes::borrar($cliente->logo);
        Imagenes::borrar($cliente->foto);

        return redirect()->route('admin.clientes.index')->with('status', 'Cliente eliminado.');
    }

    private function sistemas()
    {
        return Sistema::query()->orderBy('nombre')->pluck('nombre', 'id');
    }

    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'empresa' => ['required', 'string', 'max:150'],
            'contacto' => ['nullable', 'string', 'max:120'],
            'cargo' => ['nullable', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:150'],
            'sitio_web' => ['nullable', 'url', 'max:255'],
            'sistema_id' => ['nullable', 'exists:sistemas,id'],
            'testimonio' => ['nullable', 'string', 'max:1000'],
            'calificacion' => ['nullable', 'integer', 'min:1', 'max:5'],
            'notas' => ['nullable', 'string', 'max:3000'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'logo' => Imagenes::regla(2048),
            'foto' => Imagenes::regla(2048),
        ], Imagenes::MENSAJES);

        return array_merge(collect($datos)->except(['logo', 'foto'])->all(), [
            'calificacion' => (int) ($datos['calificacion'] ?? 5),
            'orden' => (int) ($datos['orden'] ?? 0),
            'mostrar_logo' => $request->boolean('mostrar_logo'),
            'mostrar_testimonio' => $request->boolean('mostrar_testimonio'),
        ]);
    }
}
