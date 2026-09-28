<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Per-vehicle gallery file store (phase 1)
    |--------------------------------------------------------------------------
    |
    | New admin uploads / ZIP transfers append to a JSON file per vehicle
    | under storage/app/vehicle-galleries/{id}.json instead of inserting into
    | vehicle_uploaded_images. Legacy DB rows remain readable (dual-read).
    | A later migration step can move old rows into files.
    |
    */

    'file_store_enabled' => (bool) env('VEHICLE_GALLERY_FILE_STORE', true),

    'path' => env('VEHICLE_GALLERY_PATH', storage_path('app/vehicle-galleries')),

];
