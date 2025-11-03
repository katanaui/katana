<?php

/*
 * The KatanaUI (https://katanaui.com) Config
 */
return [

    'api_keys' => [
        'address_autocomplete' => env('GOOGLE_PLACES_API_KEY'),
    ],

    'components' => [
        /*
         * The namespace/prefix used for all Katana components.
         * Example:
         *   'katana' → <x-katana.button>
         *   ''       → <x-button>
         *   'ui'     → <x-ui.button>
         */
        'namespace' => 'katana',
    ],

    'assets' => [
        /*
         * Enable automatic publishing of compiled component JavaScript assets.
         * When enabled, run: php artisan vendor:publish --tag=katana-assets
         */
        'publish' => true,

        /*
         * The public path where component assets will be published.
         * This path is relative to the public/ directory.
         * Example: 'katana' → public/katana/
         *          'assets/katana' → public/assets/katana/
         */
        'path' => 'katana',
    ],

];
