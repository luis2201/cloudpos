<?php

namespace App\Http\Controllers\Access;

use App\Domain\Access\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('access.roles.index', [
            'availablePermissions' => Role::PERMISSIONS,
            'roles' => Role::query()->withCount('users')->orderByDesc('is_system')->orderBy('name')->paginate(10),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedRole($request);
        $slug = Role::slugFrom($validated['name']);

        if ($slug === '' || Role::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Ya existe un rol con ese nombre o identificador.',
            ]);
        }

        Role::query()->create([
            ...$validated,
            'slug' => $slug,
            'permissions' => array_values($validated['permissions']),
            'is_system' => false,
        ]);

        return back()->with('success', 'Rol creado correctamente.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->is_system, 403, 'Los roles del sistema no se pueden modificar.');
        $validated = $this->validatedRole($request);

        $role->update([
            ...$validated,
            'permissions' => array_values($validated['permissions']),
        ]);

        return back()->with('success', 'Rol actualizado correctamente.');
    }

    /** @return array<string, mixed> */
    private function validatedRole(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(array_keys(Role::PERMISSIONS))],
        ]);
    }
}
