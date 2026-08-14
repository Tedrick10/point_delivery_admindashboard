<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mobile API encryption keys
    |--------------------------------------------------------------------------
    |
    | Used by the Flutter apps for AES-256-CBC login/register payloads.
    | Must match lib/main/helper/encrypt_data.dart in flutter_user / flutter_delivery.
    |
    */

    'secret_key' => env('SECRET_KEY', '11a1215l0119a140409p0919'),
    'vikey' => env('VIKEY', '23a1dfr5lyhd9a1404845001'),

];
