# Controller Integration

You can process reports using dependency injection (recommended) or the Facade.

## Dependency injection (recommended)

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

        // Streams in browser when the request has ?preview=1 (or ?stream=1)
        // Downloads otherwise
        // The title is available in the view as $title
        return $this->laravelReports->process($report, $request, 'Users Report');
    }
}
```

## Realistic example: multiple endpoints + authorization + route model binding

The important part is always the same call:

`$this->laravelReports->process($report, $request, $title)`

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

## Facade

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

## Linking from Blade (routes + query params)

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

Next: [Features & configuration](05-features-and-configuration.md)
Back: [Blade views & data](03-views-and-data.md)
