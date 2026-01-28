# NativePHP Preview (Optional)

This package does not depend on NativePHP, but it provides a small helper to make open preview window flows easy while still using the package's PDF response for streaming.

## Why two endpoints are needed

- `openPreview(...)` opens the NativePHP window (side-effect) and returns `204`. It is not meant to be used as a normal browser link.
- `index(...)` (or a dedicated `stream(...)`) returns the PDF response. This is what the window (and the browser) loads.

## 1) Implement a window opener adapter in your app

```php
class NativePhpWindowOpener implements PreviewWindowOpener
{
    public function openPdfWindow(string $route, array $params, string $title): void
    {
        Window::open('pdf-preview-' . uniqid())
            ->route($route, $params)
            ->width(900)
            ->height(700)
            ->minWidth(600)
            ->minHeight(400)
            ->title($title)
            ->resizable(true)
            ->hideMenu()
            ->hideDevTools();
    }
}
```

Bind it in your container:

```php
$this->app->singleton(PreviewWindowOpener::class, NativePhpWindowOpener::class);
```

## 2) Use `PreviewWindowReportManager` in your controller

```php
class SaleExportController extends Controller
{
    public function __construct(
        private readonly LaravelReports $laravelReports,
        private readonly PreviewWindowReportManager $previews,
    ) {}

    // A) Opens the NativePHP preview window (does not return the PDF)
    public function openPreview(Request $request)
    {
        return $this->previews->process(
            report: new GeneralSalesReport(),
            request: $request,
            title: 'Reporte General de Ventas',
            // Point to any existing route that returns the PDF.
            // The helper will add ?preview=1 automatically.
            route: 'exports.sales.index',
        );
    }

    // B) Route that the NativePHP window loads (returns the streamed PDF)
    public function index(Request $request)
    {
        return $this->laravelReports->process(new GeneralSalesReport(), $request, 'Reporte General de Ventas');
    }
}
```

### Routes example

```php
Route::get('/exports/sales', [SaleExportController::class, 'index'])
    ->name('exports.sales.index');

Route::get('/exports/sales/preview', [SaleExportController::class, 'openPreview'])
    ->name('exports.sales.preview');
```

### Blade links for NativePHP vs browser

```blade
{{-- Browser preview: directly hit the PDF route with ?preview=1 --}}
<a href="{{ route('exports.sales.index', ['preview' => 1]) }}">Ver en navegador</a>

{{-- NativePHP preview: hit the opener route (returns 204, opens window) --}}
<a href="{{ route('exports.sales.preview') }}">Abrir en NativePHP</a>
```

## Route model binding notes

If your preview opener route includes route-model-binding params (e.g. `/exports/sales/{sale}/receipt/preview`), the helper forwards them automatically to the target route by converting models using their route keys.

Back: [Security & robustness](06-security.md)
