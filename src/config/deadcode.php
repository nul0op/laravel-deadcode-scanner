<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dead Code Scanner Ignore List
    |--------------------------------------------------------------------------
    |
    | You can ignore specific routes, controllers, or views here.
    |
    */

    'ignore' => [
        // Route names or URIs to ignore during scanning.
        // Examples:
        // 'routes' => [
        //     'login',            // route name
        //     'healthz',          // route name
        //     'admin/health',     // route URI
        // ],
        'routes' => [],

        // Fully-qualified controller classes to ignore.
        // Examples:
        // 'controllers' => [
        //     App\Http\Controllers\Internal\WebhookController::class,
        //     App\Http\Controllers\Auth\LoginController::class,
        // ],
        'controllers' => [],

        // You can ignore specific controller methods either by method name
        // (applies to any controller) or "FQCN::method" for a specific class.
        // Examples:
        // 'controller_methods' => [
        //     'helper', // ignore any public method literally named "helper"
        //     App\Http\Controllers\UserController::class . '::legacy',
        // ],
        'controller_methods' => [],

        // Blade view names (dot notation) to ignore.
        // Examples:
        // 'views' => [
        //     'errors::404',
        //     'emails.welcome',
        //     'admin.dashboard.index',
        // ],
        'views' => [],
    ],

];
