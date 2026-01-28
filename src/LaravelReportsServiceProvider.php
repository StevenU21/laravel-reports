<?php

namespace Deifhelt\LaravelReports;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelReportsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-reports')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_reports_table')
            ->hasCommand(Commands\MakeReportCommand::class);
    }
}
