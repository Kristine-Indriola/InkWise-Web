<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'guard' => 'web',      // 👈 safer hardcoded default
        'passwords' => 'users' // 👈 safer hardcoded default
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    */
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'owner' => [
            'driver' => 'session',
            'provider' => 'owners',
        ],

        'staff' => [
        'driver' => 'session',
        'provider' => 'staffs', // must match the provider below
    ],
        // 👇 NEW guard for costumer
    'guards' => [
    'costumer' => [
        'driver' => 'session',
        'provider' => 'costumers',
    ],
],
    // 👇 NEW guard for costumer
        'costumer' => [
            'driver' => 'session',
            'provider' => 'costumers',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        'owners' => [
            'driver' => 'eloquent',
            'model' => App\Models\Owner::class, // 👈 make sure this model exists
        ],

        'staffs' => [
        'driver' => 'eloquent',
        'model' => App\Models\Staff::class, // create Staff model if not yet existing
    ],
    'providers' => [
    'costumers' => [
        'driver' => 'eloquent',
        'model' => App\Models\Costumer::class,
    ],
],
    // 👇 NEW provider for costumer
        'costumers' => [
            'driver' => 'eloquent',
            'model' => App\Models\Costumer::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Settings
    |--------------------------------------------------------------------------
    */
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'owners' => [
            'provider' => 'owners',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'passwords' => [
        'users' => [
            'provider' => 'costumers',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
        // 👇 NEW password reset for costumer
        'costumers' => [
            'provider' => 'costumers',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */
    'password_timeout' => 10800,

];
