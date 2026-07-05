<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Brand Color
    |--------------------------------------------------------------------------
    |
    | The platform-wide default brand (primary) color for TeamHUB. Individual
    | workspaces can still override it with their own brand color when needed.
    |
    | All brand shades are derived from this single color in CSS, so only the
    | primary needs to be stored. Must be a 6-digit hex string.
    |
    */

    'brand' => env('APP_BRAND_COLOR', '#c8924a') ?: '#c8924a',

];
