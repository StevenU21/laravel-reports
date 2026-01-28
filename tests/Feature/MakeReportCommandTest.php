<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(app_path('Reports'));
    File::deleteDirectory(resource_path('views/reports'));
    File::deleteDirectory(base_path('tests/Feature/Reports'));
});

it('can create a basic report command with view and test', function () {
    $this->artisan('make:report', ['name' => 'UserReport'])
        ->assertExitCode(0);

    // Verify Report Class
    $path = app_path('Reports/UserReport.php');
    expect(File::exists($path))->toBeTrue();

    $content = File::get($path);
    expect($content)
        ->toContain('class UserReport implements ReportDefinition')
        ->toContain("return 'reports.user-report';")
        ->toContain("return 'user_report_'")
        ->not->toContain('use App\Models');

    // Verify View
    $viewPath = resource_path('views/reports/user-report.blade.php');
    expect(File::exists($viewPath))->toBeTrue();
    expect(File::get($viewPath))->toContain('<h1>UserReport</h1>');

    // Verify Test
    $testPath = base_path('tests/Feature/Reports/UserReportTest.php');
    expect(File::exists($testPath))->toBeTrue();
    expect(File::get($testPath))->toContain("use App\Reports\UserReport;");
});

it('can create a report with model option', function () {
    $this->artisan('make:report', ['name' => 'ProductReport', '--model' => 'Product'])
        ->assertExitCode(0);

    $path = app_path('Reports/ProductReport.php');
    expect(File::exists($path))->toBeTrue();

    $content = File::get($path);
    expect($content)
        ->toContain('use App\Models\Product;')
        ->toContain('return Product::query();');
});

it('can create a report in subdirectory', function () {
    $this->artisan('make:report', ['name' => 'Sales\\DailyReport'])
        ->assertExitCode(0);

    $path = app_path('Reports/Sales/DailyReport.php');
    expect(File::exists($path))->toBeTrue();

    $content = File::get($path);
    expect($content)
        ->toContain('class DailyReport');

    // Verify view in subdirectory
    $viewPath = resource_path('views/reports/daily-report.blade.php');
    expect(File::exists($viewPath))->toBeTrue();
});
