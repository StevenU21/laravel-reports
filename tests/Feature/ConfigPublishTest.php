<?php

use Deifhelt\LaravelReports\LaravelReportsServiceProvider;
use Illuminate\Support\ServiceProvider;

it('registers publishing configuration correctly', function () {
    // Manually boot provider to ensure registration if not autoloaded
    // (Orchestra usually loads it via getPackageProviders)

    $paths = ServiceProvider::pathsToPublish(LaravelReportsServiceProvider::class, 'laravel-reports-config');

    expect($paths)->not->toBeEmpty();

    // Check if the source file exists (key)
    $source = array_key_first($paths);
    expect(file_exists($source))->toBeTrue();

    // Check if destination is correct (value)
    $destination = $paths[$source];
    expect($destination)->toBe(config_path('reports.php'));
});
