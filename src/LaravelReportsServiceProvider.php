<?php

namespace Deifhelt\LaravelReports;

use Deifhelt\LaravelReports\Interfaces\PdfRenderer;
use Deifhelt\LaravelReports\Renderers\DomPdfRenderer;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelReportsServiceProvider extends PackageServiceProvider
{
    public function registeringPackage(): void
    {
        $this->app->singleton(PdfRenderer::class, DomPdfRenderer::class);

        $this->app->singleton(LaravelReports::class, fn ($app) => new LaravelReports(
            $app->make(PdfRenderer::class)
        ));

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
