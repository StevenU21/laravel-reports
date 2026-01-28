# Defining Reports

A report is a small class that describes:

- How to fetch data (`query(Request $request)`)
- Which Blade view to render (`view()`)
- What filename to use (`filename()`)
- Whether the package should validate report size (`shouldValidateLimit()`)

This keeps controllers skinny and makes reports easier to test.

## Example

```php
namespace App\Reports;

use App\Models\User;
use Deifhelt\LaravelReports\Interfaces\ReportDefinition;
use Deifhelt\LaravelReports\Traits\DefaultReportConfiguration;
use Illuminate\Http\Request;

class UsersReport implements ReportDefinition
{
    use DefaultReportConfiguration;

    public function query(Request $request)
    {
        // Returns a Query Builder, Eloquent Builder, Relation, Collection, or array.
        return User::query()
            ->when($request->has('role'), function ($q) use ($request) {
                $q->where('role', $request->role);
            });
    }

    public function view(): string
    {
        return 'reports.users';
    }

    public function filename(): string
    {
        return 'users-report-' . date('Y-m-d') . '.pdf';
    }
}
```

## Defaults via `DefaultReportConfiguration`

If you use the `DefaultReportConfiguration` trait, you get:

- `shouldValidateLimit()` defaulting to `false`
- `paper()` / `orientation()` defaulting to `letter` / `portrait`
- `viewData()` defaulting to `['data' => $data]`
- `summary()` defaulting to an empty array

## `query(Request $request)` return types

Return any of the following:

- Eloquent Builder
- Query Builder
- Relation
- Collection / array

When you return a Builder/Relation, the package will call `->get()` internally.

## Filters

Treat request input as untrusted.

- Validate and allowlist filters (FormRequest recommended)
- Avoid dynamic columns from request (e.g. `orderBy($request->sort)`) and raw SQL fragments
- Prefer explicit mapping from request keys to query constraints

## Optional methods supported

These methods are optional (the package checks them with `method_exists`):

- `paper(): string|array` (e.g. `'a4'` or `[0, 0, 500, 800]`)
- `orientation(): string` (`'portrait'` or `'landscape'`)
- `viewData(Collection $data): array`
- `summary(Collection $data): array` (produces `$summary`/`$totals`)
- `extraData(Request $request): array`

Next: [Blade views & data](03-views-and-data.md)
Back: [Scaffolding](01-scaffolding.md)
