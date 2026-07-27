<?php

namespace App\Http\Controllers\Access;

use App\Domain\Access\Models\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());

        return view('access.users.index', [
            'roles' => Role::query()->orderByDesc('is_system')->orderBy('name')->get(),
            'search' => $search,
            'users' => User::query()
                ->with('roles')
                ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        DB::transaction(function () use ($validated): void {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => $validated['password'],
                'is_active' => true,
            ]);
            $user->roles()->sync($validated['role_ids']);
        });

        return back()->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'confirmed', Password::min(10)->letters()->numbers()],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $administratorRole = Role::query()->where('slug', Role::ADMINISTRATOR)->firstOrFail();
        $removesAdministratorRole = $user->hasRole(Role::ADMINISTRATOR)
            && ! in_array($administratorRole->getKey(), $validated['role_ids'], false);

        if ($user->is_active && $removesAdministratorRole && $this->activeAdministratorCount() <= 1) {
            throw ValidationException::withMessages([
                'role_ids' => 'Debe permanecer al menos un administrador activo.',
            ]);
        }

        DB::transaction(function () use ($user, $validated): void {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                ...(filled($validated['password'] ?? null) ? ['password' => $validated['password']] : []),
            ]);
            $user->roles()->sync($validated['role_ids']);
        });

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['user' => 'No puedes desactivar tu propia cuenta.']);
        }

        if ($user->is_active && $user->hasRole(Role::ADMINISTRATOR)) {
            if ($this->activeAdministratorCount() <= 1) {
                return back()->withErrors(['user' => 'Debe permanecer al menos un administrador activo.']);
            }
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active ? 'Usuario activado.' : 'Usuario desactivado.');
    }

    private function activeAdministratorCount(): int
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('slug', Role::ADMINISTRATOR))
            ->count();
    }
}
