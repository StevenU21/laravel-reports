<?php

namespace Deifhelt\LaravelReports;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelReportsServiceProvider extends PackageServiceProvider
{
    public function registeringPackage(): void
    {
        $this->app->singleton(LaravelReports::class, fn () => new LaravelReports());

        // Stable service key for the Facade accessor
        $this->app->alias(LaravelReports::class, 'laravel-reports');
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-reports')
            ->hasConfigFile('reports')
            ->hasViews()
            ->hasMigration('create_laravel_reports_table')
            ->hasCommand(Commands\MakeReportCommand::class);
    }

    public function bootingPackage()
    {
        $this->publishes([
            __DIR__ . '/../config/reports.php' => config_path('reports.php'),
        ], 'laravel-reports-config');
    }
}
