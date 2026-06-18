<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Brand Color
    |--------------------------------------------------------------------------
    |
    | The platform-wide default brand (primary) color. This represents the
    | university-level default until universities become their own model;
    | at that point this value should come from the current university.
    | Individual clubs may override it with their own brand color.
    |
    | All brand shades are derived from this single color in CSS, so only the
    | primary needs to be stored. Must be a 6-digit hex string.
    |
    */

    'brand' => env('APP_BRAND_COLOR', '#006471'),

];
