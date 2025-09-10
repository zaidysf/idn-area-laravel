<?php

declare(strict_types=1);

/**
 * Indonesian Area Data Package Configuration - by zaidysf
 * Official BPS (Badan Pusat Statistik) Government Data Source
 * Dual-mode system: API (real-time) vs Local (cached)
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Operation Mode
    |--------------------------------------------------------------------------
    |
    | Choose between two operation modes:
    | - 'api': Real-time data from BPS government API (slower but always latest)
    | - 'local': Fast queries from local database (requires periodic sync)
    |
    */
    'mode' => env('IDN_AREA_MODE', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Setup Status
    |--------------------------------------------------------------------------
    |
    | Tracks whether the initial setup has been completed.
    | Set automatically by the setup command.
    |
    */
    'setup_completed' => env('IDN_AREA_SETUP_COMPLETED', false),

    /*
    |--------------------------------------------------------------------------
    | DataToko API Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for connecting to the DataToko API service.
    | Used in API mode for real-time data access.
    |
    */
    'datatoko_api' => [
        'base_url' => env('IDN_AREA_DATATOKO_URL', 'https://data.toko.center'),
        'access_key' => env('IDN_AREA_ACCESS_KEY'),
        'secret_key' => env('IDN_AREA_SECRET_KEY'),
        'timeout' => env('IDN_AREA_API_TIMEOUT', 30), // seconds
        'retry_attempts' => env('IDN_AREA_API_RETRIES', 3),
        'retry_delay' => env('IDN_AREA_API_RETRY_DELAY', 1), // seconds
        'verify_ssl' => env('IDN_AREA_VERIFY_SSL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Token Storage
    |--------------------------------------------------------------------------
    |
    | Configuration for storing DataToko API authentication tokens.
    | Options: 'redis', 'file', 'cache'
    |
    */
    'token_storage' => [
        'driver' => env('IDN_AREA_TOKEN_STORAGE', 'cache'), // redis, file, cache
        'ttl' => env('IDN_AREA_TOKEN_TTL', 3600), // 1 hour
        'file_path' => env('IDN_AREA_TOKEN_FILE_PATH', 'storage/app/idn-area-token.json'),
        'redis_key' => env('IDN_AREA_TOKEN_REDIS_KEY', 'idn_area:auth_token'),
    ],

    /*
    |--------------------------------------------------------------------------
    | BPS API Configuration (Legacy - for data sync only)
    |--------------------------------------------------------------------------
    |
    | Settings for connecting to the official BPS government API.
    | Used for data synchronization in local mode only.
    |
    */
    'bps_api' => [
        'base_url' => env('IDN_AREA_BPS_URL', 'https://sig.bps.go.id/rest-drop-down/'),
        'timeout' => env('IDN_AREA_API_TIMEOUT', 30), // seconds
        'retry_attempts' => env('IDN_AREA_API_RETRIES', 3),
        'retry_delay' => env('IDN_AREA_API_RETRY_DELAY', 1), // seconds
        'default_period' => env('IDN_AREA_DEFAULT_PERIOD', '2024_1.2025'),
        'verify_ssl' => env('IDN_AREA_VERIFY_SSL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Caching settings - only applies to local mode for performance optimization.
    | API mode bypasses cache to ensure real-time data.
    |
    */
    'cache' => [
        'enabled' => env('IDN_AREA_CACHE_ENABLED', true),
        'ttl' => env('IDN_AREA_CACHE_TTL', 3600), // 1 hour
        'search_ttl' => env('IDN_AREA_SEARCH_CACHE_TTL', 1800), // 30 minutes
        'stats_ttl' => env('IDN_AREA_STATS_CACHE_TTL', 7200), // 2 hours
        'prefix' => env('IDN_AREA_CACHE_PREFIX', 'idn_area'),
        'tags' => ['idn_area'], // For cache invalidation
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for local database storage (local mode).
    |
    */
    'database' => [
        'table_prefix' => env('IDN_AREA_TABLE_PREFIX', 'idn_'),
        'enable_foreign_keys' => env('IDN_AREA_ENABLE_FOREIGN_KEYS', true),
        'chunk_size' => env('IDN_AREA_CHUNK_SIZE', 1000),
        'connection' => env('IDN_AREA_DB_CONNECTION', null), // Use default if null
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Optimize performance for your specific use case.
    |
    */
    'performance' => [
        'chunk_size' => env('IDN_AREA_CHUNK_SIZE', 1000),
        'max_search_results' => env('IDN_AREA_MAX_SEARCH_RESULTS', 100),
        'enable_query_log' => env('IDN_AREA_QUERY_LOG', false),
        'memory_limit' => env('IDN_AREA_MEMORY_LIMIT', '512M'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configure search behavior for both API and local modes.
    |
    */
    'search' => [
        'min_query_length' => env('IDN_AREA_MIN_QUERY_LENGTH', 2),
        'case_sensitive' => env('IDN_AREA_SEARCH_CASE_SENSITIVE', false),
        'max_results' => env('IDN_AREA_SEARCH_MAX_RESULTS', 100),
        'enable_fuzzy_search' => env('IDN_AREA_ENABLE_FUZZY_SEARCH', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for data synchronization from BPS API to local database.
    |
    */
    'sync' => [
        'auto_sync' => env('IDN_AREA_AUTO_SYNC', false),
        'sync_interval_hours' => env('IDN_AREA_SYNC_INTERVAL', 24), // 24 hours
        'batch_size' => env('IDN_AREA_SYNC_BATCH_SIZE', 100),
        'enable_progress_bar' => env('IDN_AREA_SYNC_PROGRESS', true),
        'verify_data' => env('IDN_AREA_VERIFY_SYNC_DATA', true),
        'backup_before_sync' => env('IDN_AREA_BACKUP_BEFORE_SYNC', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Models
    |--------------------------------------------------------------------------
    |
    | Model classes for Indonesian area data.
    | You can override these with your own extended models.
    |
    */
    'models' => [
        'province' => \zaidysf\IdnArea\Models\Province::class,
        'regency' => \zaidysf\IdnArea\Models\Regency::class,
        'district' => \zaidysf\IdnArea\Models\District::class,
        'village' => \zaidysf\IdnArea\Models\Village::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | BPS Data Structure Validation
    |--------------------------------------------------------------------------
    |
    | Validation patterns for official BPS code structure.
    |
    */
    'validation' => [
        'province_code_pattern' => '/^[0-9]{1,2}$/', // 11, 12, ..., 95
        'regency_code_pattern' => '/^[0-9]{3,4}$/', // 1101, 1102, ..., 9507
        'district_code_pattern' => '/^[0-9]{5,7}$/', // 110101, 110102, etc.
        'village_code_pattern' => '/^[0-9]{10}$/', // 1101011001, etc.
        'strict_validation' => env('IDN_AREA_STRICT_VALIDATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for debugging and monitoring.
    |
    */
    'logging' => [
        'enabled' => env('IDN_AREA_LOGGING', false),
        'channel' => env('IDN_AREA_LOG_CHANNEL', 'single'),
        'level' => env('IDN_AREA_LOG_LEVEL', 'info'),
        'log_api_calls' => env('IDN_AREA_LOG_API_CALLS', false),
        'log_cache_hits' => env('IDN_AREA_LOG_CACHE_HITS', false),
        'log_sync_operations' => env('IDN_AREA_LOG_SYNC', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features.
    |
    */
    'features' => [
        'enable_statistics' => env('IDN_AREA_ENABLE_STATISTICS', true),
        'enable_search' => env('IDN_AREA_ENABLE_SEARCH', true),
        'enable_export' => env('IDN_AREA_ENABLE_EXPORT', true),
        'enable_hierarchy' => env('IDN_AREA_ENABLE_HIERARCHY', true),
        'enable_api_fallback' => env('IDN_AREA_ENABLE_API_FALLBACK', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for data export functionality.
    |
    */
    'export' => [
        'default_format' => env('IDN_AREA_EXPORT_FORMAT', 'csv'),
        'export_path' => env('IDN_AREA_EXPORT_PATH', 'idn-area-export'),
        'include_timestamps' => env('IDN_AREA_EXPORT_TIMESTAMPS', false),
        'max_export_size' => env('IDN_AREA_MAX_EXPORT_SIZE', 100000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Information
    |--------------------------------------------------------------------------
    |
    | Package metadata and credits.
    |
    */
    'package' => [
        'version' => '3.0.0-BPS',
        'author' => 'zaidysf',
        'data_source' => 'BPS (Badan Pusat Statistik) Indonesia',
        'api_source' => 'https://sig.bps.go.id/rest-drop-down/',
        'last_updated' => '2024',
        'government_official' => true,
    ],
];
