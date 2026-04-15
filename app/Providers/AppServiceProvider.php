<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

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
        // Désactive la mise en minuscule automatique des en-têtes Excel
        // pour que 'FACTURE', 'CODE_CLIENT', etc. soient lus tels quels.
        HeadingRowFormatter::default('none');
    }
}
