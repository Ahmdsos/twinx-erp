<?php

declare(strict_types=1);

/**
 * TWINX ERP Configuration
 * 
 * Core configuration settings for the TWINX ERP system.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenant Settings
    |--------------------------------------------------------------------------
    */
    'tenant' => [
        // Enable/disable multi-tenant functionality
        'enabled' => env('TWINX_TENANT_ENABLED', true),

        // Default tenant discovery method: 'header', 'session', 'hybrid'
        'discovery' => env('TWINX_TENANT_DISCOVERY', 'hybrid'),

        // Header names for API tenant discovery
        'headers' => [
            'company' => 'X-Company-ID',
            'branch' => 'X-Branch-ID',
        ],

        // Session keys for web tenant context
        'session' => [
            'company' => 'current_company_id',
            'branch' => 'current_branch_id',
        ],

        // Routes that don't require tenant context
        'exempt_routes' => [
            'login',
            'logout',
            'register',
            'password.*',
            'tenant.select',
            'tenant.switch',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        // Default currency (ISO 4217)
        'currency' => env('TWINX_DEFAULT_CURRENCY', 'SAR'),

        // Default timezone
        'timezone' => env('TWINX_DEFAULT_TIMEZONE', 'Asia/Riyadh'),

        // Default language
        'language' => env('TWINX_DEFAULT_LANGUAGE', 'ar'),

        // Fiscal year start (MM-DD format)
        'fiscal_year_start' => env('TWINX_FISCAL_YEAR_START', '01-01'),

        // Country (ISO 3166-1 alpha-2)
        'country' => env('TWINX_DEFAULT_COUNTRY', 'SA'),
    ],

    /*
    |--------------------------------------------------------------------------
    | UUID Settings
    |--------------------------------------------------------------------------
    */
    'uuid' => [
        // UUID version: 4 (random) or 7 (timestamp-based)
        'version' => env('TWINX_UUID_VERSION', 4),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Settings
    |--------------------------------------------------------------------------
    */
    'audit' => [
        // Enable/disable audit trail
        'enabled' => env('TWINX_AUDIT_ENABLED', true),

        // Audit driver: 'database', 'file', etc.
        'driver' => env('TWINX_AUDIT_DRIVER', 'database'),

        // Events to audit
        'events' => [
            'created',
            'updated',
            'deleted',
            'restored',
        ],

        // Exclude these attributes from audit
        'exclude' => [
            'password',
            'remember_token',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        // Session timeout in minutes
        'session_timeout' => env('TWINX_SESSION_TIMEOUT', 120),

        // Maximum failed login attempts before lockout
        'max_login_attempts' => env('TWINX_MAX_LOGIN_ATTEMPTS', 5),

        // Lockout duration in minutes
        'lockout_duration' => env('TWINX_LOCKOUT_DURATION', 15),

        // Password minimum length
        'password_min_length' => env('TWINX_PASSWORD_MIN_LENGTH', 8),

        // Require password complexity
        'password_require_mixed_case' => env('TWINX_PASSWORD_MIXED_CASE', true),
        'password_require_numbers' => env('TWINX_PASSWORD_NUMBERS', true),
        'password_require_symbols' => env('TWINX_PASSWORD_SYMBOLS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'default_per_page' => env('TWINX_DEFAULT_PER_PAGE', 25),
        'max_per_page' => env('TWINX_MAX_PER_PAGE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Storage Settings
    |--------------------------------------------------------------------------
    */
    'storage' => [
        // Default disk for uploads
        'disk' => env('TWINX_STORAGE_DISK', 'local'),

        // Maximum upload size in MB
        'max_upload_size' => env('TWINX_MAX_UPLOAD_SIZE', 100),

        // Allowed file extensions
        'allowed_extensions' => [
            'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv'],
        ],
    ],

];
