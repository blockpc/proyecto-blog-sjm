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
     * Adds the Search mixin to Eloquent's query builder so its methods are available on all Eloquent queries.
     */
    public function register(): void
    {
        Builder::mixin(new Search);
    }

    /**
     * Register package console commands when the application is running in the console.
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
