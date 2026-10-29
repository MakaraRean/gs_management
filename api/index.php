<?php

use Illuminate\Http\Middleware\TrustProxies;
use Monolog\Formatter\LineFormatter;

/**
 * Vercel entry point (vercel-php runtime).
 *
 * Vercel's filesystem is read-only outside /tmp and there are no queue
 * workers, so these defaults keep Laravel from writing to the project
 * directory. Vercel's log viewer only keeps the tail of long output, so
 * errors are logged on one line without a stack trace. The Aiven MySQL
 * connection is verified against the committed project CA. Anything set
 * in the Vercel project environment wins.
 */
$vercelEnvironmentDefaults = [
    'APP_CONFIG_CACHE' => '/tmp/config.php',
    'APP_EVENTS_CACHE' => '/tmp/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/packages.php',
    'APP_ROUTES_CACHE' => '/tmp/routes.php',
    'APP_SERVICES_CACHE' => '/tmp/services.php',
    'VIEW_COMPILED_PATH' => '/tmp/views',
    'LOG_CHANNEL' => 'stderr',
    'LOG_STDERR_FORMATTER' => LineFormatter::class,
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_SECURE_COOKIE' => 'true',
    'MYSQL_ATTR_SSL_CA' => dirname(__DIR__).'/database/certs/aiven-ca.pem',
];

foreach ($vercelEnvironmentDefaults as $key => $value) {
    if (! isset($_SERVER[$key]) && ! isset($_ENV[$key]) && getenv($key) === false) {
        $_SERVER[$key] = $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}

require __DIR__.'/../vendor/autoload.php';

// Vercel terminates HTTPS at its edge, so trust its X-Forwarded-* headers for https:// URLs...
TrustProxies::at('*');

require __DIR__.'/../public/index.php';
