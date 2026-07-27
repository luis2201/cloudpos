<?php

namespace App\Http\Controllers\Setup;

use App\Domain\Access\Models\Role;
use App\Domain\Setup\OnboardingManager;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FirstAdminController extends Controller
{
    public function create(OnboardingManager $onboarding): View|RedirectResponse
    {
        if (User::query()->exists()) {
            return to_route(Auth::check() ? $onboarding->nextRouteName() : 'login');
        }

        return view('setup.admin');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $administrator = Role::query()->firstOrCreate(
                ['slug' => Role::ADMINISTRATOR],
                [
                    'name' => 'Administrador',
                    'description' => 'Acceso total y protegido a la configuración de CloudPOS.',
                    'permissions' => ['*'],
                    'is_system' => true,
                ],
            );

            $administrator = Role::query()->whereKey($administrator->getKey())->lockForUpdate()->firstOrFail();

            if (User::query()->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'El administrador inicial ya fue creado. Inicia sesión para continuar.',
                ]);
            }

            $user = User::query()->create([...$validated, 'is_active' => true]);
            $user->roles()->attach($administrator);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('setup.company.create')
            ->with('success', 'Administrador creado. Ahora registra los datos tributarios del negocio.');
    }
}
