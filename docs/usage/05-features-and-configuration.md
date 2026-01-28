# Features & Configuration

## Limit validation

To protect the report from massive queries, you can override `shouldValidateLimit()`:

```php
public function shouldValidateLimit(): bool
{
    return true; // validates against config('reports.limit')
}
```

When enabled, the package:

- Throws a `ReportException` if the result count is `0`
- Throws a `ReportException` if the result count is greater than `config('reports.limit')`

Configure the limit in `config/reports.php` (default: `1000`).

## Paper configuration

Customize the size and orientation:

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

## Custom PDF engines

DomPDF is the default renderer.

If you want to use a different engine (e.g. Snappy/Wkhtmltopdf), bind the renderer interface in your application container.

See: ../configuration.md

Next: [Security & robustness](06-security.md)
Back: [Controller integration](04-controller-integration.md)
