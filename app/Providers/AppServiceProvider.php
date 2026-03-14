<?php

namespace App\Providers;

use App\Models\Debt;
use App\Models\RecurringTemplate;
use App\Models\User;
use App\Observers\DebtObserver;
use App\Observers\RecurringTemplateObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Pennant\Feature;

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
        $this->configureDefaults();
        $this->registerObservers();
        $this->defineFeatureFlags();
    }

    private function registerObservers(): void
    {
        Debt::observe(DebtObserver::class);
        RecurringTemplate::observe(RecurringTemplateObserver::class);
    }

    private function defineFeatureFlags(): void
    {
        Feature::define('unlimited_transactions', fn (User $user) => $user->subscribed());
        Feature::define('multiple_workspaces', fn (User $user) => $user->subscribed());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
