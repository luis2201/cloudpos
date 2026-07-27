<?php

namespace App\Http\Middleware;

use App\Domain\Setup\OnboardingManager;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemConfigured
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $nextRoute = app(OnboardingManager::class)->nextRouteName();

        if ($nextRoute !== 'dashboard') {
            return to_route($nextRoute);
        }

        return $next($request);
    }
}
