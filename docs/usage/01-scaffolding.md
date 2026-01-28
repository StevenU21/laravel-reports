# Scaffolding (make:report)

The quickest way to start is the Artisan generator.

## 1) Generate a report

```bash
php artisan make:report UsersReport
```

This command generates:

1. **Report Class**: `app/Reports/UsersReport.php`
2. **Blade View**: `resources/views/reports/users-report.blade.php`
3. **Pest Test**: `tests/Feature/Reports/UsersReportTest.php`

## 2) Generate a report tied to a model

```bash
php artisan make:report UsersReport --model=User
```

This scaffolds a `query()` method based on `User::query()`.

## Notes

- Report classes are generated under `App\Reports`.
- Views are created under `resources/views/reports`.
- Filenames include a timestamp suffix by default.

Next: [Defining reports](02-report-definition.md)
Back: [Usage overview](00-overview.md)
