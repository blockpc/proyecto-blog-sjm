<?php

declare(strict_types=1);

namespace Blockpc\App\Providers;

use Blockpc\App\Commands\SyncPermissionsCommand;
use Blockpc\App\Commands\SyncRolesCommand;
use Blockpc\App\Mixins\Search;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

class BlockpcServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Builder::mixin(new Search);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerConsoleCommands();
    }

    /**
     * Register console commands when running in console.
     */
    private function registerConsoleCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncPermissionsCommand::class,
                SyncRolesCommand::class,
            ]);
        }
    }
}
