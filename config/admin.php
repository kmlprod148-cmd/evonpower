<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin URL prefix
    |--------------------------------------------------------------------------
    |
    | The path segment used for every administrative route (default "admin").
    | Override via ADMIN_PATH in .env to a non-guessable value (e.g.
    | "back-office-7f3a") so the staff area is not enumerable by bots.
    | When changed, hard-coded "/admin/..." URLs in Blade views/controllers
    | must also be reviewed — prefer route() helpers in new code.
    |
    */
    // The `?:` coercion treats an empty ADMIN_PATH= the same as a missing key.
    // Without this, an empty .env value collapses the staff route to `/login`,
    // which collides with the customer login and breaks the gate entirely.
    'path' => env('ADMIN_PATH') ?: 'admin',

    /*
    |--------------------------------------------------------------------------
    | Admin-panel permission name
    |--------------------------------------------------------------------------
    |
    | Spatie permission that gates access to /{admin.path}/*. Users (admins,
    | operators, integrators, partners, …) must explicitly hold this
    | permission — directly or via a role — to enter the back-office.
    |
    */
    'permission' => env('ADMIN_PANEL_PERMISSION') ?: 'access-admin-panel',

];
