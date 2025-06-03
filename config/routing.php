<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Automatic Registration Routes
    |--------------------------------------------------------------------------
    |
    | Automatic registration of routes will only happen if this setting is `true`
    |
    */

    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Automatically Registered Paths
    |--------------------------------------------------------------------------
    |
    | Controllers in these directories that have routing attributes will automatically be registered.
    | Optionally, you can specify group configuration by using key/values
    |
    */

    'directories' => [
        app_path('Http/Controllers'),
        // app_path('Http/Controllers/Backend') => [
        //     'prefix'       => 'backend',
        //     'middleware'   => 'web',
        //     // only register routes in files that match the patterns
        //     'patterns'     => ['*Controller.php'],
        //     // do not register routes in files that match the patterns
        //     'not_patterns' => [],
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | This middleware will be applied to all routes.
    |
    */

    'middleware' => [
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Middleware
    |--------------------------------------------------------------------------
    |
    | Specify middleware that should be removed from all routes.
    |
    */

    'excluded_middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Scope Bindings
    |--------------------------------------------------------------------------
    |
    | When enabled, implicitly scoped bindings will be enabled by default.
    | You can override this behaviour by using the `ScopeBindings` attribute, and passing `false` to it.
    |
    | Possible values:
    |  - null: use the default behaviour
    |  - true: enable implicitly scoped bindings for all routes
    |  - false: disable implicitly scoped bindings for all routes
    |
    */

    'scope_bindings' => null,

    /*
    |--------------------------------------------------------------------------
    | Aliases Configurations
    |--------------------------------------------------------------------------
    |
    | Aliases of Action in Gateway Mode.
    |
    */

    'alias' => [
        '1.0.0' => [
            'ping' => 'utils.ping.ping',
        ],
        '2.0.0' => [
            'ping' => 'utils.ping.pong',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Gateway Middleware
    |--------------------------------------------------------------------------
    |
    | This middleware will be applied to the gateway routes.
    |
    */

    'gateway_middleware' => [],
];
