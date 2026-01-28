<?php

namespace Deifhelt\LaravelReports\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeReportCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new report class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Report';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('model')) {
            return __DIR__.'/stubs/report-model.stub';
        }

        return __DIR__.'/stubs/report.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Reports';
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        $model = $this->option('model');

        if ($model) {
            $modelClass = $this->qualifyModel($model);
            $stub = str_replace(
                ['{{ namespacedModel }}', '{{ model }}'],
                [$modelClass, class_basename($modelClass)],
                $stub
            );
        }

        $stub = str_replace(
            ['{{ view }}', '{{ filename }}'],
            [$this->getViewName($name), $this->getFileName($name)],
            $stub
        );

        return $stub;
    }

    /**
     * Get the view name in dot notation.
     */
    protected function getViewName($name)
    {
        return str($name)->classBasename()->kebab();
    }

    /**
     * Get the filename for the report.
     */
    protected function getFileName($name)
    {
        return str($name)->classBasename()->snake();
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        if (parent::handle() === false && ! $this->option('force')) {
            return false;
        }

        $this->createView();
        $this->createTest();

        return null;
    }

    /**
     * Create the view for the report.
     */
    protected function createView()
    {
        $viewName = $this->getViewName($this->getNameInput());
        $path = resource_path('views/reports/'.$viewName.'.blade.php');

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->components->error('View already exists.');

            return;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->buildView());

        $this->components->info('View created successfully.');
    }

    /**
     * Build the view with the given name.
     */
    protected function buildView()
    {
        $stub = $this->files->get(__DIR__.'/stubs/view.stub');

        return str_replace(
            ['{{ class }}', '{{ date }}'],
            [str($this->getNameInput())->classBasename(), now()->format('Y-m-d H:i:s')],
            $stub
        );
    }

    /**
     * Create the test for the report.
     */
    protected function createTest()
    {
        $name = str_replace('\\', '/', $this->getNameInput());
        $path = base_path('tests/Feature/Reports/'.$name.'Test.php');

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->components->error('Test already exists.');

            return;
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->buildTest());

        $this->components->info('Test created successfully.');
    }

    /**
     * Build the test with the given name.
     */
    protected function buildTest()
    {
        $stub = $this->files->get(__DIR__.'/stubs/test.stub');

        return str_replace(
            ['{{ class }}', '{{ view }}', '{{ filename }}'],
            [
                str($this->getNameInput())->classBasename(),
                $this->getViewName($this->getNameInput()),
                $this->getFileName($this->getNameInput()),
            ],
            $stub
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the report applies to'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the report already exists'],
        ];
    }

    /**
     * Qualify the given model class base name.
     *
     * @param  string  $model
     * @return string
     */
    protected function qualifyModel($model)
    {
        $model = ltrim($model, '\\/');

        $rootNamespace = $this->laravel->getNamespace();

        if (str_starts_with($model, $rootNamespace)) {
            return $model;
        }

        return is_dir(app_path('Models'))
            ? $rootNamespace.'Models\\'.$model
            : $rootNamespace.$model;
    }
}
