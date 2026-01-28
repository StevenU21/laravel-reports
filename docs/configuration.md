# Configuration

The configuration file is located at `config/reports.php` once published.

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Report Limit
    |--------------------------------------------------------------------------
    |
    | The maximum number of records allowed in a report to prevent
    | memory exhaustion.
    |
    */
    'limit' => 1000,
];

```

## DomPDF Settings

Since this package is a wrapper for `barryvdh/laravel-dompdf`, you can configure PDF options directly in your Laravel application's `config/dompdf.php` file. This includes:

- Default paper size.
- Orientation.
- Fonts and encoding.
- Rendering and security options.
