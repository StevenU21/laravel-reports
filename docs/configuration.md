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

## Custom PDF Engines

By default this package renders PDFs using DomPDF. If you want to use a different engine (e.g. Snappy/Wkhtmltopdf), bind the renderer interface in your application container.

Implement the interface:

```php
use Deifhelt\LaravelReports\Interfaces\PdfRenderer;
use Symfony\Component\HttpFoundation\Response;

class MyPdfRenderer implements PdfRenderer
{
    public function stream(string $view, array $data, string|array $paper, string $orientation, string $filename): Response
    {
        // Render and return a streamed response
    }

    public function download(string $view, array $data, string|array $paper, string $orientation, string $filename): Response
    {
        // Render and return a download response
    }
}
```

Then register it (e.g. in `AppServiceProvider`):

```php
use Deifhelt\LaravelReports\Interfaces\PdfRenderer;

$this->app->singleton(PdfRenderer::class, MyPdfRenderer::class);
```
