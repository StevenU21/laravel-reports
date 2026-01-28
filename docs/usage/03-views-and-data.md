# Blade Views & Data

A report renders a Blade view into a PDF.

## Where views live

By convention, report views live in:

- `resources/views/reports/*.blade.php`

Your reports `view()` returns the dot-notation view name (e.g. `reports.users`).

## Variables available in the PDF view

The package always provides:

- `$data`
- `$filters` (query string only)
- `$title`

### `$filters` design note

`$filters` is populated from `$request->query()` only. This avoids accidentally leaking body payloads or uploaded files into the PDF view.

## Example Blade view

Create your Blade file in `resources/views/reports/users.blade.php`. The `$data` variable will contain the results of your query.

```html
<!DOCTYPE html>
<html>
    <head>
        <title>{{ $title ?? 'Users Report' }}</title>
        <style>
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
        <h1>{{ $title ?? 'Users List' }}</h1>
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

## Adding more view variables

Use either of these optional methods on your report:

- `viewData(Collection $data): array` for variables derived from the dataset
- `extraData(Request $request): array` for variables derived from request/context

## Summary / totals

If your report defines `summary(Collection $data): array`, the package exposes:

- `$summary`
- `$totals` (alias)

Next: [Controller integration](04-controller-integration.md)
Back: [Defining reports](02-report-definition.md)
