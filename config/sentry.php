<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sentry DSN
    |--------------------------------------------------------------------------
    |
    | The DSN tells the Sentry SDK where to send the events to.
    |
    */

    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | The environment helps you filter events by environment.
    |
    */

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),

    /*
    |--------------------------------------------------------------------------
    | Release
    |--------------------------------------------------------------------------
    |
    | The release version of your application.
    |
    */

    'release' => env('SENTRY_RELEASE'),

    /*
    |--------------------------------------------------------------------------
    | Sample Rate
    |--------------------------------------------------------------------------
    |
    | The sample rate for error events.
    |
    */

    'sample_rate' => env('SENTRY_SAMPLE_RATE', 1.0),

    /*
    |--------------------------------------------------------------------------
    | Traces Sample Rate
    |--------------------------------------------------------------------------
    |
    | The sample rate for performance monitoring.
    |
    */

    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.2),

    /*
    |--------------------------------------------------------------------------
    | Profiles Sample Rate
    |--------------------------------------------------------------------------
    |
    | The sample rate for profiling.
    |
    */

    'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),

    /*
    |--------------------------------------------------------------------------
    | Send Default PII
    |--------------------------------------------------------------------------
    |
    | If this flag is enabled, certain personally identifiable information
    | is added by active integrations.
    |
    */

    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

    /*
    |--------------------------------------------------------------------------
    | Breadcrumbs
    |--------------------------------------------------------------------------
    |
    | Configure breadcrumb recording.
    |
    */

    'breadcrumbs' => [
        // Capture Laravel logs as breadcrumbs
        'logs' => true,

        // Capture SQL queries as breadcrumbs
        'sql_queries' => true,

        // Capture bindings on SQL queries
        'sql_bindings' => false,

        // Capture queue job information as breadcrumbs
        'queue_info' => true,

        // Capture command information as breadcrumbs
        'command_info' => true,

        // Capture HTTP client requests as breadcrumbs
        'http_client_requests' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Controller Base Namespace
    |--------------------------------------------------------------------------
    |
    | You can configure the base namespace of your controllers here to
    | resolve the correct controller names when using route model binding.
    |
    */

    'controllers_base_namespace' => env('SENTRY_CONTROLLERS_BASE_NAMESPACE', 'App\\Http\\Controllers'),

    /*
    |--------------------------------------------------------------------------
    | Ignore Exceptions
    |--------------------------------------------------------------------------
    |
    | You can specify exception classes that should not be reported to Sentry.
    |
    */

    'ignore_exceptions' => [
        // Illuminate\Auth\AuthenticationException::class,
        // Illuminate\Auth\Access\AuthorizationException::class,
        // Symfony\Component\HttpKernel\Exception\HttpException::class,
        // Illuminate\Database\Eloquent\ModelNotFoundException::class,
        // Illuminate\Validation\ValidationException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore Transactions
    |--------------------------------------------------------------------------
    |
    | You can specify transaction names that should not be sent to Sentry.
    |
    */

    'ignore_transactions' => [
        // 'health-check',
    ],

];
