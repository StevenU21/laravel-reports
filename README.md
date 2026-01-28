# Laravel Reports

[![Latest Version on Packagist](https://img.shields.io/packagist/v/deifhelt/laravel-reports.svg?style=flat-square)](https://packagist.org/packages/deifhelt/laravel-reports)
[![Total Downloads](https://img.shields.io/packagist/dt/deifhelt/laravel-reports.svg?style=flat-square)](https://packagist.org/packages/deifhelt/laravel-reports)
[![License](https://img.shields.io/packagist/l/deifhelt/laravel-reports.svg?style=flat-square)](https://packagist.org/packages/deifhelt/laravel-reports)

## Overview

Laravel Reports is a small, opinionated wrapper around `barryvdh/laravel-dompdf` that helps you generate PDFs using a consistent report class pattern.

Instead of scattering PDF logic across controllers/routes, you define a report once (query + view + filename) and let the package handle rendering and response mode.

## What it provides

- **Report definitions** via `ReportDefinition` (query + view + filename)
- **Single entrypoint** via `LaravelReports::process()`
- **Automatic response mode**:
  - `?preview=1` or `?stream=1` streams in the browser
  - no flag downloads the PDF
- **Optional dataset limit validation** to protect against huge exports
- **Safe view variables** (`filters` are query-string only)
- **Pluggable renderer** via `PdfRenderer` (DomPDF by default)
- **Optional NativePHP preview helper** (`PreviewWindowReportManager`) for open window then stream PDF flows

## Installation

```bash
composer require deifhelt/laravel-reports
```

Publish the configuration:

```bash
php artisan vendor:publish --tag="laravel-reports-config"
```

DomPDF settings (fonts, remote assets, security, etc.) are configured in your app via `config/dompdf.php`.

## Public API (most used)

- `LaravelReports::process($report, $request, $title = 'Report')`
- `LaravelReports::stream($report, $request)`
- `LaravelReports::download($report, $request)`

## Documentation

- [Requirements](docs/requirements.md)
- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Usage (index)](docs/usage.md)

## Notes

- This package ships with an example migration stub via package tools; PDF rendering does not require a database table.

## Credits

- [Deifhelt Ulloa](https://github.com/deifhelt)
- [All Contributors](../../contributors)

## License

This package is open-source software licensed under the [MIT license](LICENSE.md).
