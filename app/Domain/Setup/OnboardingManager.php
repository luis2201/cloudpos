<?php

namespace App\Domain\Setup;

use App\Domain\Organization\Models\Company;
use App\Domain\Organization\Models\Establishment;
use App\Domain\Organization\Models\Warehouse;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class OnboardingManager
{
    public function nextRouteName(): string
    {
        if (! User::query()->exists()) {
            return 'setup.admin.create';
        }

        if (! Auth::check()) {
            return 'login';
        }

        if (! Company::query()->exists()) {
            return 'setup.company.create';
        }

        if (! Establishment::query()->exists()) {
            return 'setup.establishment.create';
        }

        if (! Warehouse::query()->exists()) {
            return 'setup.warehouse.create';
        }

        return 'dashboard';
    }

    public function isComplete(): bool
    {
        return $this->nextRouteName() === 'dashboard';
    }

    public function completedSteps(): int
    {
        return (int) User::query()->exists()
            + (int) Company::query()->exists()
            + (int) Establishment::query()->exists()
            + (int) Warehouse::query()->exists();
    }
}
