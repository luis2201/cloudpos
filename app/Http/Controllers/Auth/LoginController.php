<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Setup\OnboardingManager;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(OnboardingManager $onboarding): View|RedirectResponse
    {
        if (! User::query()->exists()) {
            return to_route('setup.admin.create');
        }

        if (Auth::check()) {
            return to_route($onboarding->nextRouteName());
        }

        return view('auth.login');
    }

    public function store(Request $request, OnboardingManager $onboarding): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son válidas o el usuario está inactivo.',
            ]);
        }

        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route($onboarding->nextRouteName()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
