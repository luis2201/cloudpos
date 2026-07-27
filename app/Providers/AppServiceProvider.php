<?php

namespace App\Providers;

use App\Domain\Access\Models\Role;
use App\Domain\Organization\Models\Company;
use App\Domain\Organization\Models\Establishment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user): ?bool {
            return $user->hasRole(Role::ADMINISTRATOR) ? true : null;
        });

        foreach (array_keys(Role::PERMISSIONS) as $permission) {
            Gate::define($permission, fn (User $user): bool => $user->hasPermission($permission));
        }

        View::composer('layouts.app', function ($view): void {
            $company = Schema::hasTable('companies') ? Company::query()->first() : null;
            $establishment = Schema::hasTable('establishments')
                ? Establishment::query()->where('is_main', true)->first()
                : null;

            $view->with(compact('company', 'establishment'));
        });
    }
}
