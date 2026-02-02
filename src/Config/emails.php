<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenancy Mode
    |--------------------------------------------------------------------------
    |
    | Controls how the package handles multi-tenancy.
    | 'auto' - Auto-detect tenancy package presence
    | 'single' - Force single-tenant mode
    | 'multi' - Force multi-tenant mode
    |
    */
    'tenancy_mode' => 'auto',

    /*
    |--------------------------------------------------------------------------
    | Email Types
    |--------------------------------------------------------------------------
    |
    | The list of allowed email types. Used for validation.
    |
    */
    'types' => ['personal', 'work', 'billing', 'other'],

    /*
    |--------------------------------------------------------------------------
    | Default Type
    |--------------------------------------------------------------------------
    |
    | The default email type when none is specified.
    |
    */
    'default_type' => 'personal',

    /*
    |--------------------------------------------------------------------------
    | Allow Custom Types
    |--------------------------------------------------------------------------
    |
    | When true, types not listed in 'types' array are still accepted.
    | When false, only types in the 'types' array are valid.
    |
    */
    'allow_custom_types' => true,

];
