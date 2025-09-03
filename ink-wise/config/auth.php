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
        // 👇 NEW guard for customer
    'guards' => [
    'customer' => [
        'driver' => 'session',
        'provider' => 'customers',
    ],
],
    // 👇 NEW guard for customer
        'customer' => [
            'driver' => 'session',
            'provider' => 'customers',
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
    'customers' => [
        'driver' => 'eloquent',
        'model' => App\Models\customer::class,
    ],
],
    // 👇 NEW provider for customer
        'customers' => [
            'driver' => 'eloquent',
            'model' => App\Models\customer::class,
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
            'provider' => 'customers',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
        // 👇 NEW password reset for customer
        'customers' => [
            'provider' => 'customers',
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
