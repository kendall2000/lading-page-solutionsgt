<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Models\Sistema;
use App\Support\Imagenes;
use App\Support\Orden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/** Manuales de los sistemas: guía escrita, PDF (Contabo) y/o video. */
class ManualController extends Controller
{
    public function index(Request $request): View
    {
        $sistema = $request->integer('sistema') ?: null;

        return view('admin.manuales.index', [
            'manuales' => Manual::query()->with('sistema')->when($sistema, fn ($q) => $q->where('sistema_id', $sistema))
                ->orderBy('sistema_id')->orderBy('orden')->orderBy('titulo')->get(),
            'sistemas' => Sistema::query()->orderBy('nombre')->pluck('nombre', 'id'),
            'sistema' => $sistema,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.manuales.form', [
            'manual' => new Manual(['visible' => true, 'sistema_id' => $request->integer('sistema') ?: null]),
            'sistemas' => Sistema::query()->orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        if (! $request->filled('orden')) {
            $datos['orden'] = Orden::siguiente(Manual::query()->where('sistema_id', $datos['sistema_id'] ?? null));
        }
        $manual = Manual::query()->create($datos);
        $this->guardarArchivo($request, $manual);

        return redirect()->route('admin.manuales.edit', $manual)->with('status', 'Manual creado.');
    }

    public function edit(Manual $manual): View
    {
        return view('admin.manuales.form', ['manual' => $manual, 'sistemas' => Sistema::query()->orderBy('nombre')->pluck('nombre', 'id')]);
    }

    public function update(Request $request, Manual $manual): RedirectResponse
    {
        $manual->update($this->validar($request, $manual));
        $this->guardarArchivo($request, $manual);

        return back()->with('status', 'Manual guardado.');
    }

    public function destroy(Manual $manual): RedirectResponse
    {
        $manual->delete();
        Imagenes::borrar($manual->archivo);

        return redirect()->route('admin.manuales.index')->with('status', 'Manual eliminado.');
    }

    /** PDF en Contabo (mismo disco que las imágenes). */
    private function guardarArchivo(Request $request, Manual $manual): void
    {
        try {
            $anterior = $manual->archivo;
            if ($request->hasFile('archivo')) {
                $manual->update(['archivo' => Imagenes::subir($request->file('archivo'), 'manuales')]);
            } elseif ($request->boolean('quitar_archivo')) {
                $manual->update(['archivo' => null]);
            } else {
                return;
            }
            Imagenes::borrar($anterior);
        } catch (Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['archivo' => 'El manual se guardó, pero el PDF no se pudo subir a Contabo. Intenta de nuevo.']);
        }
    }

    private function validar(Request $request, ?Manual $manual = null): array
    {
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('titulo'))]);

        $datos = $request->validate([
            'sistema_id' => ['nullable', 'exists:sistemas,id'],
            'titulo' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:170', Rule::unique('manuales', 'slug')->ignore($manual?->id)],
            'resumen' => ['nullable', 'string', 'max:300'],
            'contenido' => ['nullable', 'string', 'max:200000'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'archivo' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ], [
            'slug.unique' => 'Ya hay otro manual con esa dirección.',
            'archivo.mimes' => 'El archivo debe ser PDF.',
            'archivo.max' => 'El PDF no puede pasar de 20 MB.',
        ]);

        return array_merge(collect($datos)->except('archivo')->all(), [
            'orden' => (int) ($datos['orden'] ?? $manual?->orden ?? 0),
            'visible' => $request->boolean('visible'),
            'solo_clientes' => $request->boolean('solo_clientes'),
        ]);
    }
}
