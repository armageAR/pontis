<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Initial Superadmin
    |--------------------------------------------------------------------------
    |
    | Credentials used by SuperAdminSeeder to create the first superadmin
    | account. Leave the password empty and the seeder generates a random one
    | and prints it once, so no deployment ends up with a known default.
    |
    */

    'superadmin' => [
        'name' => env('SUPERADMIN_NAME', 'Super Admin'),
        'email' => env('SUPERADMIN_EMAIL', 'admin@example.com'),
        'password' => env('SUPERADMIN_PASSWORD'),
    ],

];
