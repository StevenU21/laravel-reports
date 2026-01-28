# Laravel Reports

[![Latest Version on Packagist](https://img.shields.io/packagist/v/deifhelt/laravel-reports.svg?style=flat-square)](https://packagist.org/packages/deifhelt/laravel-reports)
[![Total Downloads](https://img.shields.io/packagist/dt/deifhelt/laravel-reports.svg?style=flat-square)](https://packagist.org/packages/deifhelt/laravel-reports)
[![License](https://img.shields.io/packagist/l/deifhelt/laravel-reports.svg?style=flat-square)](https://packagist.org/packages/deifhelt/laravel-reports)

---

## Project Description

**Laravel Reports** serves as a professional, structured container for `barryvdh/laravel-dompdf`. Its primary goal is to organize report generation logic by encapsulating it within dedicated definition classes, rather than scattering logic across controllers or routes.

This package allows you to:

- Centralize data query logic.
- Define views and filenames in a predictable manner.
- Validate record limits to prevent memory issues.
- Automatically handle download or browser streaming.

---

---

## Installation

You can install the package via composer:

```bash
composer require deifhelt/laravel-reports
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-reports-config"
```

## Creating Reports

The package provides a convenient command to generate a report class, its corresponding Blade view, and a test file:

```bash
php artisan make:report MonthlySales
```

This will create:

- `app/Reports/MonthlySales.php`: The report definition class.
- `resources/views/reports/monthly-sales.blade.php`: The Blade view for the PDF.
- `tests/Feature/Reports/MonthlySalesTest.php`: A basic Pest test for the report.

You can also specify a model:

```bash
php artisan make:report MonthlySales --model=Sale
```

## Official Documentation

---

## Quick Example

Define your report by implementing the `ReportDefinition` interface:

```php
use Deifhelt\LaravelReports\Interfaces\ReportDefinition;
use Deifhelt\LaravelReports\Traits\DefaultReportConfiguration;

class MonthlySales implements ReportDefinition
{
    use DefaultReportConfiguration;

    public function query(Request $request) {
        return Sale::whereMonth('created_at', now()->month);
    }

    public function view(): string {
        return 'reports.sales';
    }

    public function filename(): string {
        return 'monthly-sales.pdf';
    }
}
```

Generate the PDF in your controller:

```php
public function export(Request $request)
{
    return \Deifhelt\LaravelReports\Facades\LaravelReports::process(new MonthlySales(), $request);
}
```

---

## Credits

- [Deifhelt Ulloa](https://github.com/deifhelt)
- [All Contributors](../../contributors)

## License

This package is open-source software licensed under the [MIT license](LICENSE.md).
