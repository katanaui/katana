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
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Get the configurable namespace for components
        $namespace = config('katana.components.namespace', 'katana');

        // Prefer packages/ path (editable in playground) over vendor/ (read-only copy)
        $packagesPath = base_path('packages/katanaui/katana/resources/views/components');
        $vendorPath = __DIR__.'/../resources/views/components';
        $componentsPath = is_dir($packagesPath) ? $packagesPath : $vendorPath;

        if (empty($namespace)) {
            // No namespace - load components from root (e.g., <x-button>)
            Blade::anonymousComponentPath($componentsPath.'/katana');
            $this->loadViewsFrom($componentsPath.'/katana', '');
        } else {
            // Use configured namespace (e.g., <x-katana.button> or <x-ui.button>)
            Blade::anonymousComponentPath($componentsPath, $namespace);
            $this->loadViewsFrom($componentsPath, $namespace);
        }

        // Register Volt single-file Livewire components
        if (class_exists(\Livewire\Volt\Volt::class)) {
            \Livewire\Volt\Volt::mount([
                dirname(__DIR__).'/resources/views/livewire',
            ]);
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('katana.php'),
            ], 'config');

            // Publishing compiled component JavaScript assets
            if (config('katana.assets.publish', true)) {
                $assetPath = config('katana.assets.path', 'katana');
                $this->publishes([
                    __DIR__.'/../public/katana' => public_path($assetPath),
                ], 'katana-assets');
            }

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/katana'),
            ], 'views');*/

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

        // Load global helper functions if enabled
        if (config('katana.globals.enabled', true)) {
            require_once __DIR__.'/helpers.php';
        }

        // Register the main class to use with the facade
        $this->app->singleton('katana', function () {
            return new Katana;
        });
    }
}
