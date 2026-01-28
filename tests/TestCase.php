<?php

namespace Deifhelt\LaravelReports\Tests;

use Deifhelt\LaravelReports\LaravelReportsServiceProvider;
use Deifhelt\LaravelReports\Interfaces\PdfRenderer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public static $latestResponse = null;

    /**
     * Shared mock used in Pest tests when needed.
     *
     * @var PdfRenderer|MockInterface
     */
    public $pdfRenderer;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Deifhelt Ulloa\\LaravelReports\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelReportsServiceProvider::class,
            \Barryvdh\DomPDF\ServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
