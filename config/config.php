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
    
];
