<?php

namespace Katanaui;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class KatanaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'katana');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'katana');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Get the configurable namespace for components
        $namespace = config('katana.components.namespace', 'katana');
        
        if (empty($namespace)) {
            
            // No namespace - load components from root (e.g., <x-button>)
            Blade::anonymousComponentPath(__DIR__.'/../resources/views/components/katana');
            $this->loadViewsFrom(__DIR__.'/../resources/views/components/katana', '');
        } else {
            // Use configured namespace (e.g., <x-katana.button> or <x-ui.button>)
            Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', $namespace);
            $this->loadViewsFrom(__DIR__.'/../resources/views/components', $namespace);
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('katana.php'),
            ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/katana'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/katana'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/katana'),
            ], 'lang');*/

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'katana');

        // Register the main class to use with the facade
        $this->app->singleton('katana', function () {
            return new Katana;
        });
    }
}
