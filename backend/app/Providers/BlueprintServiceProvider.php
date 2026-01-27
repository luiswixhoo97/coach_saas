<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;

class BlueprintServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Macro para agregar timestamps + softDeletes en una sola llamada
        if (! Blueprint::hasMacro('auditable')) {
            Blueprint::macro('auditable', function (): void {
                $this->timestamps();      // created_at, updated_at
                $this->softDeletes();     // deleted_at
            });
        }

        if (! Blueprint::hasMacro('dropAuditable')) {
            Blueprint::macro('dropAuditable', function (): void {
                $this->dropTimestamps();
                $this->dropSoftDeletes();
            });
        }
    }
}
