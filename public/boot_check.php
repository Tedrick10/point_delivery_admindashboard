<?php
/**
 * Temporary production diagnostic — delete after the site is healthy.
 * Visit: https://yoursite.com/boot_check.php
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "PHP " . PHP_VERSION . "\n";
echo "cwd: " . getcwd() . "\n";

$root = dirname(__DIR__);
echo "app root: {$root}\n";

$checks = [
    'vendor/autoload.php' => $root . '/vendor/autoload.php',
    '.env' => $root . '/.env',
    'bootstrap/app.php' => $root . '/bootstrap/app.php',
    'storage/logs writable' => $root . '/storage/logs',
    'bootstrap/cache writable' => $root . '/bootstrap/cache',
    'ralouphie/getallheaders' => $root . '/vendor/ralouphie/getallheaders/src/getallheaders.php',
];

foreach ($checks as $label => $path) {
    if (str_contains($label, 'writable')) {
        echo $label . ': ' . (is_dir($path) && is_writable($path) ? 'OK' : 'FAIL') . " ({$path})\n";
    } else {
        echo $label . ': ' . (file_exists($path) ? 'OK' : 'MISSING') . "\n";
    }
}

if (!file_exists($root . '/vendor/autoload.php')) {
    echo "\nSTOP: run composer install --no-dev in app root.\n";
    exit;
}

try {
    require $root . '/vendor/autoload.php';
    echo "autoload: OK\n";
} catch (Throwable $e) {
    echo "autoload ERROR: " . $e->getMessage() . "\n";
    exit;
}

try {
    $app = require $root . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "laravel bootstrap: OK\n";
    echo "APP_KEY set: " . (empty(env('APP_KEY')) && empty(config('app.key')) ? 'NO' : 'YES') . "\n";
} catch (Throwable $e) {
    echo "laravel bootstrap ERROR: " . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
    exit;
}

$log = $root . '/storage/logs/laravel.log';
if (file_exists($log)) {
    echo "\n--- laravel.log (last 40 lines) ---\n";
    $lines = @file($log);
    if ($lines) {
        echo implode('', array_slice($lines, -40));
    }
} else {
    echo "\nNo laravel.log yet.\n";
}

echo "\nDONE — delete this file when finished.\n";
