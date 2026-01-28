# Usage

Laravel Reports simplifies PDF report generation by structuring the logic into dedicated classes.

## Command Generation

The easiest way to get started is by using the Artisan command:

```bash
php artisan make:report UsersReport
```

This command will automatically generate:

1.  **Report Class**: `app/Reports/UsersReport.php`
2.  **Blade View**: `resources/views/reports/users-report.blade.php`
3.  **Pest Test**: `tests/Feature/Reports/UsersReportTest.php`

You can also link it to an existing model:

```bash
php artisan make:report UsersReport --model=User
```

## 1. Create a Report Definition Manually

```php
namespace App\Reports;

use Deifhelt\LaravelReports\Interfaces\ReportDefinition;
use Deifhelt\LaravelReports\Traits\DefaultReportConfiguration;
use Illuminate\Http\Request;
use App\Models\User;

class UsersReport implements ReportDefinition
{
    use DefaultReportConfiguration;

    public function query(Request $request)
    {
        // Returns a Query Builder, Eloquent Builder, or Collection
        return User::query()
            ->when($request->has('role'), function ($q) use ($request) {
                $q->where('role', $request->role);
            });
    }

    public function view(): string
    {
        // The Blade view that will render the PDF
        return 'reports.users';
    }

    public function filename(): string
    {
        return 'users-report-' . date('Y-m-d') . '.pdf';
    }
}
```

## 2. Create the Blade View

Create your Blade file in `resources/views/reports/users.blade.php`. The `$data` variable will contain the results of your query.

```html
<!DOCTYPE html>
<html>
    <head>
        <title>Users Report</title>
        <style>
            /* Your CSS styles for PDF */
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th,
            td {
                border: 1px solid black;
                padding: 5px;
            }
        </style>
    </head>
    <body>
        <h1>Users List</h1>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
```

## 3. Generate the Report in the Controller

You can process reports using dependency injection (recommended) or the Facade.

### Recommended: Dependency Injection

```php
namespace App\Http\Controllers;

use App\Reports\UsersReport;
use Deifhelt\LaravelReports\LaravelReports;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private readonly LaravelReports $laravelReports)
    {
    }

    public function download(Request $request)
    {
        $report = new UsersReport();

        // Automatically handles Stream or Download based on the request
        // If the request has ?preview or ?stream, it will be shown in the browser.
        // Otherwise, it will be downloaded.
        // Optional third parameter becomes available in the Blade view as $title
        return $this->laravelReports->process($report, $request, 'Users Report');
    }
}
```

### Example: Multiple endpoints + authorization + route model binding

This is a more realistic example, similar to a sales export controller. The important part is always the same call:

`$this->laravelReports->process($report, $request, $title)`

The response mode is chosen automatically:

- `?preview=1` or `?stream=1` streams in the browser
- no flag downloads the PDF

```php
namespace App\Http\Controllers\Exports;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Reports\GeneralSalesReport;
use App\Reports\SaleReceiptReport;
use App\Reports\SingleSalesReport;
use Deifhelt\LaravelReports\LaravelReports;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class SaleExportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly LaravelReports $laravelReports)
    {
    }

    // GET /exports/sales
    public function index(Request $request)
    {
        $this->authorize('export', Sale::class);

        return $this->laravelReports->process(
            report: new GeneralSalesReport(),
            request: $request,
            title: 'Reporte General de Ventas',
        );
    }

    // GET /exports/sales/{sale}
    public function show(Sale $sale, Request $request)
    {
        $this->authorize('export', $sale);

        return $this->laravelReports->process(
            report: new SingleSalesReport($sale),
            request: $request,
            title: 'Venta #' . $sale->id,
        );
    }

    // GET /exports/sales/{sale}/receipt
    public function receipt(Sale $sale, Request $request)
    {
        $this->authorize('export', $sale);

        return $this->laravelReports->process(
            report: new SaleReceiptReport($sale),
            request: $request,
            title: 'Recibo #' . $sale->id,
        );
    }
}
```

### Alternative: Facade

If you prefer using the Facade, make sure you import the Facade class (not the concrete `Deifhelt\LaravelReports\LaravelReports` class), otherwise you'll get a “non-static method … cannot be called statically” type error.

```php
namespace App\Http\Controllers;

use App\Reports\UsersReport;
use Deifhelt\LaravelReports\Facades\LaravelReports;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function download(Request $request)
    {
        return LaravelReports::process(new UsersReport(), $request, 'Users Report');
    }
}
```

### Linking from Blade (routes + query params)

You typically expose routes in your app and link to them from Blade.

Example routes:

```php
use App\Http\Controllers\Exports\SaleExportController;

Route::get('/exports/sales', [SaleExportController::class, 'index'])->name('exports.sales.index');
Route::get('/exports/sales/{sale}', [SaleExportController::class, 'show'])->name('exports.sales.show');
Route::get('/exports/sales/{sale}/receipt', [SaleExportController::class, 'receipt'])->name('exports.sales.receipt');
```

Example links:

```blade
{{-- Downloads (no query flag) --}}
<a href="{{ route('exports.sales.index') }}">Descargar reporte general</a>

{{-- Streams in browser (adds ?preview=1) --}}
<a href="{{ route('exports.sales.index', ['preview' => 1]) }}">Ver reporte general</a>

{{-- Streams a single-sale report --}}
<a href="{{ route('exports.sales.show', ['sale' => $sale->getRouteKey(), 'preview' => 1]) }}">
    Ver venta #{{ $sale->id }}
</a>

{{-- Receipt example --}}
<a href="{{ route('exports.sales.receipt', ['sale' => $sale->getRouteKey()]) }}">Descargar recibo</a>
```

Notes:

- `?preview=1` (or `?stream=1`) streams in the browser; without it, the package downloads.
- Any query string params (like `date_from`, `date_to`, `role`, etc.) are available in your report's `query(Request $request)`.
- Validate/allowlist filters (FormRequest) before using them in a query.

## Additional Features

### Limit Validation

If you want to protect the report from massive queries, you can override the `shouldValidateLimit` method or configure it in your class:

```php
public function shouldValidateLimit(): bool
{
    return true; // Throws exception if record limit is exceeded (Default: 1000)
}
```

### Paper Configuration

You can customize the size and orientation:

```php
public function paper(): string|array
{
    return 'a4'; // or [0, 0, 500, 800]
}

public function orientation(): string
{
    return 'landscape';
}
```

## Security & Robustness

### Treat request input as untrusted

- The frontend should send intentions (IDs, filters, ranges). Your backend/report must calculate consequences (totals, taxes, amounts). Avoid trusting user-provided totals.
- Prefer strict validation of filters (FormRequest in your app) and map inputs explicitly (avoid passing arbitrary request keys into query logic).

### Sanitize and constrain filters

- Allowlist filter keys (e.g. `role`, `date_from`, `date_to`) and validate types/ranges.
- Avoid using raw `orderBy($request->sort)` or `whereRaw(...)` with user input.
- The package exposes `filters` to the Blade view using only query-string parameters. Still, avoid rendering unescaped user input.

### Blade/HTML safety for PDFs

- Use Blade escaping (`{{ }}`) for any user-provided values. Avoid `{!! !!}` unless you fully control the content.
- Be careful with external/remote assets (images/fonts). Review DomPDF settings such as remote access and chroot restrictions in your app's `config/dompdf.php`.

### Multi-tenant and authorization

- Always apply tenant scoping in `query()` (global scopes or explicit `where('tenant_id', ...)`).
- Enforce authorization in your controller before generating the report (Policies/Gates).
- If the report receives a model (e.g. single-record report), ensure the model is tenant-scoped and authorized.

## NativePHP Preview (Optional Integration)

This package does not depend on NativePHP, but it provides a small helper to make “open preview window” flows easy while still using the package's PDF `Response` for streaming.

### How it works

- Your "open preview" controller action calls the helper.
- The helper validates the limit (if `shouldValidateLimit()` is enabled).
- If validation passes, it calls your app-specific window opener and returns `204 No Content`.
- The NativePHP window loads a route with `?preview=1`, and that route returns the streamed PDF using the package.

Why two endpoints/methods are needed:

- `openPreview(...)` is an action that **opens the NativePHP window** (side-effect) and returns `204`. It is not meant to be used as a normal browser link.
- `index(...)` (or a dedicated `stream(...)`) is the action that **returns the PDF response** (stream/download). This is what the window (and the browser) loads.

If you are not using NativePHP, you usually only need `index()` (and optionally `show()`/`receipt()` etc.), and you can just link with `?preview=1`.

### 1) Implement a window opener adapter in your app

```php
use Deifhelt\LaravelReports\Interfaces\PreviewWindowOpener;
use Native\Laravel\Facades\Window;

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
use Deifhelt\LaravelReports\Interfaces\PreviewWindowOpener;

$this->app->singleton(PreviewWindowOpener::class, NativePhpWindowOpener::class);
```

### 2) Use `PreviewWindowReportManager` in your controller

```php
use App\Reports\GeneralSalesReport;
use App\Http\Controllers\Controller;
use Deifhelt\LaravelReports\LaravelReports;
use Deifhelt\LaravelReports\Preview\PreviewWindowReportManager;
use Illuminate\Http\Request;

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
        // When the window loads this route with ?preview=1, the package streams the PDF.
        // Otherwise, it downloads the PDF.
        return $this->laravelReports->process(new GeneralSalesReport(), $request, 'Reporte General de Ventas');
    }
}
```

#### Routes example

```php
use App\Http\Controllers\Exports\SaleExportController;
use Illuminate\Support\Facades\Route;

Route::get('/exports/sales', [SaleExportController::class, 'index'])
    ->name('exports.sales.index');

Route::get('/exports/sales/preview', [SaleExportController::class, 'openPreview'])
    ->name('exports.sales.preview');
```

#### Blade links for NativePHP vs browser

```blade
{{-- Browser preview: directly hit the PDF route with ?preview=1 --}}
<a href="{{ route('exports.sales.index', ['preview' => 1]) }}">Ver en navegador</a>

{{-- NativePHP preview: hit the opener route (returns 204, opens window) --}}
<a href="{{ route('exports.sales.preview') }}">Abrir en NativePHP</a>
```

#### Route model binding notes

If your preview opener route includes route-model-binding params (e.g. `/exports/sales/{sale}/receipt/preview`), the helper forwards them automatically to the target route (by converting models to their route keys).

