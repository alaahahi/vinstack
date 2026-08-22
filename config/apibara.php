<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Apibara Vehicle Auction Data API
    |--------------------------------------------------------------------------
    |
    | Keep APIBARA_API_KEY on the server only. Never expose it to Vue/Vite.
    |
    */

    'api_key' => env('APIBARA_API_KEY'),

    'base_url' => env('APIBARA_BASE_URL', 'https://apibara.tech/api/v1/vehicle-auction'),

    'timeout' => (int) env('APIBARA_TIMEOUT', 30),

    'connect_timeout' => (int) env('APIBARA_CONNECT_TIMEOUT', 10),

    /*
    | Free plan starts at 100 requests / month. Cache identical queries to avoid
    | burning quota when admins/dealers re-open the same search.
    */
    'cache_enabled' => filter_var(env('APIBARA_CACHE_ENABLED', true), FILTER_VALIDATE_BOOL),

    'cache_ttl' => (int) env('APIBARA_CACHE_TTL', 86400), // 24 hours

    'filters_cache_ttl' => (int) env('APIBARA_FILTERS_CACHE_TTL', 86400), // 24 hours

    'max_per_page' => (int) env('APIBARA_MAX_PER_PAGE', 10),

    'monthly_free_quota' => (int) env('APIBARA_MONTHLY_FREE_QUOTA', 100),

];
