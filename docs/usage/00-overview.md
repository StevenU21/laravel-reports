# Usage Overview

Laravel Reports helps you generate PDF reports in a consistent, testable way by:

- Moving report concerns into dedicated classes (query + view + filename)
- Rendering via a pluggable PDF engine (DomPDF by default)
- Choosing **download vs stream** automatically using query flags
- Optionally validating query size to prevent expensive reports

## Core flow

1. Your app defines a report class (implements `ReportDefinition`).
2. A controller calls the report manager (`LaravelReports`).
3. The package executes `query(Request $request)`, prepares view data, and renders the Blade view into a PDF.

## Response mode (download vs preview)

`LaravelReports::process(...)` decides the response mode based on the request:

- `?preview=1` or `?stream=1`  streams in the browser
- no flag  downloads the PDF

## View data contract

Every PDF view receives these variables:

- `$data`: the result set (usually a `Collection` when you return a Builder)
- `$filters`: **only** the query-string parameters (`$request->query()`)
- `$title`: the title passed by your controller

Optionally, if your report defines `summary(Collection $data): array`, the view also receives:

- `$summary`
- `$totals` (alias of `$summary`)

Next: [Scaffolding (make:report)](01-scaffolding.md)
