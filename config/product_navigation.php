<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Product Navigation Configuration
    |--------------------------------------------------------------------------
    |
    | This file configures the contextual product navigation system.
    | This system provides Prev/Next navigation on product detail pages
    | based on the browsing context (filters, sorting) the user came from.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    
    // Time-to-live for navigation lists in seconds (default: 10 minutes)
    // Shorter TTL ensures navigation stays fresh when products change
    'cache_ttl' => env('PRODUCT_NAV_CACHE_TTL', 600),
    
    // Maximum number of products to store in a navigation list
    // Prevents memory issues with very large result sets
    // Products beyond this limit won't have accurate position tracking
    'max_list_size' => env('PRODUCT_NAV_MAX_LIST_SIZE', 500),
    
    // Cache key prefix for navigation lists
    'cache_prefix' => 'product_nav_list_',

    /*
    |--------------------------------------------------------------------------
    | Fallback Behavior
    |--------------------------------------------------------------------------
    |
    | Configure what happens when a user lands on a product page directly
    | (without browsing context)
    |
    */
    
    // Enable category-based fallback navigation
    // When true, shows prev/next from same category if no list context
    'enable_category_fallback' => env('PRODUCT_NAV_CATEGORY_FALLBACK', false),
    
    // Default sort field for fallback navigation
    'fallback_order_by' => 'created_at',
    
    // Default sort direction for fallback navigation
    'fallback_direction' => 'desc',

    /*
    |--------------------------------------------------------------------------
    | Response Settings
    |--------------------------------------------------------------------------
    */
    
    // Include navigation data in product detail responses automatically
    // When false, navigation must be requested via separate endpoint
    'include_in_product_response' => env('PRODUCT_NAV_AUTO_INCLUDE', true),
    
    // Fields to include in prev/next product data
    'navigation_fields' => [
        'id',
        'name', 
        'slug',
        'price',
        'thumbnail',
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    
    // Use Redis for navigation caches (recommended for production)
    // Falls back to default cache driver if Redis unavailable
    'prefer_redis' => env('PRODUCT_NAV_PREFER_REDIS', true),
    
    // Enable query logging for navigation queries (development only)
    'log_queries' => env('PRODUCT_NAV_LOG_QUERIES', false),
];
