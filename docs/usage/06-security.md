# Security & Robustness

This package helps structure PDF generation, but your app is still responsible for security boundaries.

## Treat request input as untrusted

- The frontend should send intentions (IDs, filters, ranges).
- Your backend/report must calculate consequences (totals, taxes, amounts).

(Anchoring bias warning: its easy to anchor on a UI total and reuse it server-side; always recompute.)

## Sanitize and constrain filters

- Allowlist keys and validate types/ranges (FormRequest recommended)
- Avoid passing user input into raw SQL or dynamic column names

## Blade / HTML safety for PDFs

- Use Blade escaping (`{{ }}`) for user-provided values
- Avoid `{!! !!}` unless you fully control the content
- Review DomPDF settings such as remote access and chroot restrictions in your app's `config/dompdf.php`

## Multi-tenant and authorization

- Always apply tenant scoping in `query()` (global scopes or explicit `where('tenant_id', ...)`)
- Enforce authorization in your controller before generating the report

Next: [NativePHP preview (optional)](07-nativephp-preview.md)
Back: [Features & configuration](05-features-and-configuration.md)
