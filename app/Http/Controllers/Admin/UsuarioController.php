<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Fortify\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Seguridad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Personas que pueden entrar al panel. */
class UsuarioController extends Controller
{
    use PasswordValidationRules;

    public function index(): View
    {
        return view('admin.usuarios.index', ['usuarios' => User::query()->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.usuarios.form', ['usuario' => new User(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        User::query()->create($datos);

        return redirect()->route('admin.usuarios.index')->with('status', 'Usuario creado.');
    }

    public function edit(User $usuario): View
    {
        return view('admin.usuarios.form', ['usuario' => $usuario]);
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $datos = $this->validar($request, $usuario);
        if ($usuario->is($request->user()) && ! $datos['is_active']) {
            return back()->withErrors(['is_active' => 'No puedes desactivar tu propio usuario.']);
        }
        if (blank($datos['password'] ?? null)) {
            unset($datos['password']);
        }
        $usuario->update($datos);
        if (! $usuario->is_active || isset($datos['password'])) {
            Seguridad::cerrarSesiones($usuario->id, $usuario->is($request->user()) ? $request->session()->getId() : null);
        }

        return redirect()->route('admin.usuarios.index')->with('status', 'Usuario guardado.');
    }

    private function validar(Request $request, ?User $usuario = null): array
    {
        $request->merge(['email' => Str::lower((string) $request->input('email'))]);
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'password' => $usuario ? ['nullable', ...array_slice($this->passwordRules(), 1)] : $this->passwordRules(),
        ], [], ['name' => 'nombre', 'email' => 'correo', 'password' => 'contraseña']);

        return $datos + ['is_active' => $request->boolean('is_active')];
    }
}
