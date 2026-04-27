<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Response;

// Load Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Perform migration
try {
    echo "Starting migration...<br>";
    Artisan::call('migrate', ['--force' => true]);
    echo "Migration output:<br><pre>";
    echo Artisan::output();
    echo "</pre>";
    echo "Migration finished successfully!";
} catch (\Exception $e) {
    echo "Error during migration: " . $e->getMessage();
}
